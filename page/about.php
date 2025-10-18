<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giới thiệu - BuildPC.vn</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">

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
      background: linear-gradient(90deg, #007bff 0%, #00aaff 50%, #007bff 100%);
      box-shadow: 0 4px 16px rgba(0, 123, 255, 0.2);
      position: sticky;
      top: 0;
      z-index: 999;
      padding: 12px 40px;
    }

    .header-main {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 32px;
      max-width: 100%;
      margin: 0;
      padding: 0;
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
      filter: drop-shadow(0 2px 8px rgba(255, 255, 255, 0.3));
    }

    .logo span {
      font-size: 24px;
      font-weight: 800;
      color: white;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

    .header-actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .cart-link {
      position: relative;
      background: rgba(255, 255, 255, 0.2);
      color: white;
      padding: 10px 20px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .cart-link:hover {
      background: white;
      color: #007bff;
      border-color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
    }

    .cart-count {
      position: absolute;
      top: -10px;
      right: -10px;
      background: linear-gradient(135deg, #ffeb3b, #ff9800);
      color: #111;
      font-size: 11px;
      font-weight: 900;
      border-radius: 50%;
      min-width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 6px;
      box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
    }

    .login-btn {
      background: rgba(255, 255, 255, 0.2);
      color: white;
      padding: 10px 20px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .login-btn:hover {
      background: white;
      color: #007bff;
      border-color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
    }

    .user-menu {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .welcome {
      font-weight: 600;
      color: white;
      display: flex;
      align-items: center;
      gap: 6px;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .logout-btn {
      background: linear-gradient(135deg, #ff5252, #ff1744);
      color: white;
      padding: 10px 20px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s ease;
      border: none;
    }

    .logout-btn:hover {
      background: linear-gradient(135deg, #ff1744, #d50000);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 23, 68, 0.3);
    }

    /* ===== NAVIGATION ===== */
    .nav-wrapper {
      background: linear-gradient(90deg, #007bff 0%, #00aaff 50%, #007bff 100%);
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    }

    .nav {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 40px;
      display: flex;
      justify-content: center;
      gap: 8px;
    }

    .nav a {
      color: white;
      text-decoration: none;
      font-weight: 500;
      font-size: 14px;
      padding: 14px 20px;
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
      background: linear-gradient(90deg, #ffeb3b, #ff9800);
      transition: width 0.3s ease;
      border-radius: 3px 3px 0 0;
    }

    .nav a:hover,
    .nav a.active {
      color: #fff;
      text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
    }

    .nav a:hover::after,
    .nav a.active::after {
      width: 100%;
    }

    /* ==== PHẦN NỀN VÀ BỐ CỤC ==== */
    .about-section {
      background: linear-gradient(135deg, #a2c2e2, #2e8bfa);
      color: #fff;
      padding: 80px 20px;
      text-align: center;
      border-radius: 20px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
      margin-top: 60px;
      margin-bottom: 60px;
    }

    /* ==== TIÊU ĐỀ ==== */
    .about-section h1 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 20px;
      letter-spacing: 1px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* ==== ĐOẠN VĂN ==== */
    .about-section p {
      font-size: 1.1rem;
      line-height: 1.8;
      max-width: 800px;
      margin: 0 auto 30px auto;
    }

    /* ==== KHUNG GIỚI THIỆU NHÓM ==== */
    .team {
      margin-top: 50px;
      padding-bottom: 80px;
    }

    .team h2 {
      color: #2e8bfa;
      margin-bottom: 30px;
      font-size: 2rem;
      font-weight: 700;
    }

    .team-member {
      background: #fff;
      color: #333;
      border-radius: 15px;
      padding: 30px 20px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      height: 100%;
    }

    .team-member:hover {
      transform: translateY(-10px);
      box-shadow: 0 8px 25px rgba(46, 139, 250, 0.3);
    }

    .team-member h4 {
      color: #2e8bfa;
      font-weight: bold;
      font-size: 1.25rem;
      margin-bottom: 10px;
    }

    .team-member p {
      color: #666;
      margin: 0;
      font-size: 1rem;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      header {
        padding: 10px 16px;
      }

      .header-main {
        flex-wrap: wrap;
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
        gap: 0;
      }

      .nav a {
        white-space: nowrap;
        padding: 14px 16px;
        font-size: 13px;
      }

      .about-section {
        padding: 60px 20px;
        margin-top: 40px;
      }

      .about-section h1 {
        font-size: 2rem;
      }

      .about-section p {
        font-size: 1rem;
      }

      .team h2 {
        font-size: 1.75rem;
      }

      .team-member {
        margin-bottom: 20px;
      }
    }

    @media (max-width: 480px) {
      .logo span {
        font-size: 20px;
      }

      .cart-link span,
      .login-btn span {
        display: none;
      }

      .about-section h1 {
        font-size: 1.75rem;
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
  <div class="header-main">
    <div class="logo">
      <a href="../index.php">
        <span>🖥️ BuildPC.vn</span>
      </a>
    </div>

    <form class="search-container" method="GET" action="../index.php">
      <input type="text" name="q" placeholder="Tìm sản phẩm..." 
             value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
      <button type="submit">🔍</button>
    </form>

    <div class="header-actions">
      <a href="../cart.php" class="cart-link">
        <i class="fa-solid fa-cart-shopping"></i>
        <span>Giỏ hàng</span>
        <?php if ($cart_count > 0): ?>
          <span class="cart-count"><?= $cart_count ?></span>
        <?php endif; ?>
      </a>

      <?php if (!isset($_SESSION['user'])): ?>
        <a href="login.php" class="login-btn">
          <i class="fa-solid fa-user"></i>
          <span>Đăng nhập</span>
        </a>
      <?php else: ?>
        <div class="user-menu">
          <span class="welcome">
            <i class="fa-solid fa-circle-user"></i>
            <?= htmlspecialchars($_SESSION['user']['username']) ?>
          </span>
          <a href="logout.php" class="logout-btn">Đăng xuất</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="nav-wrapper">
    <nav class="nav">
      <a href="../index.php">
        <i class="fa-solid fa-house"></i> Trang chủ
      </a>
      <a href="products.php">
        <i class="fa-solid fa-box"></i> Sản phẩm
      </a>
      <a href="brands.php">
        <i class="fa-solid fa-tag"></i> Thương hiệu
      </a>
      <a href="builds.php">
        <i class="fa-solid fa-screwdriver-wrench"></i> Xây dựng cấu hình
      </a>
      <a href="about.php" class="active">
        <i class="fa-solid fa-circle-info"></i> Giới thiệu
      </a>
      <a href="contact.php">
        <i class="fa-solid fa-envelope"></i> Liên hệ
      </a>
    </nav>
  </div>
</header>

<div class="container about-section mt-5">
    <h1>Về Chúng Tôi</h1>
    <p>
        💻 <strong>BuildPC</strong> là nền tảng hỗ trợ người dùng dễ dàng lựa chọn, cấu hình và mua sắm linh kiện máy tính phù hợp nhất.  
        Với giao diện thân thiện, thông tin minh bạch và tính năng so sánh linh kiện thông minh. Chúng tôi giúp bạn tự tin tạo nên bộ PC mạnh mẽ, tối ưu hiệu năng và chi phí.
    </p>
    <p>
        Sứ mệnh của chúng tôi là mang đến cho người dùng trải nghiệm mua sắm linh kiện trực tuyến <strong>nhanh chóng - chính xác - chuyên nghiệp</strong>.  
        Mỗi sản phẩm được chọn lọc kỹ càng từ các thương hiệu uy tín hàng đầu như <em>ASUS, MSI, GIGABYTE, Intel, AMD</em>...
    </p>
</div>

<div class="container team text-center">
    <h2>👨‍💻 Nhóm Phát Triển</h2>
    <div class="row justify-content-center">
        <div class="col-md-3 team-member mx-3 mb-4">
            <h4>Xuân Minh</h4>
            <p>Backend Developer</p>
        </div>
        <div class="col-md-3 team-member mx-3 mb-4">
            <h4>Mạnh Hùng</h4>
            <p>Admin</p>
        </div>
        <div class="col-md-3 team-member mx-3 mb-4">
            <h4>Hoàng Nam</h4>
            <p>Database</p>
        </div>
    </div>
</div>

<!-- ===== FOOTER ===== -->
<footer>
  <p>© <?= date('Y') ?> BuildPC.vn — Máy tính & Linh kiện chính hãng</p>
</footer>

</body>
</html>