<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', 'localhost');
    session_start();
}

$basePath = (strpos($_SERVER['PHP_SELF'], '/page/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false)
    ? '../'
    : './';

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BuildPC.vn - Cấu hình máy tính theo ý bạn</title>

  <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css?v=3.1">
  <script src="<?php echo $basePath; ?>assets/js/main.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: "Segoe UI", Tahoma, sans-serif;
      background: linear-gradient(180deg, #e6f3ff, #ffffff);
      color: #333;
    }

    /* ===== HEADER ===== */
    header {
      background: linear-gradient(90deg, #007bff, #00aaff);
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 40px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 999;
      gap: 20px;
    }

    /* ===== LOGO ===== */
    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }

    .logo a {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }

    .logo img {
      height: 45px;
      transition: transform 0.3s ease;
    }

    .logo img:hover {
      transform: scale(1.05);
    }

    .logo span {
      color: white;
      font-weight: 700;
      font-size: 22px;
      letter-spacing: 0.5px;
      white-space: nowrap;
    }

    /* ===== NAVIGATION ===== */
    .nav {
      display: flex;
      align-items: center;
      gap: 20px;
      flex: 1;
      justify-content: center;
    }

    .nav a {
      color: white;
      text-decoration: none;
      font-weight: 500;
      font-size: 14px;
      transition: all 0.3s ease;
      position: relative;
      padding-bottom: 4px;
      white-space: nowrap;
    }

    .nav a::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      width: 0%;
      height: 2px;
      background: white;
      transition: width 0.3s;
    }

    .nav a:hover::after {
      width: 100%;
    }
    

    /* ===== SEARCH CONTAINER ===== */
    .search-wrapper {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-shrink: 0;
    }

    .search-container {
      background: white;
      border-radius: 50px;
      overflow: hidden;
      width: 350px;
      height: 40px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      display: flex;
      align-items: center;
      transition: all 0.3s ease;
    }

    .search-container:focus-within {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      width: 380px;
    }

    .search-container input {
      flex: 1;
      border: none;
      outline: none;
      padding: 0 14px;
      font-size: 13px;
      color: #333;
      height: 40px;
    }

    .search-container input::placeholder {
      color: #999;
      text-align: center;
    }

    .search-container button {
      background: none;
      border: none;
      width: 40px;
      height: 40px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      color: #007bff;
      transition: 0.3s;
      flex-shrink: 0;
    }

    .search-container button:hover {
      color: #0056b3;
      transform: scale(1.1);
    }

    /* ===== GIỎ HÀNG ===== */
    .cart-link {
      position: relative;
      background: white;
      color: #007bff;
      padding: 6px 14px;
      border-radius: 20px;
      text-decoration: none;
      font-weight: 600;
      font-size: 13px;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .cart-link:hover {
      background: #f1f7ff;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
      transform: translateY(-2px);
    }

    .cart-count {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #ffb300;
      color: #111;
      font-size: 11px;
      font-weight: bold;
      border-radius: 50%;
      padding: 2px 6px;
      line-height: 1;
      min-width: 24px;
      text-align: center;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }

    /* ===== NÚT ĐĂNG NHẬP / ĐĂNG XUẤT ===== */
    .auth-section {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }

    .login-btn, .logout-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 13px;
      text-decoration: none;
      transition: all 0.3s ease;
      cursor: pointer;
      white-space: nowrap;
    }

    .login-btn {
      background: transparent;
      color: #ffffff;
      border: 2px solid rgba(255, 255, 255, 0.4);
      position: relative;
    }

    .login-btn::after {
      display: none !important;
    }

    .login-btn:hover {
      background: #ffffff;
      color: #007bff;
      border-color: #ffffff;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
      transform: translateY(-2px);
    }

    .login-btn i {
      color: inherit;
    }

    .logout-btn {
      background: #ff4d4d;
      color: white;
      border: none;
    }

    .logout-btn:hover {
      background: #e60000;
      box-shadow: 0 4px 10px rgba(230, 0, 0, 0.2);
      transform: translateY(-2px);
    }

    .welcome {
      color: #fff;
      font-size: 13px;
      font-weight: 500;
    }

    .search-container {
  flex: 1;
  max-width: 600px;
  position: relative;
}

.search-container input {
  width: 100%;
  padding: 14px 48px 14px 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50px;
  font-size: 15px;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.9);
}

.search-container input:focus {
  outline: none;
  border-color: white;
  background: white;
  box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
}

.search-container button {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  color: #007bff;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.search-container button:hover {
  background: rgba(0, 123, 255, 0.1);
  transform: translateY(-50%) scale(1.1);
}


    /* ===== RESPONSIVE ===== */
    @media (max-width: 1200px) {
      .nav {
        gap: 12px;
      }

      .nav a {
        font-size: 13px;
      }

      .search-container {
        width: 300px;
      }

      .search-container:focus-within {
        width: 330px;
      }
    }

    @media (max-width: 992px) {
      header {
        flex-wrap: wrap;
        padding: 12px 20px;
        gap: 12px;
      }

      .logo {
        order: 1;
      }

      .nav {
        order: 3;
        width: 100%;
        justify-content: center;
        margin-top: 8px;
        gap: 10px;
        flex-wrap: wrap;
      }

      .nav a {
        font-size: 12px;
        padding: 4px 8px;
      }

      .search-wrapper {
        order: 2;
      }

      .search-container {
        width: 280px;
        height: 38px;
      }

      .search-container:focus-within {
        width: 300px;
      }
    }

    @media (max-width: 768px) {
      header {
        flex-direction: column;
        padding: 10px;
      }

      .logo {
        justify-content: center;
        width: 100%;
      }

      .nav {
        width: 100%;
        order: 2;
        margin-top: 8px;
      }

      .search-wrapper {
        width: 100%;
        order: 3;
        margin-top: 8px;
      }

      .search-container {
        width: 100%;
      }

      .search-container:focus-within {
        width: 100%;
      }

      .auth-section {
        width: 100%;
        order: 4;
        margin-top: 8px;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
      }

      .nav a {
        font-size: 12px;
      }
    }

    @media (max-width: 480px) {
      header {
        padding: 8px;
      }

      .logo span {
        font-size: 18px;
      }

      .logo img {
        height: 35px;
      }

      .nav {
        gap: 6px;
      }

      .nav a {
        font-size: 11px;
        padding: 2px 6px;
      }

      .search-container {
        font-size: 12px;
        height: 36px;
      }

      .search-container input {
        padding: 0 10px;
        font-size: 12px;
      }

      .cart-link {
        font-size: 12px;
        padding: 5px 10px;
      }

      .login-btn,
      .logout-btn {
        font-size: 12px;
        padding: 5px 10px;
      }
    }
  </style>
</head>

<body>
  <header>
    <div class="logo">
      <a href="../index.php">
        <span>🖥️ BuildPC.vn</span>
      </a>
    </div>

    <nav class="nav">
      <a href="<?php echo $basePath; ?>index.php">Trang chủ</a>
      <a href="<?php echo $basePath; ?>page/products.php">Sản phẩm</a>
      <a href="<?php echo $basePath; ?>page/brands.php">Thương hiệu</a>
      <a href="<?php echo $basePath; ?>page/builds.php">Xây dựng cấu hình</a>
      <a href="<?php echo $basePath; ?>page/about.php">Giới thiệu</a>
      <a href="<?php echo $basePath; ?>page/contact.php">Liên hệ</a>
    </nav>

    <div class="search-wrapper">
      <form class="search-container" method="GET" action="../index.php">
      <input type="text" name="q" placeholder="Tìm sản phẩm..." 
             value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
      <button type="submit">🔍</button>
    </form>

      <a href="<?php echo $basePath; ?>cart.php" class="cart-link">
        <i class="fa-solid fa-cart-shopping"></i> Giỏ hàng
        <?php if ($cartCount > 0): ?>
          <span class="cart-count"><?= $cartCount ?></span>
        <?php endif; ?>
      </a>
    </div>

    <div class="auth-section">
      <?php if (!isset($_SESSION['user'])): ?>
        <a href="<?php echo $basePath; ?>page/login.php" class="login-btn">
          <i class="fa-solid fa-user"></i> Đăng nhập
        </a>
      <?php else: ?>
        <span class="welcome">👋 
          <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong>
        </span>
        <a href="<?php echo $basePath; ?>page/logout.php" class="logout-btn">Đăng xuất</a>
      <?php endif; ?>
    </div>
  </header>
</body>
</html>