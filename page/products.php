<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ✅ Tạo CSRF token
$csrf = generateCSRFToken();

// ✅ Lấy danh sách danh mục & thương hiệu
$categories = getCategories();
$brands = getAllBrands();

// ✅ Xử lý tìm kiếm / lọc
$keyword = trim($_GET['keyword'] ?? '');
$category_id = $_GET['category_id'] ?? '';
$brand_id = $_GET['brand_id'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// ✅ Build query với prepared statements
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

$pdo = getPDO();
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Lấy số lượng giỏ hàng
$user_id = getCurrentUserId();
$cart_count = $user_id ? getCartCount($user_id) : 0;

// ✅ Function render products
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

html {
  scroll-behavior: smooth;
}

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

/* ===== MAIN CONTAINER ===== */
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

/* ===== SEARCH & FILTER ===== */
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
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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

.search-bar input::placeholder {
  color: #999;
}

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
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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

/* 🪄 Popup "đã thêm vào giỏ hàng" */
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
}

.cart-popup.show {
  opacity: 1;
  transform: translateY(0);
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
  .search-bar {
    grid-template-columns: repeat(2, 1fr);
  }

  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 18px;
  }
}

@media (max-width: 768px) {
  header {
    padding: 10px 16px;
    flex-wrap: wrap;
  }

  .header-left {
    gap: 20px;
    width: 100%;
  }

  .nav {
    gap: 16px;
    font-size: 12px;
  }

  .search-bar {
    grid-template-columns: 1fr;
    gap: 10px;
    padding: 16px;
  }

  .btn-search {
    grid-column: 1 / -1;
  }

  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px;
  }
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

    <?php if (isLoggedIn()): ?>
      <span class="welcome">👋 <?= escape($_SESSION['user']['username'] ?? $_SESSION['user']['full_name']) ?></span>
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
    <input type="text" 
           name="keyword" 
           placeholder="Tìm sản phẩm..." 
           value="<?= escape($keyword) ?>">
    
    <select name="category_id">
      <option value="">-- Danh mục --</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= $c['category_id'] ?>" 
                <?= ($category_id == $c['category_id']) ? 'selected' : '' ?>>
          <?= escape($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="brand_id">
      <option value="">-- Thương hiệu --</option>
      <?php foreach ($brands as $b): ?>
        <option value="<?= $b['brand_id'] ?>" 
                <?= ($brand_id == $b['brand_id']) ? 'selected' : '' ?>>
          <?= escape($b['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <input type="number" 
           name="min_price" 
           placeholder="Giá từ..." 
           value="<?= escape($min_price) ?>">
    
    <input type="number" 
           name="max_price" 
           placeholder="Giá đến..." 
           value="<?= escape($max_price) ?>">

    <button type="submit" class="btn-search">
      <i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm
    </button>
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

<!-- 🪄 Cart Popup -->
<div id="cart-popup" class="cart-popup">🛒 Đã thêm vào giỏ hàng!</div>

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
  const cartIcon = document.querySelector(".fa-cart-shopping") || document.querySelector(".cart-link i");
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

    if (!response.ok) {
      throw new Error('Network response was not ok');
    }

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

// ===== EVENT LISTENERS =====
document.addEventListener('DOMContentLoaded', () => {
  // Add to cart buttons
  document.querySelectorAll('.add-to-cart-btn').forEach(button => {
    button.addEventListener('click', async function() {
      const productId = this.getAttribute('data-product-id');
      const productName = this.getAttribute('data-product-name');
      const qtyInput = this.parentElement.querySelector('.qty-input');
      const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

      // Disable button during request
      this.disabled = true;
      const originalText = this.innerHTML;
      this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang thêm...';

      await addToCart(productId, quantity, productName);

      // Re-enable button
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

    // Prevent negative and non-numeric input
    input.addEventListener('keydown', function(e) {
      if (e.key === '-' || e.key === 'e' || e.key === 'E' || e.key === '+') {
        e.preventDefault();
      }
    });
  });

  console.log('✅ Products page loaded successfully');
});
</script>

</body>
</html>