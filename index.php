<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db.php';
require_once 'functions.php';

// ✅ Tạo CSRF token
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// ✅ Lấy cart count từ database (không dùng session)
$user_id = getCurrentUserId();
$cart_count = $user_id ? getCartCount($user_id) : 0;

// ===== KIỂM TRA XEM USER CÓ PHẢI ADMIN KHÔNG =====
$is_admin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';

// Lấy sản phẩm theo category
$pc_products = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE c.slug = 'pc' OR c.category_id IN (SELECT category_id FROM categories WHERE slug = 'pc')
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$ai_products = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE c.slug = 'ai' OR c.category_id IN (SELECT category_id FROM categories WHERE slug = 'ai')
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$components_products = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE c.slug = 'components' OR c.category_id IN (SELECT category_id FROM categories WHERE slug = 'components')
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$laptop_products = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE c.slug = 'laptop' OR c.category_id IN (SELECT category_id FROM categories WHERE slug = 'laptop')
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$new_products = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    ORDER BY p.product_id DESC 
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

function renderProducts($products, $isLoggedIn) {
    foreach ($products as $p): 
        $image_path = getProductImagePath($p['main_image']);
    ?>
      <div class="product-card" data-aos="fade-up">
        <div class="product-image">
          <img src="<?= escape($image_path) ?>" 
               alt="<?= escape($p['name']) ?>" 
               loading="lazy"
               onerror="this.src='uploads/img/no-image.png'">
          <div class="product-overlay">
            <div class="quick-view">
              <i class="fa-solid fa-eye"></i> Xem nhanh
            </div>
          </div>
          <div class="product-badge">
            <span>Mới</span>
          </div>
        </div>
        <div class="product-content">
          <div class="product-category"><?= escape($p['category_name'] ?? 'Khác') ?></div>
          <h3 class="product-name" title="<?= escape($p['name']) ?>"><?= escape($p['name']) ?></h3>
          <div class="product-rating">
            <div class="stars">
              <i class="fa-solid fa-star"></i>
              <i class="fa-solid fa-star"></i>
              <i class="fa-solid fa-star"></i>
              <i class="fa-solid fa-star"></i>
              <i class="fa-solid fa-star-half-stroke"></i>
            </div>
            <span class="rating-count">(4.5)</span>
          </div>
          <p class="product-price">
            <span class="price-value"><?= formatPriceVND($p['price']) ?></span>
          </p>
          
          <?php if ($isLoggedIn): ?>
          <div class="quantity-wrapper">
            <div class="qty-control">
              <button type="button" class="qty-btn qty-minus" data-product-id="<?= $p['product_id'] ?>">−</button>
              <input type="number" value="1" min="1" max="99" class="qty-input" readonly data-product-id="<?= $p['product_id'] ?>">
              <button type="button" class="qty-btn qty-plus" data-product-id="<?= $p['product_id'] ?>">+</button>
            </div>
            <button type="button" class="add-to-cart-btn" data-product-id="<?= $p['product_id'] ?>" data-product-name="<?= escape($p['name']) ?>">
              <i class="fa-solid fa-cart-plus"></i>
              <span>Thêm</span>
            </button>
          </div>
          <?php else: ?>
          <a href="page/login.php" class="btn-login-product">
            <i class="fa-solid fa-user"></i> Đăng nhập để mua
          </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach;
}

function renderCategorySection($title, $icon, $products, $viewMoreLink, $isLoggedIn) {
    ?>
    <div class="section">
      <div class="section-header">
        <h2>
          <i class="<?= $icon ?>"></i>
          <?= $title ?>
        </h2>
        <p>Những sản phẩm tốt nhất dành cho bạn</p>
      </div>
      <div class="product-grid">
        <?php if (!empty($products)) {
            renderProducts($products, $isLoggedIn);
        } else {
            echo '<div class="empty-state" style="grid-column: 1/-1;">
              <i class="fa-solid fa-box-open"></i>
              <p>Chưa có sản phẩm trong danh mục này</p>
            </div>';
        } ?>
      </div>
      <div style="text-align: center; margin-top: 30px;">
        <a href="<?= $viewMoreLink ?>" class="btn-view-more">Xem thêm →</a>
      </div>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BuildPC - PC Gaming & Linh Kiện Chính Hãng</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
      color: #2d3436;
      min-height: 100vh;
      line-height: 1.6;
      overflow-x: hidden;
    }

    /* ===== HEADER ===== */
    header {
      background: #fff;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 999;
      border-bottom: 2px solid #e9ecef;
      animation: slideDown 0.6s ease-out;
    }

    @keyframes slideDown {
      from {
        transform: translateY(-30px);
        opacity: 0.5;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .header-top {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      color: white;
      padding: 10px 0;
      font-size: 14px;
      text-align: center;
      font-weight: 500;
      animation: fadeIn 0.8s ease-out 0.2s both;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .header-main {
      max-width: 1400px;
      margin: 0 auto;
      padding: 18px 32px;
      display: flex;
      align-items: center;
      gap: 32px;
      justify-content: space-between;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-shrink: 0;
    }

    .logo a {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .logo a:hover {
      transform: scale(1.05);
    }

    .logo span {
      font-size: 24px;
      font-weight: 800;
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      letter-spacing: -0.5px;
    }

    .search-container {
      flex: 1;
      max-width: 600px;
      position: relative;
      animation: slideInLeft 0.6s ease-out 0.1s both;
      margin: 0 auto;
    }

    @keyframes slideInLeft {
      from {
        transform: translateX(-15px);
        opacity: 0.3;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    .search-container input {
      width: 100%;
      padding: 14px 48px 14px 20px;
      border: 2px solid #e9ecef;
      border-radius: 12px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: #f8f9fa;
      font-weight: 500;
    }

    .search-container input::placeholder {
      color: #adb5bd;
    }

    .search-container input:focus {
      outline: none;
      border-color: #007bff;
      background: white;
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
      transform: translateY(-2px);
    }

    .search-container button {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      border: none;
      width: 40px;
      height: 40px;
      border-radius: 8px;
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
    }

    .search-container button:hover {
      transform: translateY(-50%) scale(1.08);
      box-shadow: 0 4px 16px rgba(0, 123, 255, 0.4);
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-shrink: 0;
      animation: slideInRight 0.6s ease-out 0.1s both;
      margin-left: auto;
    }

    @keyframes slideInRight {
      from {
        transform: translateX(15px);
        opacity: 0.3;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    .cart-link {
      position: relative;
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      color: white;
      padding: 12px 24px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
      overflow: hidden;
    }

    .cart-link::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.1);
      transition: left 0.3s ease;
      border-radius: 12px;
    }

    .cart-link:hover::before {
      left: 100%;
    }

    .cart-link:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 123, 255, 0.4);
    }

    .cart-link span {
      position: relative;
      z-index: 1;
    }

    .cart-count {
      position: absolute;
      top: -8px;
      right: -8px;
      background: linear-gradient(135deg, #ff6b6b, #ff5252);
      color: white;
      font-size: 11px;
      font-weight: 700;
      border-radius: 10px;
      min-width: 22px;
      height: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 6px;
      box-shadow: 0 4px 12px rgba(255, 107, 107, 0.4);
      animation: popIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    @keyframes popIn {
      0% {
        transform: scale(0.7);
        opacity: 0;
      }
      100% {
        transform: scale(1);
        opacity: 1;
      }
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

    /* ===== NÚT ADMIN ===== */
    .admin-btn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 12px 20px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
      position: relative;
      overflow: hidden;
      height: 44px;
    }

    .admin-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.15);
      transition: left 0.3s ease;
      border-radius: 12px;
    }

    .admin-btn:hover::before {
      left: 100%;
    }

    .admin-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
    }

    .admin-btn i {
      font-size: 16px;
    }

    .login-btn {
      background: white;
      color: #007bff;
      padding: 12px 24px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      border: 2px solid #007bff;
      position: relative;
      overflow: hidden;
      z-index: 1;
    }

    .login-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: #007bff;
      z-index: -1;
      transition: left 0.3s ease;
    }

    .login-btn:hover {
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 123, 255, 0.3);
    }

    .login-btn:hover::before {
      left: 0;
    }

    .btn-login-product {
      display: inline-block;
      width: 100%;
      background: linear-gradient(135deg, #28a745, #1e7e34);
      color: white;
      padding: 12px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 700;
      font-size: 14px;
      text-align: center;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .btn-login-product:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
    }

    .user-menu {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .welcome {
      font-weight: 600;
      color: #495057;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .welcome i {
      color: #007bff;
      font-size: 16px;
    }

    .logout-btn {
      background: linear-gradient(135deg, #ff6b6b, #ff5252);
      color: white;
      padding: 10px 20px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
    }

    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(255, 107, 107, 0.4);
    }

    /* ===== NAVIGATION ===== */
    .nav-wrapper {
      background: white;
      border-top: 1px solid #e9ecef;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
      animation: slideDown 0.6s ease-out 0.1s both;
      display: flex;
      align-items: center;
      justify-content: center;
      max-width: 100%;
      margin: 0 auto;
      padding: 0 32px;
    }

    .nav {
      display: flex;
      justify-content: center;
      gap: 8px;
      flex-wrap: wrap;
      padding: 0;
      margin: 0;
      list-style: none;
      width: auto;
    }

    .nav-item {
      position: relative;
    }

    .nav-link {
      color: #495057;
      text-decoration: none;
      font-weight: 500;
      font-size: 14px;
      padding: 16px 20px;
      transition: all 0.3s ease;
      position: relative;
      display: flex;
      align-items: center;
      gap: 6px;
      border-radius: 8px;
    }

    .nav-link::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 0;
      height: 3px;
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      transition: width 0.3s ease;
      border-radius: 3px 3px 0 0;
    }

    .nav-link:hover {
      color: #007bff;
      background: rgba(0, 123, 255, 0.08);
    }

    .nav-link:hover::after {
      width: 80%;
    }

    /* ===== DROPDOWN ===== */
    .dropdown-toggle {
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .dropdown-toggle i:last-child {
      font-size: 12px;
      transition: transform 0.3s ease;
    }

    .nav-item:hover .dropdown-toggle i:last-child {
      transform: rotate(180deg);
    }

    .dropdown-menu {
      position: absolute;
      top: 100%;
      left: 0;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
      min-width: 280px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
      z-index: 100;
      margin-top: 8px;
      border: 1px solid #e9ecef;
      list-style: none;
      padding: 8px 0;
    }

    .nav-item:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .dropdown-item {
      display: flex;
      align-items: center;
      gap: 12px;
      color: #495057;
      text-decoration: none;
      font-weight: 500;
      font-size: 14px;
      padding: 12px 20px;
      transition: all 0.2s ease;
      position: relative;
    }

    .dropdown-item i {
      font-size: 16px;
      color: #007bff;
      opacity: 0.8;
      transition: all 0.2s ease;
    }

    .dropdown-item:hover {
      color: #007bff;
      background: rgba(0, 123, 255, 0.08);
      padding-left: 24px;
    }

    .dropdown-item:hover i {
      opacity: 1;
      transform: scale(1.1);
    }

    .dropdown-item::before {
      content: "";
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 3px;
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      border-radius: 0 3px 3px 0;
      opacity: 0;
      transition: opacity 0.2s ease;
    }

    .dropdown-item:hover::before {
      opacity: 1;
    }

    /* ===== BANNER ===== */
    .banner {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      color: white;
      padding: 100px 32px;
      text-align: center;
      position: relative;
      overflow: hidden;
      animation: fadeIn 0.8s ease-out;
    }

    .banner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: 
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.05) 0%, transparent 50%);
      animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-8px); }
    }

    .banner-content {
      max-width: 800px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
      animation: slideUp 0.8s ease-out 0.2s both;
    }

    @keyframes slideUp {
      from {
        transform: translateY(15px);
        opacity: 0.3;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .banner h1 {
      font-size: 48px;
      font-weight: 800;
      margin-bottom: 16px;
      line-height: 1.2;
      text-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
      letter-spacing: -1px;
    }

    .banner p {
      font-size: 18px;
      opacity: 0.95;
      font-weight: 400;
      margin-bottom: 32px;
    }

    .banner-features {
      display: flex;
      justify-content: center;
      gap: 48px;
      margin-top: 32px;
      flex-wrap: wrap;
    }

    .feature-item {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 15px;
      font-weight: 500;
      animation: fadeIn 0.8s ease-out;
      transition: all 0.3s ease;
    }

    .feature-item:hover {
      transform: translateY(-2px);
      filter: brightness(1.05);
    }

    .feature-item i {
      font-size: 28px;
      opacity: 0.95;
      transition: all 0.3s ease;
    }

    .feature-item:hover i {
      transform: scale(1.08);
    }

    /* ===== SECTION ===== */
    .section {
      max-width: 1400px;
      margin: 80px auto;
      padding: 0 32px;
      animation: fadeIn 0.6s ease-out;
    }

    .section-header {
      text-align: center;
      margin-bottom: 60px;
      animation: slideUp 0.6s ease-out;
    }

    .section-header h2 {
      font-size: 32px;
      font-weight: 800;
      color: #007bff;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      letter-spacing: -0.5px;
    }

    .section-header h2 i {
      animation: bounce 1s ease-in-out infinite;
    }

    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-4px); }
    }

    .section-header p {
      color: #6c757d;
      font-size: 16px;
      font-weight: 500;
    }

    /* ===== PRODUCT GRID ===== */
    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 28px;
    }

    .product-card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      display: flex;
      flex-direction: column;
      height: 100%;
      border: 1px solid #f1f3f5;
      position: relative;
    }

    .product-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 30px rgba(0, 123, 255, 0.15);
      border-color: #007bff;
    }

    .product-image {
      position: relative;
      width: 100%;
      height: 260px;
      overflow: hidden;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .product-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }

    .product-card:hover .product-image img {
      transform: scale(1.08);
    }

    .product-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 123, 255, 0.92);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.4s ease;
      backdrop-filter: blur(2px);
    }

    .product-card:hover .product-overlay {
      opacity: 1;
    }

    .quick-view {
      color: white;
      font-weight: 600;
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 14px 28px;
      background: rgba(255, 255, 255, 0.15);
      border-radius: 10px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      animation: slideUp 0.4s ease-out;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .quick-view:hover {
      background: rgba(255, 255, 255, 0.25);
      transform: translateY(-3px);
    }

    .product-badge {
      position: absolute;
      top: 12px;
      right: 12px;
      z-index: 2;
    }

    .product-badge span {
      background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
      color: white;
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 700;
      box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
      display: inline-block;
      animation: badgePulse 2s ease-in-out infinite;
    }

    @keyframes badgePulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.02); }
    }

    .product-content {
      padding: 22px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .product-category {
      color: #007bff;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 10px;
      opacity: 0.8;
      transition: all 0.3s ease;
    }

    .product-card:hover .product-category {
      opacity: 1;
      letter-spacing: 1.5px;
    }

    .product-name {
      font-size: 15px;
      font-weight: 700;
      color: #2d3436;
      margin-bottom: 12px;
      line-height: 1.4;
      min-height: 42px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      transition: color 0.3s ease;
    }

    .product-card:hover .product-name {
      color: #007bff;
    }

    .product-rating {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 14px;
    }

    .stars {
      display: flex;
      gap: 2px;
      color: #ffa500;
      font-size: 14px;
    }

    .stars i {
      transition: all 0.3s ease;
    }

    .product-card:hover .stars i {
      filter: drop-shadow(0 0 3px rgba(255, 165, 0, 0.5));
    }

    .rating-count {
      color: #6c757d;
      font-size: 13px;
      font-weight: 500;
    }

    .product-price {
      margin-bottom: 18px;
      display: flex;
      align-items: baseline;
      gap: 12px;
    }

    .price-value {
      color: #007bff;
      font-weight: 800;
      font-size: 24px;
      letter-spacing: -0.5px;
    }

    .quantity-wrapper {
      display: flex;
      gap: 10px;
      margin-top: auto;
    }

    .qty-control {
      display: flex;
      align-items: center;
      border: 2px solid #e9ecef;
      border-radius: 10px;
      overflow: hidden;
      background: white;
      transition: all 0.3s ease;
    }

    .product-card:hover .qty-control {
      border-color: #007bff;
      box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
    }

    .qty-btn {
      background: #f8f9fa;
      border: none;
      width: 36px;
      height: 42px;
      cursor: pointer;
      font-size: 18px;
      font-weight: 700;
      color: #495057;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .qty-btn:hover {
      background: #007bff;
      color: white;
      transform: scale(1.1);
    }

    .qty-btn:active {
      transform: scale(0.95);
    }

    .qty-input {
      width: 52px;
      border: none;
      text-align: center;
      font-size: 15px;
      font-weight: 700;
      color: #2d3436;
      background: white;
      padding: 0 8px;
    }

    .add-to-cart-btn {
      flex: 1;
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      color: white;
      border: none;
      border-radius: 10px;
      padding: 0 20px;
      cursor: pointer;
      font-weight: 700;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
      position: relative;
      overflow: hidden;
    }

    .add-to-cart-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.2);
      transition: left 0.3s ease;
    }

    .add-to-cart-btn:hover::before {
      left: 100%;
    }

    .add-to-cart-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 123, 255, 0.4);
    }

    .add-to-cart-btn:active {
      transform: translateY(-1px);
    }

    .add-to-cart-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .btn-view-more {
      display: inline-block;
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      color: white;
      padding: 14px 40px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    }

    .btn-view-more:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 123, 255, 0.4);
    }

    /* ===== TOAST ===== */
    .toast {
      position: fixed;
      bottom: 32px;
      right: 32px;
      background: linear-gradient(135deg, #51cf66, #37b24d);
      color: white;
      padding: 16px 24px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(81, 207, 102, 0.3);
      display: none;
      font-weight: 600;
      font-size: 15px;
      animation: toastSlide 0.4s ease;
      z-index: 2000;
      max-width: 320px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    @keyframes toastSlide {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    .toast.error {
      background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
      box-shadow: 0 8px 24px rgba(255, 107, 107, 0.3);
    }

    /* ===== EMPTY STATE ===== */
    .empty-state {
      text-align: center;
      padding: 80px 20px;
      animation: fadeIn 0.6s ease-out;
    }

    .empty-state i {
      font-size: 80px;
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 24px;
      display: block;
      animation: float 3s ease-in-out infinite;
    }

    .empty-state p {
      color: #6c757d;
      font-size: 18px;
      font-weight: 500;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
      .header-main {
        padding: 14px 24px;
        gap: 20px;
      }

      .nav {
        padding: 0 24px;
      }

      .section {
        padding: 0 24px;
        margin: 60px auto;
      }

      .banner h1 {
        font-size: 40px;
      }

      .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
      }

      .dropdown-menu {
        min-width: 260px;
      }
    }

    @media (max-width: 768px) {
      .header-main {
        flex-wrap: wrap;
        padding: 12px 16px;
        gap: 12px;
      }

      .logo {
        order: 1;
      }

      .search-container {
        order: 3;
        width: 100%;
        max-width: 100%;
        margin: 0;
      }

      .header-actions {
        order: 2;
        margin-left: auto;
      }

      .nav {
        overflow-x: auto;
        justify-content: flex-start;
        padding: 0 16px;
        -webkit-overflow-scrolling: touch;
      }

      .nav-link {
        white-space: nowrap;
        padding: 14px 16px;
        font-size: 13px;
      }

      .banner {
        padding: 60px 20px;
      }

      .banner h1 {
        font-size: 32px;
      }

      .banner p {
        font-size: 16px;
      }

      .banner-features {
        gap: 24px;
      }

      .section {
        margin: 50px auto;
        padding: 0 16px;
      }

      .section-header h2 {
        font-size: 26px;
      }

      .section-header p {
        font-size: 14px;
      }

      .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 16px;
      }

      .product-image {
        height: 180px;
      }

      .product-content {
        padding: 14px;
      }

      .product-name {
        font-size: 14px;
        min-height: 38px;
      }

      .price-value {
        font-size: 18px;
      }

      .qty-btn {
        width: 32px;
        height: 36px;
        font-size: 16px;
      }

      .qty-input {
        width: 44px;
      }

      .toast {
        bottom: 20px;
        right: 20px;
        left: 20px;
        max-width: none;
      }

      .cart-link span {
        display: none;
      }

      .login-btn span {
        display: none;
      }

      .admin-btn span {
        display: none;
      }

      .cart-link, .login-btn, .admin-btn {
        padding: 10px 14px;
        font-size: 12px;
      }
    }

    /* ===== FOOTER ===== */
    footer {
      background: linear-gradient(90deg, #007bff 0%, #00aaff 50%, #007bff 100%);
      color: white;
      text-align: center;
      padding: 32px 20px;
      margin-top: 80px;
      font-size: 14px;
      box-shadow: 0 -4px 12px rgba(0, 107, 255, 0.1);
      font-weight: 500;
      animation: slideUp 0.8s ease-out;
    }

    footer p {
      margin: 0;
    }
  </style>
</head>

<body>

<!-- 🔊 Audio for notification sound -->
<audio id="tingSound" preload="auto">
  <source src="uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<header>
  <div class="header-top">
    <i class="fa-solid fa-truck-fast"></i> Miễn phí vận chuyển cho đơn hàng từ 500.000₫
  </div>

  <div class="header-main">
    <div class="logo">
      <a href="index.php">
        <span>🖥️ BuildPC</span>
      </a>
    </div>

    <form class="search-container" method="GET" action="index.php">
      <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." 
             value="<?= escape($_GET['q'] ?? '') ?>">
      <button type="submit">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </form>

    <div class="header-actions">
      <a href="page/cart.php" class="cart-link">
        <i class="fa-solid fa-cart-shopping"></i>
        <span>Giỏ hàng</span>
        <?php if ($cart_count > 0): ?>
          <span class="cart-count"><?= $cart_count ?></span>
        <?php endif; ?>
      </a>

      <?php if ($is_admin): ?>
        <a href="page/admin.php" class="admin-btn" title="Vào trang quản lý admin">
          <i class="fa-solid fa-screwdriver-wrench"></i>
          <span>Admin</span>
        </a>
      <?php endif; ?>

      <?php if (!isset($_SESSION['user'])): ?>
        <a href="page/login.php" class="login-btn">
          <i class="fa-solid fa-user"></i>
          <span>Đăng nhập</span>
        </a>
      <?php else: ?>
        <div class="user-menu">
          <span class="welcome">
            <i class="fa-solid fa-circle-user"></i>
            <?= escape($_SESSION['user']['username']) ?>
          </span>
          <a href="page/logout.php" class="logout-btn">Đăng xuất</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="nav-wrapper">
    <ul class="nav">
      <li class="nav-item">
        <a href="index.php" class="nav-link">
          <i class="fa-solid fa-house"></i> Trang chủ
        </a>
      </li>

      <li class="nav-item">
        <a href="page/products.php" class="nav-link dropdown-toggle">
          <i class="fa-solid fa-box"></i> Sản phẩm
          <i class="fa-solid fa-chevron-down"></i>
        </a>
        
        <ul class="dropdown-menu">
          <li><a href="page/products.php?category=pc" class="dropdown-item"><i class="fa-solid fa-desktop"></i> PC</a></li>
          <li><a href="page/products.php?category=ai" class="dropdown-item"><i class="fa-solid fa-microchip"></i> PC AI</a></li>
          <li><a href="page/products.php?category=components" class="dropdown-item"><i class="fa-solid fa-puzzle-piece"></i> Linh kiện PC</a></li>
          <li><a href="page/products.php?category=monitors" class="dropdown-item"><i class="fa-solid fa-tv"></i> Màn hình</a></li>
          <li><a href="page/products.php?category=laptop" class="dropdown-item"><i class="fa-solid fa-laptop"></i> Laptop</a></li>
          <li><a href="page/products.php?category=peripherals" class="dropdown-item"><i class="fa-solid fa-keyboard"></i> Thiết bị văn phòng</a></li>
          <li><a href="page/products.php?category=gear" class="dropdown-item"><i class="fa-solid fa-headphones"></i> Phím chuột ghế gear</a></li>
        </ul>
      </li>

      <li class="nav-item"><a href="page/brands.php" class="nav-link"><i class="fa-solid fa-tag"></i> Thương hiệu</a></li>
      <li class="nav-item"><a href="page/builds.php" class="nav-link"><i class="fa-solid fa-screwdriver-wrench"></i> Xây dựng cấu hình</a></li>
      <li class="nav-item"><a href="page/about.php" class="nav-link"><i class="fa-solid fa-circle-info"></i> Giới thiệu</a></li>
      <li class="nav-item"><a href="page/contact.php" class="nav-link"><i class="fa-solid fa-envelope"></i> Liên hệ</a></li>
    </ul>
  </div>
</header>

<div class="banner">
  <div class="banner-content">
    <h1>BuildPC - Xây Dựng PC Mơ Ước</h1>
    <p>Linh kiện chính hãng • Giá tốt nhất • Hỗ trợ tư vấn 24/7</p>
    <div class="banner-features">
      <div class="feature-item">
        <i class="fa-solid fa-shield-halved"></i>
        <span>Chính hãng 100%</span>
      </div>
      <div class="feature-item">
        <i class="fa-solid fa-truck-fast"></i>
        <span>Giao hàng nhanh</span>
      </div>
      <div class="feature-item">
        <i class="fa-solid fa-headset"></i>
        <span>Hỗ trợ 24/7</span>
      </div>
      <div class="feature-item">
        <i class="fa-solid fa-medal"></i>
        <span>Bảo hành tốt nhất</span>
      </div>
    </div>
  </div>
</div>

<?php 
$isLoggedIn = isLoggedIn();
renderCategorySection(
  'Máy tính bộ PC',
  'fa-solid fa-desktop',
  $pc_products,
  'page/products.php?category=pc',
  $isLoggedIn
);

renderCategorySection(
  'PC AI cao cấp',
  'fa-solid fa-microchip',
  $ai_products,
  'page/products.php?category=ai',
  $isLoggedIn
);

renderCategorySection(
  'Linh kiện PC chính hãng',
  'fa-solid fa-puzzle-piece',
  $components_products,
  'page/products.php?category=components',
  $isLoggedIn
);

renderCategorySection(
  'Laptop gaming',
  'fa-solid fa-laptop',
  $laptop_products,
  'page/products.php?category=laptop',
  $isLoggedIn
);

renderCategorySection(
  'Sản phẩm mới nhất',
  'fa-solid fa-sparkles',
  $new_products,
  'page/products.php',
  $isLoggedIn
);
?>

<div id="toast" class="toast"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
// ===== AOS Animation =====
AOS.init({
  duration: 800,
  easing: 'ease-out-cubic',
  once: true,
  offset: 50
});

// ===== CONSTANTS =====
const CSRF = <?= json_encode($csrf) ?>;
const API_URL = 'api/cart_api.php';

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

// ===== UTILITY FUNCTIONS =====
function playTingSound() {
  const sound = document.getElementById("tingSound");
  if (sound) {
    sound.play().catch(()=>{});
  }
}

function shakeCartIcon() {
  const cartIcon = document.querySelector(".fa-cart-shopping") || document.querySelector(".cart-link i");
  if (cartIcon) {
    cartIcon.classList.add("cart-shake");
    setTimeout(() => cartIcon.classList.remove("cart-shake"), 700);
  }
}

function showToast(text, ok = true) {
  const t = document.getElementById('toast');
  t.classList.toggle('error', !ok);
  t.textContent = text;
  t.style.display = 'block';
  setTimeout(() => t.style.display = 'none', 3000);
}

function updateCartBadge(count) {
  let badge = document.querySelector('.cart-count');
  const cartLink = document.querySelector('.cart-link');
  
  if (count > 0) {
    if (badge) {
      badge.textContent = count;
      badge.style.animation = 'none';
      setTimeout(() => { badge.style.animation = ''; }, 10);
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
    
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    
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
    showToast('❌ Số lượng không hợp lệ (1-99)', false);
    return;
  }

  const formData = new FormData();
  formData.append('action', 'add');
  formData.append('product_id', productId);
  formData.append('quantity', quantity);
  formData.append('csrf', CSRF);

  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });

    if (!response.ok) {
      throw new Error('Network response was not ok');
    }

    const data = await response.json();

    if (data.ok || data.success) {
      // ✅ Play sound
      playTingSound();
      
      // ✅ Show toast
      showToast(`✓ Đã thêm ${quantity} ${productName} vào giỏ hàng`, true);
      
      // ✅ Shake cart icon
      shakeCartIcon();
      
      // ✅ Refresh cart count
      await refreshCartCount();
      
      console.log(`✅ Added ${quantity}x ${productName} to cart`);
    } else {
      showToast(`❌ ${data.message || 'Không thể thêm vào giỏ hàng'}`, false);
    }
  } catch (error) {
    console.error('Error adding to cart:', error);
    showToast('❌ Lỗi kết nối. Vui lòng thử lại', false);
  }
}

// ===== EVENT LISTENERS =====
document.addEventListener('DOMContentLoaded', () => {
  // Quantity controls
  document.querySelectorAll('.qty-minus').forEach(btn => {
    btn.addEventListener('click', () => {
      const productId = btn.getAttribute('data-product-id');
      const input = document.querySelector(`.qty-input[data-product-id="${productId}"]`);
      const currentValue = parseInt(input.value) || 1;
      if (currentValue > 1) {
        input.value = currentValue - 1;
      }
    });
  });

  document.querySelectorAll('.qty-plus').forEach(btn => {
    btn.addEventListener('click', () => {
      const productId = btn.getAttribute('data-product-id');
      const input = document.querySelector(`.qty-input[data-product-id="${productId}"]`);
      const currentValue = parseInt(input.value) || 1;
      const maxValue = parseInt(input.getAttribute('max')) || 99;
      if (currentValue < maxValue) {
        input.value = currentValue + 1;
      }
    });
  });

  // Add to cart buttons
  document.querySelectorAll('.add-to-cart-btn').forEach(button => {
    button.addEventListener('click', async function() {
      const productId = this.getAttribute('data-product-id');
      const productName = this.getAttribute('data-product-name');
      const qtyInput = document.querySelector(`.qty-input[data-product-id="${productId}"]`);
      const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

      // Disable button during request
      this.disabled = true;
      const originalHTML = this.innerHTML;
      this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

      await addToCart(productId, quantity, productName);

      // Re-enable button
      this.disabled = false;
      this.innerHTML = originalHTML;
    });
  });

  console.log('✅ Index page loaded successfully');
});
</script>

<footer>
  <p>© <?= date('Y') ?> BuildPC.vn — Máy tính & Linh kiện chính hãng</p>
</footer>

</body>
</html>