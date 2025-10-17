<?php
// === KHỞI ĐỘNG PHIÊN LÀM VIỆC (SESSION) ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === XỬ LÝ ĐƯỜNG DẪN TƯƠNG ĐỐI ===
$basePath = (strpos($_SERVER['PHP_SELF'], '/page/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false)
    ? '../'
    : './';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>BuildPC.vn - Cấu hình máy tính theo ý bạn</title>

  <!-- ===== LIÊN KẾT CSS & JS ===== -->
  <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css?v=3.1">
  <script src="<?php echo $basePath; ?>assets/js/main.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* ====== RESET & BODY ====== */
    body {
      margin: 0;
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
      padding: 15px 50px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.08);
      position: sticky;
      top: 0;
      z-index: 999;
    }

    /* ===== LOGO ===== */
    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .logo img {
      height: 45px;
    }

    .logo span {
      color: white;
      font-weight: 700;
      font-size: 22px;
      letter-spacing: 0.5px;
    }

    /* ===== NAVIGATION ===== */
    .nav {
      display: flex;
      align-items: center;
      gap: 25px;
    }

    .nav a {
      color: white;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      position: relative;
      padding-bottom: 4px;
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

    /* ===== NÚT ĐĂNG NHẬP / ĐĂNG XUẤT ===== */
    .login-btn, .logout-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 14px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 14px;
      text-decoration: none;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    /* Nút đăng nhập: trong suốt, chữ trắng */
    .login-btn {
      background: transparent;
      color: #ffffff;
      border: 2px solid rgba(255,255,255,0.4);
      text-decoration: none !important;
      position: relative;
    }

    /* Xóa hiệu ứng gạch chân khi hover */
    .login-btn::after {
      display: none !important;
    }

    .login-btn:hover {
      background: #ffffff;
      color: #007bff;
      border-color: #ffffff;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    /* Icon trong nút đăng nhập */
    .login-btn i {
      color: inherit;
    }

    /* Nút đăng xuất */
    .logout-btn {
      background: #ff4d4d;
      color: white;
      border: 2px solid #ff4d4d;
    }

    .logout-btn:hover {
      background: #e60000;
      border-color: #e60000;
    }

    /* ===== LỜI CHÀO ===== */
    .welcome {
      color: #fff;
      margin-right: 10px;
      font-size: 15px;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      header {
        flex-direction: column;
        padding: 15px;
      }

      .nav {
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
      }

      .nav a {
        margin: 8px;
      }
    }
  </style>
</head>

<body>
  <header>
    <div class="logo">
      <a href="<?php echo $basePath; ?>index.php">
        <img src="<?php echo $basePath; ?>assets/images/logo.png" alt="BuildPC.vn">
      </a>
      <span>BuildPC.vn</span>
    </div>

    <nav class="nav">
      <a href="<?php echo $basePath; ?>index.php">Trang chủ</a>
      <a href="<?php echo $basePath; ?>page/products.php">Sản phẩm</a>
      <a href="<?php echo $basePath; ?>page/brands.php">Thương hiệu</a>
      <a href="<?php echo $basePath; ?>page/builds.php">Xây dựng cấu hình</a>
      <a href="<?php echo $basePath; ?>page/about.php">Giới thiệu</a>
      <a href="<?php echo $basePath; ?>page/contact.php">Liên hệ</a>

      <?php if (!isset($_SESSION['user'])): ?>
        <a href="<?php echo $basePath; ?>page/login.php" class="login-btn">
          <i class="fa-solid fa-user"></i> Đăng nhập
        </a>
      <?php else: ?>
        <span class="welcome">👋 Xin chào, 
          <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong>
        </span>
        <a href="<?php echo $basePath; ?>page/logout.php" class="logout-btn">Đăng xuất</a>
      <?php endif; ?>
    </nav>
  </header>
</body>
</html>
