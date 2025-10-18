<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';

// ===== CSRF TOKEN =====
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// Lấy danh sách thương hiệu
$stmt = $pdo->query("SELECT * FROM brands ORDER BY name ASC");
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nếu có chọn 1 thương hiệu cụ thể
$products = [];
$brand_title = null;
if (isset($_GET['brand_id'])) {
    $brand_id = intval($_GET['brand_id']);

    $stmt = $pdo->prepare("
        SELECT p.product_id, p.name, p.price, p.main_image, b.name AS brand, c.name AS category
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.brand_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$brand_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $brand_name = $pdo->prepare("SELECT name FROM brands WHERE brand_id = ?");
    $brand_name->execute([$brand_id]);
    $brand_title = $brand_name->fetchColumn();
}

// Lấy số lượng giỏ hàng
$cart_count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pid => $it) {
        $cart_count += is_array($it) && isset($it['quantity']) ? (int)$it['quantity'] : (int)$it;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thương hiệu - BuildPC.vn</title>
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

.logo a {
  text-decoration: none;
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

.header-center {
  display: flex;
  align-items: center;
  flex: 1;
  max-width: 400px;
}

.search-container {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 25px;
  overflow: hidden;
  width: 100%;
  height: 38px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
  display: flex;
  align-items: center;
  transition: all 0.3s ease;
}

.search-container:focus-within {
  box-shadow: 0 8px 25px rgba(0, 107, 255, 0.25);
  transform: translateY(-2px);
}

.search-container input {
  flex: 1;
  border: none;
  outline: none;
  padding: 0 16px;
  font-size: 13px;
  color: #333;
  height: 38px;
  background: transparent;
}

.search-container input::placeholder {
  color: #999;
}

.search-container button {
  background: none;
  border: none;
  width: 38px;
  height: 38px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  color: #007bff;
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.search-container button:hover {
  color: #ff9800;
  transform: scale(1.15);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-shrink: 0;
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

/* ===== BANNER ===== */
.banner {
  background: linear-gradient(135deg, #1a73e8 0%, #1e88e5 50%, #1565c0 100%);
  color: white;
  text-align: center;
  padding: 60px 20px;
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
  font-size: 42px;
  margin-bottom: 12px;
  font-weight: 900;
  text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  position: relative;
  z-index: 1;
  letter-spacing: -0.5px;
}

.banner p {
  font-size: 15px;
  opacity: 0.95;
  position: relative;
  z-index: 1;
  font-weight: 300;
  letter-spacing: 0.5px;
}

/* ===== SECTION ===== */
.section {
  max-width: 1400px;
  margin: 60px auto;
  padding: 0 20px;
}

.section-title {
  font-size: 28px;
  color: #1a73e8;
  text-align: center;
  margin-bottom: 40px;
  font-weight: 800;
  letter-spacing: -0.5px;
}

/* ===== BRAND GRID ===== */
.brand-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 24px;
  animation: fadeIn 0.6s ease-out;
  margin-bottom: 60px;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.brand-card {
  background: white;
  border-radius: 16px;
  padding: 24px 16px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 220px;
}

.brand-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 28px rgba(26, 115, 232, 0.25);
}

.brand-card a {
  text-decoration: none;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.brand-logo {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.brand-logo img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.brand-card:hover .brand-logo {
  transform: scale(1.08);
}

.brand-name {
  font-size: 15px;
  font-weight: 700;
  color: #1a73e8;
  transition: color 0.3s ease;
  letter-spacing: 0.2px;
}

.brand-card:hover .brand-name {
  color: #ff9800;
}

/* ===== PRODUCT SECTION ===== */
.product-section {
  margin-top: 60px;
}

.product-section h2 {
  font-size: 28px;
  color: #1a73e8;
  font-weight: 800;
  margin-bottom: 30px;
  letter-spacing: -0.5px;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 24px;
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

.product-image {
  position: relative;
  width: 100%;
  height: 180px;
  overflow: hidden;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
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

.product-info {
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

.product-category {
  font-size: 11px;
  color: #999;
  margin-bottom: 8px;
  font-weight: 500;
}

.product-price {
  color: #1a73e8;
  font-weight: 800;
  font-size: 16px;
  margin-bottom: 10px;
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
}

.qty-input:focus {
  outline: none;
  border-color: #1a73e8;
  background: #f0f7ff;
}

.product-btn {
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

.product-btn:hover {
  background: linear-gradient(135deg, #1565c0, #0d47a1);
  transform: translateY(-2px);
  box-shadow: 0 5px 12px rgba(26, 115, 232, 0.3);
}

.product-btn:active {
  transform: translateY(0);
}

.no-products {
  text-align: center;
  padding: 50px 20px;
  color: #999;
  font-size: 15px;
}

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
  z-index: 2000;
  animation: slideIn 0.4s ease-out;
}

@keyframes slideIn {
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
  header {
    padding: 10px 24px;
    gap: 16px;
  }

  .nav {
    gap: 20px;
  }

  .banner h1 {
    font-size: 32px;
  }

  .brand-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }

  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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

  .logo span {
    font-size: 16px;
  }

  .nav {
    gap: 16px;
    font-size: 12px;
  }

  .header-center {
    width: 100%;
    max-width: 100%;
    order: 3;
    margin-top: 10px;
  }

  .header-right {
    width: 100%;
    justify-content: flex-start;
    order: 4;
    margin-top: 10px;
  }

  .banner h1 {
    font-size: 24px;
  }

  .section {
    margin: 40px auto;
  }

  .section-title {
    font-size: 22px;
  }

  .brand-grid {
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 16px;
  }

  .brand-card {
    min-height: 180px;
    padding: 16px 12px;
  }

  .brand-logo {
    width: 100px;
    height: 100px;
  }

  .brand-name {
    font-size: 13px;
  }

  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
  }

  .product-image {
    height: 140px;
  }

  .product-name {
    font-size: 12px;
  }

  .product-price {
    font-size: 14px;
  }
}

@media (max-width: 480px) {
  header {
    padding: 8px 12px;
  }

  .logo span {
    font-size: 14px;
  }

  .nav {
    gap: 12px;
    font-size: 11px;
  }

  .search-container {
    height: 34px;
  }

  .search-container input {
    font-size: 12px;
    padding: 0 12px;
  }

  .search-container button {
    width: 34px;
    height: 34px;
  }

  .cart-link,
  .login-btn,
  .logout-btn {
    font-size: 11px;
    padding: 6px 12px;
  }

  .banner h1 {
    font-size: 20px;
  }

  .banner p {
    font-size: 13px;
  }

  .brand-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .brand-card {
    min-height: 160px;
    padding: 12px;
  }

  .brand-logo {
    width: 90px;
    height: 90px;
  }

  .brand-name {
    font-size: 12px;
  }

  .product-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .product-image {
    height: 120px;
  }

  .product-name {
    font-size: 11px;
  }

  .product-price {
    font-size: 13px;
  }

  .product-actions {
    gap: 6px;
  }

  .qty-input {
    width: 50px;
    padding: 6px;
    font-size: 11px;
  }

  .product-btn {
    font-size: 11px;
    padding: 6px 8px;
  }
}
</style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header>
  <div class="header-left">
    <div class="logo">
      <a href="../index.php">
        <span>🖥️ BuildPC.vn</span>
      </a>
    </div>

    <nav class="nav">
      <a href="../index.php">Trang chủ</a>
      <a href="products.php">Sản phẩm</a>
      <a href="brands.php" class="active">Thương hiệu</a>
      <a href="builds.php">Xây dựng cấu hình</a>
      <a href="about.php">Giới thiệu</a>
      <a href="contact.php">Liên hệ</a>
    </nav>
  </div>

  <div class="header-center">
    <form class="search-container" method="GET" action="products.php">
      <input type="text" name="keyword" placeholder="Tìm sản phẩm...">
      <button type="submit">
        <i class="fa-solid fa-search"></i>
      </button>
    </form>
  </div>

  <div class="header-right">
    <a href="../cart.php" class="cart-link">
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
  <h1>Thương Hiệu Nổi Bật</h1>
  <p>Các thương hiệu công nghệ hàng đầu thế giới</p>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="section">
  <!-- BRAND LIST -->
  <div class="brand-grid">
    <?php foreach ($brands as $b): ?>
    <div class="brand-card">
      <a href="?brand_id=<?= $b['brand_id'] ?>">
        <div class="brand-logo">
          <img src="../uploads/<?= htmlspecialchars($b['slug'] ?: 'default_brand.png') ?>" 
               alt="<?= htmlspecialchars($b['name']) ?>">
        </div>
        <div class="brand-name"><?= htmlspecialchars($b['name']) ?></div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- PRODUCT LIST (if brand selected) -->
  <?php if (!empty($products)): ?>
  <div class="product-section">
    <h2><?= htmlspecialchars($brand_title) ?></h2>

    <div class="product-grid">
      <?php foreach ($products as $p): ?>
      <div class="product-card">
        <div class="product-image">
          <img src="../uploads/<?= htmlspecialchars($p['main_image'] ?: 'default.jpg') ?>" 
               alt="<?= htmlspecialchars($p['name']) ?>">
        </div>
        <div class="product-info">
          <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
          <p class="product-category"><?= htmlspecialchars($p['category'] ?? 'Không rõ') ?></p>
          <p class="product-price"><?= number_format($p['price'], 0, ',', '.') ?> ₫</p>
          
          <div class="product-actions">
            <input type="number" class="qty-input" value="1" min="1" max="99" data-product-id="<?= $p['product_id'] ?>">
            <button type="button" class="product-btn add-to-cart-btn" data-product-id="<?= $p['product_id'] ?>">
              <i class="fa-solid fa-cart-plus"></i> Thêm
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ===== TOAST ===== -->
<div id="toast" class="toast"></div>

<!-- ===== FOOTER ===== -->
<footer>
  <p>© <?= date('Y') ?> BuildPC.vn — Máy tính & Linh kiện chính hãng</p>
</footer>

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
    let badge = document.querySelector('.cart-count');
    if (!badge) {
        const link = document.querySelector('.cart-link');
        const span = document.createElement('span');
        span.className = 'cart-count';
        span.textContent = n;
        link.appendChild(span);
    } else {
        badge.textContent = Math.max(0, n);
    }
}

async function addToCart(productId, quantity = 1) {
    const form = new URLSearchParams();
    form.append('action', 'add');
    form.append('product_id', productId);
    form.append('quantity', quantity);
    form.append('csrf', CSRF);

    try {
        const resp = await fetch('../cart.php', {
            method: 'POST',
            body: form
        });
        const data = await resp.json();
        if (data.ok) {
            showToast('✓ Thêm thành công ' + quantity + ' sản phẩm', true);
            const badge = document.querySelector('.cart-count');
            const current = badge ? parseInt(badge.textContent) || 0 : 0;
            setCartBadge(current + parseInt(quantity));
        } else {
            showToast(data.message || '✗ Thêm thất bại', false);
        }
    } catch (err) {
        console.error(err);
        showToast('✗ Lỗi kết nối', false);
    }
}

document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const productId = btn.getAttribute('data-product-id');
        const qtyInput = btn.parentElement.querySelector('.qty-input');
        const qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
        if (qty < 1) {
            showToast('✗ Số lượng không hợp lệ', false);
            return;
        }
        addToCart(productId, qty);
    });
});
</script>

