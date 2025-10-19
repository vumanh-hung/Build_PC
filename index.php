<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$cart_count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pid => $it) {
        if (is_array($it) && isset($it['quantity'])) {
            $cart_count += (int)$it['quantity'];
        } else {
            $cart_count += (int)$it;
        }
    }
}

$search_query = "";
$search_results = [];
if (!empty($_GET['q'])) {
    $search_query = trim($_GET['q']);
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, b.name AS brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.name LIKE :keyword
        ORDER BY p.product_id DESC
    ");
    $stmt->execute([':keyword' => "%$search_query%"]);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$filter_active = false;
$where = [];
$params = [];

if (!empty($_GET['category_id'])) {
    $where[] = "p.category_id = :category_id";
    $params[':category_id'] = $_GET['category_id'];
    $filter_active = true;
}
if (!empty($_GET['brand_id'])) {
    $where[] = "p.brand_id = :brand_id";
    $params[':brand_id'] = $_GET['brand_id'];
    $filter_active = true;
}
if (!empty($_GET['min_price'])) {
    $where[] = "p.price >= :min_price";
    $params[':min_price'] = $_GET['min_price'];
    $filter_active = true;
}
if (!empty($_GET['max_price'])) {
    $where[] = "p.price <= :max_price";
    $params[':max_price'] = $_GET['max_price'];
    $filter_active = true;
}

if ($filter_active) {
    $sql = "
        SELECT p.*, c.name AS category_name, b.name AS brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        " . (!empty($where) ? "WHERE " . implode(" AND ", $where) : "") . "
        ORDER BY p.product_id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filtered_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$new_products = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    ORDER BY p.product_id DESC 
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

function renderProducts($products) {
    foreach ($products as $p): ?>
      <div class="product-card">
        <div class="product-image">
          <img src="uploads/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
          <div class="product-overlay">
            <div class="quick-view">
              <i class="fa-solid fa-eye"></i> Xem nhanh
            </div>
          </div>
          <div class="product-badge">Mới</div>
        </div>
        <div class="product-content">
          <div class="product-category"><?php echo htmlspecialchars($p['category_name'] ?? 'Khác'); ?></div>
          <h3 class="product-name" title="<?php echo htmlspecialchars($p['name']); ?>"><?php echo htmlspecialchars($p['name']); ?></h3>
          <div class="product-rating">
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star-half-stroke"></i>
            <span class="rating-count">(4.5)</span>
          </div>
          <p class="product-price">
            <span class="price-value"><?php echo number_format($p['price']); ?>₫</span>
          </p>
          <form class="add-to-cart-form" data-product-id="<?php echo $p['product_id']; ?>" onsubmit="return false;">
            <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
            <div class="quantity-wrapper">
              <div class="qty-control">
                <button type="button" class="qty-btn qty-minus">−</button>
                <input type="number" name="quantity" value="1" min="1" max="99" class="qty-input" readonly>
                <button type="button" class="qty-btn qty-plus">+</button>
              </div>
              <button type="button" class="add-to-cart-btn">
                <i class="fa-solid fa-cart-plus"></i>
                <span>Thêm</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    <?php endforeach;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BuildPC.vn - PC Gaming & Linh Kiện Chính Hãng</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
      background: #f8f9fa;
      color: #2d3436;
      min-height: 100vh;
      line-height: 1.6;
    }

    /* ===== HEADER ===== */
    header {
      background: #fff;
      box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
      position: sticky;
      top: 0;
      z-index: 999;
      border-bottom: 1px solid #e9ecef;
    }

    .header-top {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      color: white;
      padding: 8px 0;
      font-size: 13px;
      text-align: center;
    }

    .header-main {
      max-width: 1400px;
      margin: 0 auto;
      padding: 16px 32px;
      display: flex;
      align-items: center;
      gap: 32px;
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
      transition: transform 0.3s ease;
    }

    .logo a:hover {
      transform: translateY(-2px);
    }

    .logo img {
      height: 48px;
      filter: drop-shadow(0 2px 8px rgba(102, 126, 234, 0.3));
    }

    .logo span {
      font-size: 24px;
      font-weight: 800;
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .search-container {
      flex: 1;
      max-width: 600px;
      position: relative;
    }

    .search-container input {
      width: 100%;
      padding: 14px 48px 14px 20px;
      border: 2px solid #e9ecef;
      border-radius: 12px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: #f8f9fa;
    }

    .search-container input:focus {
      outline: none;
      border-color: #007bff;
      background: white;
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
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
    }

    .search-container button:hover {
      transform: translateY(-50%) scale(1.05);
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-shrink: 0;
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
    }

    .cart-link:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
    }

    .cart-count {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #ff6b6b;
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
      box-shadow: 0 2px 8px rgba(255, 107, 107, 0.4);
      animation: cartPulse 0.5s ease;
    }

    @keyframes cartPulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.2); }
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
    }

    .login-btn:hover {
      background: #007bff;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
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
      gap: 6px;
    }

    .logout-btn {
      background: #ff6b6b;
      color: white;
      padding: 10px 20px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .logout-btn:hover {
      background: #ff5252;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
    }

    /* ===== NAVIGATION ===== */
    .nav-wrapper {
      background: white;
      border-top: 1px solid #e9ecef;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.04);
    }

    .nav {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 32px;
      display: flex;
      justify-content: center;
      gap: 8px;
    }

    .nav a {
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
    }

    .nav a::after {
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

    .nav a:hover {
      color: #007bff;
    }

    .nav a:hover::after {
      width: 100%;
    }

    /* ===== BANNER ===== */
    .banner {
      background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
      color: white;
      padding: 80px 32px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .banner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
      opacity: 0.3;
    }

    .banner-content {
      max-width: 800px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    .banner h1 {
      font-size: 48px;
      font-weight: 800;
      margin-bottom: 16px;
      line-height: 1.2;
      text-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
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
    }

    .feature-item i {
      font-size: 24px;
      opacity: 0.9;
    }

    /* ===== SECTION ===== */
    .section {
      max-width: 1400px;
      margin: 60px auto;
      padding: 0 32px;
    }

    .section-header {
      text-align: center;
      margin-bottom: 48px;
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
    }

    .section-header p {
      color: #6c757d;
      font-size: 16px;
    }

    /* ===== PRODUCT GRID ===== */
    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 24px;
    }

    .product-card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      display: flex;
      flex-direction: column;
      height: 100%;
      border: 1px solid #f1f3f5;
    }

    .product-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 32px rgba(0, 123, 255, 0.15);
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
      transform: scale(1.1);
    }

    .product-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 123, 255, 0.95);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s ease;
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
      padding: 12px 24px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 8px;
      backdrop-filter: blur(10px);
    }

    .product-badge {
      position: absolute;
      top: 12px;
      right: 12px;
      background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
      color: white;
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 700;
      box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
      z-index: 1;
    }

    .product-content {
      padding: 20px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .product-category {
      color: #007bff;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
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
    }

    .product-rating {
      display: flex;
      align-items: center;
      gap: 4px;
      margin-bottom: 12px;
      color: #ffa500;
      font-size: 14px;
    }

    .rating-count {
      color: #6c757d;
      font-size: 13px;
      margin-left: 4px;
    }

    .product-price {
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .price-value {
      color: #007bff;
      font-weight: 800;
      font-size: 22px;
    }

    .quantity-wrapper {
      display: flex;
      gap: 8px;
      margin-top: auto;
    }

    .qty-control {
      display: flex;
      align-items: center;
      border: 2px solid #e9ecef;
      border-radius: 10px;
      overflow: hidden;
      background: white;
    }

    .qty-btn {
      background: #f8f9fa;
      border: none;
      width: 32px;
      height: 40px;
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
    }

    .qty-input {
      width: 48px;
      border: none;
      text-align: center;
      font-size: 15px;
      font-weight: 700;
      color: #2d3436;
      background: white;
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
    }

    .add-to-cart-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
    }

    .add-to-cart-btn:active {
      transform: translateY(0);
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
      grid-column: 1/-1;
    }

    .empty-state i {
      font-size: 64px;
      color: #dee2e6;
      margin-bottom: 24px;
      display: block;
    }

    .empty-state p {
      color: #6c757d;
      font-size: 16px;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
      .header-main {
        padding: 16px 24px;
        gap: 20px;
      }

      .nav {
        padding: 0 24px;
      }

      .section {
        padding: 0 24px;
      }

      .banner h1 {
        font-size: 40px;
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

      .nav a {
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
        margin: 40px auto;
        padding: 0 16px;
      }

      .section-header h2 {
        font-size: 26px;
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

      .toast {
        bottom: 20px;
        right: 20px;
        left: 20px;
        max-width: none;
      }
    }

    @media (max-width: 480px) {
      .logo span {
        font-size: 20px;
      }

      .cart-link span {
        display: none;
      }

      .login-btn span {
        display: none;
      }

      .banner h1 {
        font-size: 26px;
      }

      .banner-features {
        flex-direction: column;
        gap: 16px;
      }

      .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
      }

      .add-to-cart-btn span {
        display: none;
      }
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

  </style>
</head>

<body>

<header>
  <div class="header-top">
    <i class="fa-solid fa-truck-fast"></i> Miễn phí vận chuyển cho đơn hàng từ 500.000₫
  </div>

  <div class="header-main">
    <div class="logo">
      <a href="../index.php">
        <span>🖥️ BuildPC.vn</span>
      </a>
    </div>

    <form class="search-container" method="GET" action="index.php">
      <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." 
             value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
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

      <?php if (!isset($_SESSION['user'])): ?>
        <a href="page/login.php" class="login-btn">
          <i class="fa-solid fa-user"></i>
          <span>Đăng nhập</span>
        </a>
      <?php else: ?>
        <div class="user-menu">
          <span class="welcome">
            <i class="fa-solid fa-circle-user"></i>
            <?= htmlspecialchars($_SESSION['user']['username']) ?>
          </span>
          <a href="page/logout.php" class="logout-btn">Đăng xuất</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="nav-wrapper">
    <nav class="nav">
      <a href="index.php">
        <i class="fa-solid fa-house"></i> Trang chủ
      </a>
      <a href="page/products.php">
        <i class="fa-solid fa-box"></i> Sản phẩm
      </a>
      <a href="page/brands.php">
        <i class="fa-solid fa-tag"></i> Thương hiệu
      </a>
      <a href="page/builds.php">
        <i class="fa-solid fa-screwdriver-wrench"></i> Xây dựng cấu hình
      </a>
      <a href="page/about.php">
        <i class="fa-solid fa-circle-info"></i> Giới thiệu
      </a>
      <a href="page/contact.php">
        <i class="fa-solid fa-envelope"></i> Liên hệ
      </a>
    </nav>
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

<?php if (!empty($search_query)): ?>
  <div class="section">
    <div class="section-header">
      <h2>
        <i class="fa-solid fa-magnifying-glass"></i>
        Kết Quả Tìm Kiếm
      </h2>
      <p>Tìm thấy <?= count($search_results) ?> sản phẩm cho "<strong><?= htmlspecialchars($search_query) ?></strong>"</p>
    </div>
    <div class="product-grid">
      <?php 
      if (count($search_results) > 0) {
          renderProducts($search_results);
      } else {
          echo '<div class="empty-state">
            <i class="fa-solid fa-box-open"></i>
            <p>Không tìm thấy sản phẩm phù hợp với "<strong>' . htmlspecialchars($search_query) . '</strong>"</p>
          </div>';
      }
      ?>
    </div>
  </div>
<?php elseif ($filter_active): ?>
  <div class="section">
    <div class="section-header">
      <h2>
        <i class="fa-solid fa-filter"></i>
        Kết Quả Lọc
      </h2>
      <p>Tìm thấy <?= count($filtered_products) ?> sản phẩm phù hợp</p>
    </div>
    <div class="product-grid">
      <?php 
      if (count($filtered_products) > 0) {
          renderProducts($filtered_products);
      } else {
          echo '<div class="empty-state">
            <i class="fa-solid fa-filter-circle-xmark"></i>
            <p>Không tìm thấy sản phẩm phù hợp với bộ lọc này</p>
          </div>';
      }
      ?>
    </div>
  </div>
<?php else: ?>
  <div class="section">
    <div class="section-header">
      <h2>
        <i class="fa-solid fa-sparkles"></i>
        Sản Phẩm Mới Nhất
      </h2>
      <p>Cập nhật liên tục các sản phẩm mới nhất từ các thương hiệu hàng đầu</p>
    </div>
    <div class="product-grid"><?php renderProducts($new_products); ?></div>
  </div>
<?php endif; ?>

<div id="toast" class="toast"></div>

<script>
const CSRF = "<?php echo htmlspecialchars($csrf); ?>";

function showToast(text, ok = true) {
    const t = document.getElementById('toast');
    t.classList.toggle('error', !ok);
    t.textContent = text;
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 3000);
}

function setCartBadge(n) {
    const badge = document.querySelector('.cart-count');
    if (badge) {
        badge.textContent = Math.max(0, n);
        badge.style.animation = 'none';
        setTimeout(() => {
            badge.style.animation = '';
        }, 10);
    }
}

async function addToCart(productId, quantity = 1) {
    const form = new URLSearchParams();
    form.append('action', 'add');
    form.append('product_id', productId);
    form.append('quantity', quantity);
    form.append('csrf', CSRF);

    try {
        const resp = await fetch('api/cart_api.php', {
            method: 'POST',
            body: form
        });
        const data = await resp.json();
        if (data.ok) {
            showToast('✓ Đã thêm ' + quantity + ' sản phẩm vào giỏ hàng', true);
            let badge = document.querySelector('.cart-count');
            if (!badge) {
                const link = document.querySelector('.cart-link');
                const span = document.createElement('span');
                span.className = 'cart-count';
                span.textContent = quantity;
                link.appendChild(span);
            } else {
                const current = parseInt(badge.textContent) || 0;
                setCartBadge(current + parseInt(quantity));
            }
        } else {
            showToast(data.message || '✗ Thêm thất bại', false);
        }
    } catch (err) {
        console.error(err);
        showToast('✗ Lỗi kết nối', false);
    }
}

// Quantity controls
document.querySelectorAll('.add-to-cart-form').forEach(form => {
    const qtyInput = form.querySelector('.qty-input');
    const minusBtn = form.querySelector('.qty-minus');
    const plusBtn = form.querySelector('.qty-plus');
    const addBtn = form.querySelector('.add-to-cart-btn');

    minusBtn.addEventListener('click', () => {
        const currentValue = parseInt(qtyInput.value) || 1;
        if (currentValue > 1) {
            qtyInput.value = currentValue - 1;
        }
    });

    plusBtn.addEventListener('click', () => {
        const currentValue = parseInt(qtyInput.value) || 1;
        const maxValue = parseInt(qtyInput.max) || 99;
        if (currentValue < maxValue) {
            qtyInput.value = currentValue + 1;
        }
    });

    addBtn.addEventListener('click', () => {
        const pid = form.getAttribute('data-product-id');
        const qty = parseInt(qtyInput.value) || 1;
        if (qty < 1) {
            showToast('✗ Số lượng không hợp lệ', false);
            return;
        }
        addToCart(pid, qty);
    });
});
</script>

<!-- ===== FOOTER ===== -->
<footer>
  <p>© <?= date('Y') ?> BuildPC.vn — Máy tính & Linh kiện chính hãng</p>
</footer>

</body>
</html>