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

// ===== CHECK BUILD MODE =====
$build_mode = $_GET['mode'] ?? '';
$build_id = $_GET['build_id'] ?? '';
$item_id = $_GET['item_id'] ?? '';
$is_build_mode = !empty($build_mode) && !empty($build_id);

// Debug log
error_log("🔧 Build Mode Check:");
error_log("   build_mode: " . $build_mode);
error_log("   build_id: " . $build_id);
error_log("   item_id: " . $item_id);
error_log("   is_build_mode: " . ($is_build_mode ? 'true' : 'false'));

// ===== DATABASE INIT =====
$pdo = getPDO();

// ===== LẤY DANH SÁCH DANH MỤC & THƯƠNG HIỆU =====
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$brands = $pdo->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// ===== XỬ LÝ TÌM KIẾM / LỌC =====
$keyword = trim($_GET['keyword'] ?? '');
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$brand_id = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : 0;

$where = [];
$params = [];

if ($keyword !== '') {
    $where[] = "p.name LIKE :keyword";
    $params[':keyword'] = "%$keyword%";
}
if ($category_id > 0) {
    $where[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}
if ($brand_id > 0) {
    $where[] = "p.brand_id = :brand_id";
    $params[':brand_id'] = $brand_id;
}
if ($min_price > 0) {
    $where[] = "p.price >= :min_price";
    $params[':min_price'] = $min_price;
}
if ($max_price > 0) {
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

// ===== LẤY SỐ LƯỢNG GIỎ HÀNG =====
$user_id = getCurrentUserId();
$cart_count = $user_id ? getCartCount($user_id) : 0;

// ===== LẤY REVIEWS DATA (chỉ khi không ở build mode) =====
$review_stats = null;
$recent_reviews = [];

if (!$is_build_mode) {
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
}

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
                
                // Xử lý upload ảnh
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
function renderProducts($products, $csrf, $isLoggedIn, $isBuildMode) {
    global $pdo, $build_id, $item_id, $build_mode;
    
    error_log("🎨 renderProducts: isBuildMode=" . ($isBuildMode ? 'true' : 'false'));
    error_log("   build_mode=" . $build_mode . ", build_id=" . $build_id . ", item_id=" . $item_id);
    
    foreach ($products as $p): 
        $image_path = getProductImage($p);
        
        // Check promotion
        $promotion = null;
        $has_promotion = false;
        $original_price = $p['price'];
        $discount_percent = 0;
        $sale_price = $original_price;
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM promotions 
                WHERE product_id = :product_id 
                AND is_active = 1 
                AND start_date <= NOW() 
                AND end_date >= NOW()
                ORDER BY discount_percent DESC
                LIMIT 1
            ");
            $stmt->execute([':product_id' => $p['product_id']]);
            $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($promotion) {
                $has_promotion = true;
                $discount_percent = $promotion['discount_percent'];
                $sale_price = $original_price * (1 - $discount_percent / 100);
            }
        } catch (PDOException $e) {
            // Skip promotions if table doesn't exist
        }
        
        $sold_count = $p['sold_count'] ?? 0;
    ?>
        <div class="product-card" data-product-id="<?= $p['product_id'] ?>">
            <!-- ✅ Image - ALWAYS clickable -->
            <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="image-link" target="_blank">
                <div class="image-wrapper">
                    <?php if ($has_promotion): ?>
                    <div class="discount-badge">-<?= $discount_percent ?>%</div>
                    <?php endif; ?>
                    
                    <?php if ($p['is_hot'] ?? false): ?>
                    <div class="hot-badge">HOT</div>
                    <?php endif; ?>
                    
                    <img src="../<?= escape($image_path) ?>" 
                         alt="<?= escape($p['name']) ?>"
                         onerror="this.src='../uploads/img/no-image.png'">
                    
                    <?php if ($isBuildMode): ?>
                    <div class="image-overlay">
                        <i class="fa fa-eye"></i>
                        <span>Xem chi tiết</span>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            
            <!-- Product Info -->
            <div class="product-info-section">
                <h3 class="product-name"><?= escape($p['name']) ?></h3>
                
                <p class="brand-cat">
                    <?= escape($p['brand_name'] ?? 'Thương hiệu') ?> • 
                    <?= escape($p['category_name'] ?? 'Danh mục') ?>
                </p>
                
                <?php if ($has_promotion): ?>
                <div class="price-section">
                    <div class="price-row">
                        <span class="original-price"><?= formatPriceVND($original_price) ?></span>
                        <span class="discount-percent">-<?= $discount_percent ?>%</span>
                    </div>
                    <div class="sale-price"><?= formatPriceVND($sale_price) ?></div>
                </div>
                <?php else: ?>
                <div class="price-section">
                    <div class="current-price"><?= formatPriceVND($original_price) ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($sold_count > 0): ?>
                <div class="sold-count">
                    <i class="fa-solid fa-box"></i> Đã bán: <?= number_format($sold_count) ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- ✅ Actions -->
            <?php if ($isBuildMode): ?>
            <div class="build-mode-actions">
                <button type="button" 
                        class="select-product-btn" 
                        data-product-id="<?= $p['product_id'] ?>"
                        data-build-id="<?= $build_id ?>"
                        data-item-id="<?= $item_id ?>"
                        data-mode="<?= $build_mode ?>"
                        data-product-name="<?= escape($p['name']) ?>">
                    <?php if ($build_mode === 'replace'): ?>
                        <i class="fa fa-exchange-alt"></i> <span>Thay thế</span>
                    <?php else: ?>
                        <i class="fa fa-plus-circle"></i> <span>Thêm vào Build</span>
                    <?php endif; ?>
                </button>
            </div>
            <?php else: ?>
            <div class="normal-mode-actions">
                <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="btn-view-detail">
                    <i class="fa fa-eye"></i> Xem chi tiết
                </a>
            </div>
            <?php endif; ?>
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

// ===== INCLUDE HTML TEMPLATE =====
include 'products_template.php';
?>