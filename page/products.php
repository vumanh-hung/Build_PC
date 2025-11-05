<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ===== CSRF TOKEN =====
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// ===== LẤY DANH SÁCH DANH MỤC & THƯƠNG HIỆU =====
$pdo = getPDO();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$brands = $pdo->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// ===== XỬ LÝ TÌM KIẾM / LỌC =====
$keyword = trim($_GET['keyword'] ?? '');
$category_id = $_GET['category_id'] ?? '';
$brand_id = $_GET['brand_id'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "p.name LIKE :keyword";
    $params[':keyword'] = "%$keyword%";
}
if ($category_id !== '') {
    $where[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}
if ($brand_id !== '') {
    $where[] = "p.brand_id = :brand_id";
    $params[':brand_id'] = $brand_id;
}
if ($min_price !== '') {
    $where[] = "p.price >= :min_price";
    $params[':min_price'] = $min_price;
}
if ($max_price !== '') {
    $where[] = "p.price <= :max_price";
    $params[':max_price'] = $max_price;
}

$sql = "
    SELECT p.*, c.name AS category_name, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY p.product_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Lấy số lượng giỏ hàng
$user_id = getCurrentUserId();
$cart_count = $user_id ? getCartCount($user_id) : 0;

// ===== LẤY REVIEWS DATA =====
$stmt = $pdo->prepare("
    SELECT 
        AVG(r.rating) as avg_rating,
        COUNT(*) as total_reviews,
        SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) as rating_5,
        SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END) as rating_4,
        SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END) as rating_3,
        SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END) as rating_2,
        SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END) as rating_1
    FROM reviews r
    WHERE r.status = 'approved'
");
$stmt->execute();
$review_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy 6 reviews mới nhất
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, p.name as product_name
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN products p ON r.product_id = p.product_id
    WHERE r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 6
");
$stmt->execute();
$recent_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== HANDLE WRITE REVIEW FORM =====
$review_error = '';
$review_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'write_review') {
    if (!isset($_SESSION['user']['user_id'])) {
        $review_error = 'Vui lòng đăng nhập để viết đánh giá';
    } else {
        $product_id = intval($_POST['product_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 5);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $user_id = $_SESSION['user']['user_id'];

        if (!$product_id) {
            $review_error = 'Sản phẩm không tồn tại';
        } elseif (!hasUserPurchasedProduct($pdo, $product_id, $user_id)) {
            $review_error = 'Bạn cần mua sản phẩm này trước khi viết đánh giá';
        } elseif (hasUserReviewedProduct($pdo, $product_id, $user_id)) {
            $review_error = 'Bạn đã viết đánh giá cho sản phẩm này';
        } elseif ($rating < 1 || $rating > 5) {
            $review_error = 'Rating không hợp lệ';
        } elseif (empty($title) || strlen($title) < 5) {
            $review_error = 'Tiêu đề phải có ít nhất 5 ký tự';
        } elseif (empty($content) || strlen($content) < 20) {
            $review_error = 'Nội dung phải có ít nhất 20 ký tự';
        } else {
            $result = createReview($pdo, $product_id, $user_id, $title, $content, $rating);
            
            if ($result['success']) {
                $review_id = $result['review_id'];
                
                // ✅ Xử lý upload ảnh
                if (!empty($_FILES['images']['name'][0])) {
                    $upload_dir = dirname(__FILE__) . '/../uploads/reviews/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        if (!empty($tmp_name) && $_FILES['images']['error'][$key] === 0) {
                            $file_ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                            
                            if (in_array($file_ext, $allowed) && $_FILES['images']['size'][$key] <= 5000000) {
                                $filename = 'review_' . $review_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                                $filepath = $upload_dir . $filename;
                                
                                if (move_uploaded_file($tmp_name, $filepath)) {
                                    addReviewImage($pdo, $review_id, 'uploads/reviews/' . $filename);
                                }
                            }
                        }
                    }
                }
                
                $review_success = true;
            } else {
                $review_error = $result['message'] ?? 'Có lỗi xảy ra';
            }
        }
    }
}

// ===== HELPER FUNCTIONS =====
function renderProducts($products, $csrf, $isLoggedIn) {
    foreach ($products as $p): 
        $image_path = getProductImagePath($p['main_image']);
    ?>
        <div class="product-card">
            <div class="image-wrapper">
                <img src="../<?= escape($image_path) ?>" 
                     alt="<?= escape($p['name']) ?>"
                     onerror="this.src='../uploads/img/no-image.png'">
            </div>
            <div class="info">
                <h3 class="product-name"><?= escape($p['name']) ?></h3>
                <p class="brand-cat">
                    <?= escape($p['brand_name'] ?? 'Thương hiệu') ?> • 
                    <?= escape($p['category_name'] ?? 'Danh mục') ?>
                </p>
                <p class="price"><?= formatPriceVND($p['price']) ?></p>

                <?php if ($isLoggedIn): ?>
                <div class="product-actions">
                    <input type="number" 
                           class="qty-input" 
                           value="1" 
                           min="1" 
                           max="99" 
                           data-product-id="<?= $p['product_id'] ?>">
                    <button type="button" 
                            class="add-to-cart-btn" 
                            data-product-id="<?= $p['product_id'] ?>"
                            data-product-name="<?= escape($p['name']) ?>">
                        <i class="fa-solid fa-cart-plus"></i> Thêm
                    </button>
                </div>
                <?php else: ?>
                    <a href="login.php" class="btn-login">
                        <i class="fa-solid fa-user"></i> Đăng nhập để mua
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php 
    endforeach;
}

function renderStarsBadge($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fa-solid fa-star"></i>';
        } elseif ($i - $rating < 1) {
            $stars .= '<i class="fa-solid fa-star-half-stroke"></i>';
        } else {
            $stars .= '<i class="fa-regular fa-star"></i>';
        }
    }
    return $stars;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sản phẩm - BuildPC.vn</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html { scroll-behavior: smooth; }

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  color: #333;
  min-height: 100vh;
}

/* ===== HEADER ===== */
header {
  background: linear-gradient(90deg, #007bff 0%, #00aaff 50%, #007bff 100%);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 40px;
  box-shadow: 0 8px 24px rgba(0, 107, 255, 0.15);
  position: sticky;
  top: 0;
  z-index: 999;
  gap: 20px;
  backdrop-filter: blur(10px);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 40px;
}

.logo {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.logo span {
  color: white;
  font-weight: 800;
  font-size: 20px;
  letter-spacing: 0.5px;
  white-space: nowrap;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.nav {
  display: flex;
  align-items: center;
  gap: 28px;
}

.nav a {
  color: white;
  text-decoration: none;
  font-weight: 500;
  font-size: 13px;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.nav a:hover,
.nav a.active {
  color: #ffeb3b;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.cart-link {
  position: relative;
  background: rgba(255, 255, 255, 0.95);
  color: #007bff;
  padding: 8px 16px;
  border-radius: 20px;
  text-decoration: none;
  font-weight: 600;
  font-size: 12px;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.cart-link:hover {
  background: white;
  box-shadow: 0 6px 20px rgba(0, 107, 255, 0.3);
  transform: translateY(-3px);
}

.cart-count {
  position: absolute;
  top: -8px;
  right: -8px;
  background: linear-gradient(135deg, #ffeb3b, #ff9800);
  color: #111;
  font-size: 10px;
  font-weight: 900;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
}

.login-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 12px;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
  white-space: nowrap;
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
  border: 2px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.login-btn:hover {
  background: #ffffff;
  color: #007bff;
  border-color: #ffffff;
  transform: translateY(-3px);
}

.logout-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 12px;
  text-decoration: none;
  background: linear-gradient(135deg, #ff5252, #ff1744);
  color: white;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
}

.logout-btn:hover {
  background: linear-gradient(135deg, #ff1744, #d50000);
  transform: translateY(-3px);
}

.welcome {
  color: #fff;
  font-size: 12px;
  font-weight: 600;
}

/* 💫 Rung icon giỏ hàng */
@keyframes cartShake {
  0% { transform: rotate(0deg); }
  25% { transform: rotate(-15deg); }
  50% { transform: rotate(15deg); }
  75% { transform: rotate(-10deg); }
  100% { transform: rotate(0deg); }
}

.cart-shake {
  animation: cartShake 0.6s ease;
}

/* ===== BANNER ===== */
.banner {
  background: linear-gradient(135deg, #1a73e8 0%, #1e88e5 50%, #1565c0 100%);
  color: white;
  text-align: center;
  padding: 50px 20px;
  position: relative;
  overflow: hidden;
}

.banner::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(255, 235, 59, 0.15) 0%, transparent 70%);
  pointer-events: none;
  animation: float 20s ease-in-out infinite;
}

.banner::after {
  content: '';
  position: absolute;
  bottom: -50%;
  left: -10%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(33, 150, 243, 0.1) 0%, transparent 70%);
  pointer-events: none;
  animation: float 25s ease-in-out infinite reverse;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(30px); }
}

.banner h1 {
  font-size: 36px;
  margin-bottom: 10px;
  font-weight: 900;
  text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  position: relative;
  z-index: 1;
  letter-spacing: -0.5px;
}

.banner p {
  font-size: 14px;
  opacity: 0.95;
  position: relative;
  z-index: 1;
  font-weight: 300;
  letter-spacing: 0.5px;
}

/* ===== CONTAINER ===== */
.container {
  max-width: 1400px;
  margin: 40px auto;
  padding: 0 20px;
}

.page-title {
  font-size: 28px;
  color: #1a73e8;
  text-align: center;
  margin-bottom: 30px;
  font-weight: 800;
  letter-spacing: -0.5px;
}

/* ===== SEARCH BAR ===== */
.search-bar {
  background: white;
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 40px;
  animation: slideDown 0.6s ease-out;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

.search-bar input,
.search-bar select {
  padding: 10px 14px;
  border: 2px solid #e8e8e8;
  border-radius: 8px;
  font-size: 13px;
  transition: all 0.3s ease;
  background: white;
  font-family: inherit;
}

.search-bar input:focus,
.search-bar select:focus {
  outline: none;
  border-color: #1a73e8;
  box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
  background: #f0f7ff;
}

.search-bar input::placeholder { color: #999; }

.btn-search {
  background: linear-gradient(135deg, #1a73e8, #1565c0);
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(26, 115, 232, 0.2);
  grid-column: auto / span 1;
}

.btn-search:hover {
  background: linear-gradient(135deg, #1565c0, #0d47a1);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(26, 115, 232, 0.3);
}

/* ===== PRODUCT GRID ===== */
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 24px;
  animation: fadeIn 0.6s ease-out 0.2s both;
  margin-bottom: 60px;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.product-card {
  background: white;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.product-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 28px rgba(26, 115, 232, 0.25);
}

.image-wrapper {
  position: relative;
  width: 100%;
  height: 180px;
  overflow: hidden;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.image-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.product-card:hover .image-wrapper img {
  transform: scale(1.08);
}

.info {
  padding: 16px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.product-name {
  font-size: 14px;
  font-weight: 700;
  color: #222;
  margin-bottom: 6px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.brand-cat {
  font-size: 11px;
  color: #999;
  margin-bottom: 10px;
  font-weight: 500;
}

.price {
  color: #1a73e8;
  font-weight: 800;
  font-size: 16px;
  margin-bottom: 12px;
  letter-spacing: -0.5px;
}

.product-actions {
  display: flex;
  gap: 8px;
  margin-top: auto;
}

.qty-input {
  width: 60px;
  padding: 8px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 12px;
  text-align: center;
  font-weight: 600;
  transition: all 0.3s ease;
}

.qty-input:focus {
  outline: none;
  border-color: #1a73e8;
  background: #f0f7ff;
}

.add-to-cart-btn {
  flex: 1;
  background: linear-gradient(135deg, #1a73e8, #1565c0);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 12px;
  font-weight: 700;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  box-shadow: 0 3px 8px rgba(26, 115, 232, 0.2);
}

.add-to-cart-btn:hover {
  background: linear-gradient(135deg, #1565c0, #0d47a1);
  transform: translateY(-2px);
  box-shadow: 0 5px 12px rgba(26, 115, 232, 0.3);
}

.add-to-cart-btn:active {
  transform: translateY(0);
}

.add-to-cart-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-login {
  display: inline-block;
  background: linear-gradient(135deg, #4caf50, #45a049);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 12px;
  font-weight: 700;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
  text-decoration: none;
  margin-top: auto;
  box-shadow: 0 3px 8px rgba(76, 175, 80, 0.2);
}

.btn-login:hover {
  background: linear-gradient(135deg, #45a049, #388e3c);
  transform: translateY(-2px);
  box-shadow: 0 5px 12px rgba(76, 175, 80, 0.3);
}

.no-products {
  text-align: center;
  padding: 60px 20px;
  color: #999;
  font-size: 16px;
  grid-column: 1 / -1;
}

.no-products i {
  font-size: 48px;
  margin-bottom: 16px;
  display: block;
  opacity: 0.5;
}

/* 🪄 Cart Popup */
.cart-popup {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: #28a745;
  color: white;
  padding: 14px 22px;
  border-radius: 8px;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  opacity: 0;
  transform: translateY(30px);
  transition: all 0.4s ease;
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 8px;
}

.cart-popup.show {
  opacity: 1;
  transform: translateY(0);
}

/* ===== REVIEWS SECTION ===== */
.reviews-section {
  max-width: 1400px;
  margin: 60px auto;
  padding: 40px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.reviews-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 2px solid #e8e8e8;
}

.reviews-header h2 {
  font-size: 24px;
  color: #1a73e8;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.btn-write-review {
  background: linear-gradient(135deg, #28a745, #218838);
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  font-size: 13px;
  transition: all 0.3s ease;
  box-shadow: 0 3px 8px rgba(40, 167, 69, 0.2);
  cursor: pointer;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-write-review:hover {
  background: linear-gradient(135deg, #218838, #1e7e34);
  transform: translateY(-2px);
  box-shadow: 0 5px 12px rgba(40, 167, 69, 0.3);
}

.btn-view-all-reviews {
  background: linear-gradient(135deg, #1a73e8, #1565c0);
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  font-size: 13px;
  transition: all 0.3s ease;
  box-shadow: 0 3px 8px rgba(26, 115, 232, 0.2);
}

.btn-view-all-reviews:hover {
  background: linear-gradient(135deg, #1565c0, #0d47a1);
  transform: translateY(-2px);
  box-shadow: 0 5px 12px rgba(26, 115, 232, 0.3);
}

.reviews-stats {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: 30px;
  margin-bottom: 40px;
  padding: 20px;
  background: linear-gradient(135deg, #f0f7ff 0%, #e3f2fd 100%);
  border-radius: 12px;
}

.rating-summary { text-align: center; }

.rating-value-large {
  font-size: 48px;
  font-weight: 900;
  color: #1a73e8;
  margin-bottom: 8px;
}

.rating-stars-large {
  display: flex;
  justify-content: center;
  margin-bottom: 8px;
  gap: 2px;
}

.rating-stars-large i { color: #ffc107; font-size: 18px; }

.rating-count-text { color: #666; font-size: 12px; font-weight: 600; }

.rating-distribution { display: flex; flex-direction: column; gap: 10px; }

.rating-bar-row { display: flex; align-items: center; gap: 10px; }

.rating-bar-label { font-size: 12px; font-weight: 600; width: 40px; color: #333; }

.rating-bar-track { flex: 1; height: 8px; background: #ddd; border-radius: 4px; overflow: hidden; }

.rating-bar-fill { height: 100%; background: linear-gradient(90deg, #ffc107, #ff9800); border-radius: 4px; transition: width 0.3s ease; }

.rating-bar-count { font-size: 12px; color: #666; width: 30px; text-align: right; font-weight: 600; }

.reviews-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }

.review-item {
  background: #f9f9f9;
  padding: 18px;
  border-radius: 12px;
  border: 1px solid #e8e8e8;
  transition: all 0.3s ease;
}

.review-item:hover {
  box-shadow: 0 4px 12px rgba(26, 115, 232, 0.15);
  border-color: #1a73e8;
  background: white;
}

.review-item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 10px;
}

.review-item-author { font-weight: 700; color: #333; font-size: 13px; }
.review-item-date { color: #999; font-size: 11px; font-weight: 500; }

.review-item-rating { display: flex; gap: 2px; margin-bottom: 10px; }
.review-item-rating i { color: #ffc107; font-size: 12px; }

.review-item-title { font-weight: 700; color: #333; font-size: 13px; margin-bottom: 8px; }

.review-item-content {
  color: #666;
  font-size: 12px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 12px;
}

.review-item-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11px;
  color: #999;
  padding-top: 10px;
  border-top: 1px solid #e8e8e8;
}

.review-badge { background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; }

.no-reviews { text-align: center; padding: 40px 20px; color: #999; }
.no-reviews i { font-size: 48px; margin-bottom: 12px; display: block; opacity: 0.5; }

.review-item-images {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px;
  margin-top: 10px;
}

.review-item-img {
  width: 100%;
  height: 60px;
  border-radius: 4px;
  overflow: hidden;
}

.review-item-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* ===== MODAL STYLES ===== */
.modal {
  display: none;
  position: fixed;
  z-index: 2000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  animation: fadeInModal 0.3s ease;
  align-items: center;
  justify-content: center;
}

@keyframes fadeInModal {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal.active { display: flex; }

.modal-content {
  background: white;
  padding: 30px;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 8px 32px rgba(0,0,0,0.2);
  animation: slideUpModal 0.3s ease;
}

@keyframes slideUpModal {
  from { transform: translateY(30px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.modal-header h2 { font-size: 20px; color: #333; font-weight: 800; }

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #999;
  transition: color 0.3s ease;
}

.modal-close:hover { color: #333; }

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #333;
  font-weight: 600;
  font-size: 14px;
}

.form-group label .required { color: #dc3545; }

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 13px;
  font-family: inherit;
  transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #1a73e8;
  box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
  background: #f0f7ff;
}

.form-group textarea { resize: vertical; min-height: 120px; }

.rating-input {
  display: flex;
  gap: 10px;
  font-size: 28px;
  margin-bottom: 15px;
}

.rating-input button {
  background: none;
  border: none;
  cursor: pointer;
  color: #ddd;
  transition: all 0.3s ease;
  padding: 0;
  font-size: 32px;
}

.rating-input button:hover { color: #ffc107; }
.rating-input button.active { color: #ffc107; }

.char-count { font-size: 12px; color: #999; margin-top: 5px; }

.upload-area {
  border: 2px dashed #ddd;
  border-radius: 6px;
  padding: 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.upload-area:hover { border-color: #1a73e8; background: #f0f7ff; }

.upload-area i { font-size: 32px; color: #1a73e8; margin-bottom: 10px; }

.preview-images { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; margin-top: 15px; }

.preview-img { width: 100%; aspect-ratio: 1; border-radius: 6px; overflow: hidden; position: relative; }

.preview-img img { width: 100%; height: 100%; object-fit: cover; }

.modal-actions {
  display: flex;
  gap: 10px;
  margin-top: 25px;
}

.modal-actions button {
  flex: 1;
  padding: 12px 20px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-cancel { background: #6c757d; color: white; }
.btn-cancel:hover { background: #5a6268; transform: translateY(-2px); }

.btn-submit { background: #28a745; color: white; }
.btn-submit:hover { background: #218838; transform: translateY(-2px); }

.error-msg {
  background: #f8d7da;
  color: #721c24;
  padding: 12px 15px;
  border-radius: 6px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1px solid #f5c6cb;
}

.success-msg {
  background: #d4edda;
  color: #155724;
  padding: 12px 15px;
  border-radius: 6px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1px solid #c3e6cb;
}

/* ===== TOAST ===== */
.toast {
  position: fixed;
  right: 20px;
  bottom: 20px;
  background: linear-gradient(135deg, #4caf50, #45a049);
  color: white;
  padding: 14px 20px;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(76, 175, 80, 0.3);
  display: none;
  font-weight: 600;
  font-size: 13px;
  z-index: 3000;
  animation: slideIn 0.4s ease-out;
}

@keyframes slideIn {
  from { transform: translateX(400px); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

.toast.error {
  background: linear-gradient(135deg, #f44336, #d32f2f);
  box-shadow: 0 8px 24px rgba(244, 67, 54, 0.3);
}

/* ===== FOOTER ===== */
footer {
  background: linear-gradient(90deg, #007bff 0%, #00aaff 50%, #007bff 100%);
  color: white;
  text-align: center;
  padding: 24px 20px;
  margin-top: 60px;
  font-size: 13px;
  box-shadow: 0 -4px 12px rgba(0, 107, 255, 0.1);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
  header { padding: 10px 24px; gap: 16px; }
  .nav { gap: 20px; }
  .banner h1 { font-size: 28px; }
  .search-bar { grid-template-columns: repeat(2, 1fr); }
  .product-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 18px; }
  .reviews-stats { grid-template-columns: 1fr; gap: 20px; }
  .reviews-list { grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); }
}

@media (max-width: 768px) {
  header { padding: 10px 16px; flex-wrap: wrap; }
  .header-left { gap: 20px; width: 100%; }
  .logo span { font-size: 16px; }
  .nav { gap: 16px; font-size: 12px; }
  .header-right { width: 100%; justify-content: flex-start; }
  .banner h1 { font-size: 24px; }
  .container { margin: 30px auto; }
  .page-title { font-size: 22px; }
  .search-bar { grid-template-columns: 1fr; gap: 10px; padding: 16px; }
  .btn-search { grid-column: 1 / -1; }
  .product-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
  .image-wrapper { height: 140px; }
  .product-name { font-size: 12px; }
  .price { font-size: 14px; }
  .reviews-section { padding: 24px; margin: 40px auto; }
  .reviews-header { flex-direction: column; gap: 15px; align-items: flex-start; }
  .reviews-list { grid-template-columns: 1fr; }
  .modal-content { width: 95%; padding: 20px; }
}

@media (max-width: 480px) {
  header { padding: 8px 12px; }
  .logo span { font-size: 14px; }
  .nav { gap: 12px; font-size: 11px; }
  .cart-link, .login-btn, .logout-btn { font-size: 11px; padding: 6px 12px; }
  .banner h1 { font-size: 20px; }
  .banner p { font-size: 12px; }
  .page-title { font-size: 18px; }
  .product-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .image-wrapper { height: 120px; }
  .info { padding: 12px; }
  .product-name { font-size: 11px; }
  .brand-cat { font-size: 10px; }
  .price { font-size: 13px; }
  .reviews-section { padding: 16px; margin: 30px auto; }
  .reviews-header h2 { font-size: 18px; }
  .btn-view-all-reviews, .btn-write-review { font-size: 12px; padding: 8px 16px; }
  .rating-value-large { font-size: 36px; }
  .review-item { padding: 12px; }
  .review-item-title { font-size: 12px; }
  .review-item-content { font-size: 11px; }
  .modal-content { padding: 16px; }
  .modal-header h2 { font-size: 16px; }
}
</style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header>
  <div class="header-left">
    <div class="logo">
      <a href="../index.php" style="text-decoration: none;">
        <span>🖥️ BuildPC.vn</span>
      </a>
    </div>
    <nav class="nav">
      <a href="../index.php">Trang chủ</a>
      <a href="products.php" class="active">Sản phẩm</a>
      <a href="brands.php">Thương hiệu</a>
      <a href="builds.php">Xây dựng cấu hình</a>
      <a href="about.php">Giới thiệu</a>
      <a href="contact.php">Liên hệ</a>
    </nav>
  </div>

  <div class="header-right">
    <a href="cart.php" class="cart-link">
      <i class="fa-solid fa-cart-shopping"></i> Giỏ hàng
      <?php if ($cart_count > 0): ?>
        <span class="cart-count"><?= $cart_count ?></span>
      <?php endif; ?>
    </a>

    <?php if (isset($_SESSION['user'])): ?>
      <span class="welcome">👋 <?= htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['full_name']) ?></span>
      <a href="logout.php" class="logout-btn">Đăng xuất</a>
    <?php else: ?>
      <a href="login.php" class="login-btn"><i class="fa-solid fa-user"></i> Đăng nhập</a>
    <?php endif; ?>
  </div>
</header>

<!-- ===== BANNER ===== -->
<div class="banner">
  <h1>Danh Sách Sản Phẩm</h1>
  <p>Tìm những sản phẩm công nghệ tốt nhất theo nhu cầu của bạn</p>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="container">
  <h1 class="page-title">💻 Sản Phẩm</h1>

  <!-- ===== SEARCH & FILTER ===== -->
  <form method="GET" class="search-bar">
    <input type="text" name="keyword" placeholder="Tìm sản phẩm..." value="<?php echo htmlspecialchars($keyword); ?>">
    
    <select name="category_id">
      <option value="">-- Danh mục --</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?php echo $c['category_id']; ?>" <?php echo ($category_id == $c['category_id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($c['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="brand_id">
      <option value="">-- Thương hiệu --</option>
      <?php foreach ($brands as $b): ?>
        <option value="<?php echo $b['brand_id']; ?>" <?php echo ($brand_id == $b['brand_id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($b['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <input type="number" name="min_price" placeholder="Giá từ..." value="<?php echo htmlspecialchars($min_price); ?>">
    <input type="number" name="max_price" placeholder="Giá đến..." value="<?php echo htmlspecialchars($max_price); ?>">

    <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</button>
  </form>

   <!-- ===== PRODUCT LIST ===== -->
  <?php if (empty($products)): ?>
    <div class="product-grid">
      <div class="no-products">
        <i class="fa-solid fa-magnifying-glass"></i>
        <p>Không tìm thấy sản phẩm nào phù hợp.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="product-grid">
      <?php renderProducts($products, $csrf, isLoggedIn()); ?>
    </div>
  <?php endif; ?>
</div>

<!-- ===== REVIEWS SECTION ===== -->
<div class="reviews-section">
  <div class="reviews-header">
    <h2>⭐ Đánh Giá Từ Khách Hàng</h2>
    <div style="display: flex; gap: 10px;">
      <button class="btn-write-review" onclick="openReviewModal()" title="Viết đánh giá">
        <i class="fa-solid fa-pen"></i> Viết đánh giá
      </button>
      <a href="product-reviews.php" class="btn-view-all-reviews">Xem Tất Cả →</a>
    </div>
  </div>

  <?php if ($review_stats && $review_stats['total_reviews'] > 0): ?>
    <div class="reviews-stats">
      <div class="rating-summary">
        <div class="rating-value-large"><?= number_format($review_stats['avg_rating'], 1) ?></div>
        <div class="rating-stars-large"><?= renderStarsBadge($review_stats['avg_rating']) ?></div>
        <div class="rating-count-text"><?= $review_stats['total_reviews'] ?> đánh giá</div>
      </div>

      <div class="rating-distribution">
        <?php for ($i = 5; $i >= 1; $i--):
            $count = $review_stats["rating_$i"] ?? 0;
            $percentage = $review_stats['total_reviews'] > 0 ? ($count / $review_stats['total_reviews']) * 100 : 0;
        ?>
            <div class="rating-bar-row">
              <span class="rating-bar-label"><?= $i ?>★</span>
              <div class="rating-bar-track">
                <div class="rating-bar-fill" style="width: <?= $percentage ?>%;"></div>
              </div>
              <span class="rating-bar-count"><?= $count ?></span>
            </div>
        <?php endfor; ?>
      </div>
    </div>

    <div class="reviews-list">
      <?php foreach ($recent_reviews as $review): 
        $images = getReviewImages($pdo, $review['review_id']);
      ?>
        <div class="review-item">
          <div class="review-item-header">
            <div>
              <div class="review-item-author"><?= htmlspecialchars($review['full_name']) ?></div>
              <div class="review-item-date"><?= date('d/m/Y', strtotime($review['created_at'])) ?></div>
            </div>
            <span class="review-badge">✓ Đã mua</span>
          </div>

          <div class="review-item-rating"><?= renderStarsBadge($review['rating']) ?></div>
          <div class="review-item-title"><?= htmlspecialchars($review['title']) ?></div>
          <div class="review-item-content"><?= htmlspecialchars($review['content']) ?></div>

          <?php if (!empty($images) && count($images) > 0): ?>
            <div class="review-item-images">
              <?php foreach (array_slice($images, 0, 3) as $img): ?>
                <div class="review-item-img">
                  <img src="../<?= htmlspecialchars($img['image_path']) ?>" alt="Review" onerror="this.src='../assets/images/placeholder.jpg'">
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="review-item-footer">
            <span>📦 <?= htmlspecialchars(substr($review['product_name'], 0, 20)) ?>...</span>
            <span>👍 <?= $review['helpful_count'] ?? 0 ?> hữu ích</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="no-reviews">
      <i class="fa-solid fa-star"></i>
      <p>Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
    </div>
  <?php endif; ?>
</div>

<!-- ===== REVIEW MODAL ===== -->
<div id="reviewModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Viết Đánh Giá</h2>
      <button class="modal-close" onclick="closeReviewModal()">×</button>
    </div>

    <?php if ($review_success): ?>
      <div class="success-msg">
        <span>✓</span>
        <span>Đánh giá của bạn đã được gửi thành công! Sẽ được kiểm duyệt trong 24 giờ.</span>
      </div>
    <?php elseif (!empty($review_error)): ?>
      <div class="error-msg">
        <span>⚠️</span>
        <span><?= htmlspecialchars($review_error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="write_review">
      <input type="hidden" name="product_id" id="modalProductId" value="">
      
      <!-- Rating -->
      <div class="form-group">
        <label>Đánh giá <span class="required">*</span></label>
        <div class="rating-input" id="ratingInput">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="button" class="rating-btn <?= $i <= 5 ? 'active' : '' ?>" data-rating="<?= $i ?>" onclick="setRating(<?= $i ?>, event)">★</button>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" value="5" id="ratingValue">
      </div>

      <!-- Title -->
      <div class="form-group">
        <label>Tiêu đề <span class="required">*</span></label>
        <input type="text" name="title" placeholder="Ví dụ: Sản phẩm rất tốt, giao hàng nhanh" maxlength="200" required oninput="updateCount(this, 'titleCount')">
        <div class="char-count"><span id="titleCount">0</span>/200</div>
      </div>

      <!-- Content -->
      <div class="form-group">
        <label>Nội dung <span class="required">*</span></label>
        <textarea name="content" placeholder="Hãy kể chi tiết về sản phẩm này..." maxlength="2000" required oninput="updateCount(this, 'contentCount')"></textarea>
        <div class="char-count"><span id="contentCount">0</span>/2000</div>
      </div>

      <!-- Images -->
      <div class="form-group">
        <label>Thêm ảnh (tùy chọn)</label>
        <div class="upload-area" onclick="document.getElementById('reviewImageInput').click()" ondragover="this.style.background='#f0f7ff'" ondragleave="this.style.background='white'" ondrop="handleImageDrop(event)">
          <div><i class="fa-solid fa-image"></i></div>
          <div>Kéo và thả ảnh hoặc click để chọn</div>
          <small>Tối đa 5 ảnh, mỗi ảnh dưới 5MB (JPG, PNG, WebP)</small>
        </div>
        <input type="file" id="reviewImageInput" name="images[]" multiple accept="image/*" style="display: none;" onchange="previewReviewImages(this.files)">
        <div id="previewImages" class="preview-images"></div>
      </div>

      <!-- Actions -->
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeReviewModal()">Hủy</button>
        <button type="submit" class="btn-submit">✓ Gửi Đánh Giá</button>
      </div>
    </form>
  </div>
</div>

<!-- 🪄 Cart Popup -->
<div id="cart-popup" class="cart-popup">
  <i class="fa-solid fa-check-circle"></i> Đã thêm vào giỏ hàng!
</div>

<!-- 🔊 Audio for notification sound -->
<audio id="tingSound" preload="auto">
  <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<!-- ===== FOOTER ===== -->
<footer>
  <p>© <?= date('Y') ?> BuildPC.vn — Máy tính & Linh kiện chính hãng</p>
</footer>

<script>
// ===== Enable audio on first user interaction =====
document.addEventListener("click", () => {
  const sound = document.getElementById("tingSound");
  if (sound && sound.paused) {
    sound.play().then(() => { 
      sound.pause(); 
      sound.currentTime = 0; 
    }).catch(()=>{});
  }
}, { once: true });

// ===== CONSTANTS =====
const CSRF_TOKEN = <?= json_encode($csrf) ?>;
const API_URL = '../api/cart_api.php';

// ===== UTILITY FUNCTIONS =====
function playTingSound() {
  const sound = document.getElementById("tingSound");
  if (sound) {
    sound.play().catch(()=>{});
  }
}

function shakeCartIcon() {
  const cartIcon = document.querySelector(".fa-cart-shopping");
  if (cartIcon) {
    cartIcon.classList.add("cart-shake");
    setTimeout(() => cartIcon.classList.remove("cart-shake"), 700);
  }
}

function showCartPopup() {
  const popup = document.getElementById("cart-popup");
  popup.classList.add("show");
  setTimeout(() => popup.classList.remove("show"), 3000);
}

function updateCartBadge(count) {
  let badge = document.querySelector('.cart-count');
  const cartLink = document.querySelector('.cart-link');
  
  if (count > 0) {
    if (badge) {
      badge.textContent = count;
    } else {
      badge = document.createElement('span');
      badge.className = 'cart-count';
      badge.textContent = count;
      cartLink.appendChild(badge);
    }
  } else if (badge) {
    badge.remove();
  }
}

// ===== CART FUNCTIONS =====
async function refreshCartCount() {
  try {
    const response = await fetch(API_URL, {
      method: 'GET',
      credentials: 'include'
    });
    
    if (!response.ok) throw new Error('Network response was not ok');
    
    const data = await response.json();
    
    if (data.ok && data.cart_count !== undefined) {
      updateCartBadge(data.cart_count);
    }
  } catch (error) {
    console.error('Error refreshing cart count:', error);
  }
}

async function addToCart(productId, quantity = 1, productName = '') {
  if (quantity < 1 || quantity > 99) {
    alert('❌ Số lượng không hợp lệ (1-99)');
    return;
  }

  const formData = new FormData();
  formData.append('action', 'add');
  formData.append('product_id', productId);
  formData.append('quantity', quantity);
  formData.append('csrf', CSRF_TOKEN);

  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });

    if (!response.ok) throw new Error('Network response was not ok');

    const data = await response.json();

    if (data.ok || data.success) {
      // ✅ Play sound
      playTingSound();
      
      // ✅ Show popup
      showCartPopup();
      
      // ✅ Shake cart icon
      shakeCartIcon();
      
      // ✅ Refresh cart count
      await refreshCartCount();
      
      console.log(`✅ Added ${quantity}x ${productName} to cart`);
    } else {
      alert(`❌ ${data.message || 'Không thể thêm vào giỏ hàng'}`);
    }
  } catch (error) {
    console.error('Error adding to cart:', error);
    alert('❌ Lỗi kết nối. Vui lòng thử lại');
  }
}

// ===== REVIEW MODAL FUNCTIONS =====
function openReviewModal() {
  if (!<?= isset($_SESSION['user']) ? 'true' : 'false' ?>) {
    window.location.href = 'login.php';
    return;
  }
  document.getElementById('reviewModal').classList.add('active');
}

function closeReviewModal() {
  document.getElementById('reviewModal').classList.remove('active');
}

function setRating(rating, e) {
  e.preventDefault();
  document.getElementById('ratingValue').value = rating;
  document.querySelectorAll('.rating-btn').forEach((btn, i) => {
    btn.classList.toggle('active', i + 1 <= rating);
  });
}

function updateCount(el, id) {
  document.getElementById(id).textContent = el.value.length;
}

function previewReviewImages(files) {
  const container = document.getElementById('previewImages');
  container.innerHTML = '';
  Array.from(files).slice(0, 5).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.className = 'preview-img';
      div.innerHTML = `<img src="${e.target.result}" alt="">`;
      container.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

function handleImageDrop(e) {
  e.preventDefault();
  e.currentTarget.style.background = 'white';
  previewReviewImages(e.dataTransfer.files);
  document.getElementById('reviewImageInput').files = e.dataTransfer.files;
}

// ===== EVENT LISTENERS =====
document.addEventListener('DOMContentLoaded', () => {
  // Add to cart buttons
  document.querySelectorAll('.add-to-cart-btn').forEach(button => {
    button.addEventListener('click', async function() {
      const productId = this.getAttribute('data-product-id');
      const productName = this.getAttribute('data-product-name');
      const qtyInput = this.parentElement.querySelector('.qty-input');
      const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

      this.disabled = true;
      const originalText = this.innerHTML;
      this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang thêm...';

      await addToCart(productId, quantity, productName);

      this.disabled = false;
      this.innerHTML = originalText;
    });
  });

  // Quantity input validation
  document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('change', function() {
      let value = parseInt(this.value) || 1;
      if (value < 1) value = 1;
      if (value > 99) value = 99;
      this.value = value;
    });

    input.addEventListener('keydown', function(e) {
      if (e.key === '-' || e.key === 'e' || e.key === 'E' || e.key === '+') {
        e.preventDefault();
      }
    });
  });

  // Initialize rating buttons
  document.querySelectorAll('.rating-btn').forEach((btn, i) => {
    if (i + 1 <= 5) btn.classList.add('active');
  });

  // Auto close modal after success
  <?php if ($review_success): ?>
    setTimeout(() => {
      closeReviewModal();
      location.reload();
    }, 2000);
  <?php endif; ?>

  console.log('✅ Products page loaded successfully');
});
</script>

</body>
</html>