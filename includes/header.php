<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>BuildPC.vn - Cấu hình máy tính theo ý bạn</title>
  <link rel="stylesheet" href="./assets/css/style.css?v=2.0">
  <script src="./assets/js/main.js" defer></script>
</head>
<body>
  <!-- ===== HEADER ===== -->
  <header class="site-header">
    <div class="header-container">
      <div class="logo">
        <a href="index.php">
          <img src="assets/images/logo.png" alt="BuildPC.vn">
        </a>
      </div>
      <div class="search-bar">
        <form action="search.php" method="get">
          <input type="text" name="q" placeholder="Nhập từ khóa tìm kiếm...">
          <button type="submit"><i class="fa fa-search"></i></button>
        </form>
      </div>
      <div class="user-links">
        <a href="page/login.php"><i class="fa fa-user"></i> Đăng nhập</a>
        <a href="page/cart.php"><i class="fa fa-shopping-cart"></i> Giỏ hàng</a>
      </div>
    </div>
    <?php include __DIR__ . '/navbar.php'; ?>
  </header>

  <?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="header">
  <div class="logo">
    <a href="../index.php">BuildPC.vn</a>
  </div>

  <nav class="nav">
    <a href="../index.php">Trang chủ</a>
    <a href="../pages/products.php">Sản phẩm</a>
    <a href="../pages/brands.php">Thương hiệu</a>
    <a href="../pages/builder.php">Xây dựng cấu hình</a>
    <a href="../pages/about.php">Giới thiệu</a>
    <a href="../pages/contact.php">Liên hệ</a>

    <?php if (!isset($_SESSION['user'])): ?>
      <a href="../pages/login.php" class="login-btn">Đăng nhập</a>
    <?php else: ?>
      <span class="welcome">Xin chào, <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong></span>
      <a href="../pages/logout.php" class="logout-btn">Đăng xuất</a>
    <?php endif; ?>
  </nav>
</header>

<style>
.header {
  background: linear-gradient(90deg, #007bff, #00aaff);
  padding: 15px 50px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.header .logo a {
  color: white;
  text-decoration: none;
  font-weight: bold;
  font-size: 22px;
}
.nav a {
  color: white;
  text-decoration: none;
  margin-left: 20px;
  transition: opacity 0.2s;
}
.nav a:hover {
  opacity: 0.8;
}
.login-btn {
  background: #fff;
  color: #007bff;
  padding: 6px 14px;
  border-radius: 6px;
  font-weight: 600;
}
.login-btn:hover {
  background: #f0f0f0;
}
.logout-btn {
  background: #ff4d4d;
  padding: 6px 14px;
  border-radius: 6px;
  color: white;
  font-weight: 600;
}
.logout-btn:hover {
  background: #ff1a1a;
}
</style>
