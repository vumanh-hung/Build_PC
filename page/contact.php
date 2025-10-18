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

// Lấy số lượng giỏ hàng
$cart_count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pid => $it) {
        $cart_count += is_array($it) && isset($it['quantity']) ? (int)$it['quantity'] : (int)$it;
    }
}

// Xử lý form liên hệ
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $csrf) {
        $error_message = 'Token không hợp lệ. Vui lòng thử lại.';
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $message = trim($_POST['message']);

        if (empty($name) || empty($email) || empty($message)) {
            $error_message = 'Vui lòng điền đầy đủ thông tin.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Email không hợp lệ.';
        } else {
            // Lưu vào database hoặc gửi email (tùy chọn)
            // Ở đây chỉ hiển thị thông báo thành công
            $success_message = 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Liên hệ - BuildPC.vn</title>
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

/* ==== TỔNG THỂ ==== */
.contact-section {
  background: linear-gradient(135deg, #a2c2e2, #2e8bfa);
  color: #fff;
  padding: 60px 20px;
  text-align: center;
  border-radius: 20px;
  margin: 40px auto;
  max-width: 1200px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

/* ==== TIÊU ĐỀ ==== */
.contact-section h1 {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 20px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.contact-section p {
  font-size: 1.1rem;
  line-height: 1.6;
}

/* ==== FORM LIÊN HỆ ==== */
.contact-form {
  background: #fff;
  color: #333;
  border-radius: 15px;
  padding: 30px;
  max-width: 700px;
  margin: 40px auto;
  box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}

.contact-form .form-group {
  margin-bottom: 20px;
  text-align: left;
}

.contact-form label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
  color: #2e8bfa;
}

.contact-form input,
.contact-form textarea {
  width: 100%;
  padding: 12px;
  border: 2px solid #cce0ff;
  border-radius: 10px;
  font-size: 1rem;
  transition: 0.3s;
  font-family: inherit;
}

.contact-form input:focus,
.contact-form textarea:focus {
  border-color: #2e8bfa;
  outline: none;
  box-shadow: 0 0 8px rgba(46, 139, 250, 0.3);
}

.contact-form textarea {
  resize: vertical;
  min-height: 120px;
}

.contact-form button {
  background: linear-gradient(135deg, #2e8bfa, #1c6fe2);
  color: #fff;
  border: none;
  padding: 12px 30px;
  font-size: 1.1rem;
  border-radius: 10px;
  cursor: pointer;
  transition: 0.3s;
  font-weight: 600;
  width: 100%;
  box-shadow: 0 4px 12px rgba(46, 139, 250, 0.3);
}

.contact-form button:hover {
  background: linear-gradient(135deg, #1c6fe2, #1a5fc9);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(46, 139, 250, 0.4);
}

/* ==== THÔNG TIN LIÊN HỆ ==== */
.contact-info {
  margin: 50px auto;
  max-width: 700px;
  background: white;
  padding: 30px;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.contact-info h4 {
  color: #2e8bfa;
  font-weight: bold;
  font-size: 1.2rem;
  margin-top: 20px;
  margin-bottom: 10px;
}

.contact-info h4:first-child {
  margin-top: 0;
}

.contact-info p {
  margin-bottom: 8px;
  font-size: 1rem;
  color: #555;
  line-height: 1.6;
}

/* ==== BẢN ĐỒ ==== */
.map-container {
  margin: 40px auto;
  max-width: 1200px;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.map-container iframe {
  display: block;
  width: 100%;
}

/* ==== ALERT MESSAGES ==== */
.alert {
  padding: 15px 20px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-weight: 500;
}

.alert-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
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
    font-size: 28px;
  }

  .banner p {
    font-size: 14px;
  }

  .contact-section {
    margin: 20px;
    padding: 40px 20px;
  }

  .contact-section h1 {
    font-size: 2rem;
  }

  .contact-section p {
    font-size: 1rem;
  }

  .contact-form {
    padding: 20px;
    margin: 20px;
  }

  .contact-info {
    margin: 30px 20px;
    padding: 20px;
  }

  .map-container {
    margin: 30px 20px;
  }
}

@media (max-width: 480px) {
  .logo span {
    font-size: 14px;
  }

  .nav {
    gap: 12px;
    font-size: 11px;
  }

  .banner h1 {
    font-size: 24px;
  }

  .contact-section h1 {
    font-size: 1.75rem;
  }

  .contact-form button {
    font-size: 1rem;
    padding: 10px 20px;
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
      <a href="brands.php">Thương hiệu</a>
      <a href="builds.php">Xây dựng cấu hình</a>
      <a href="about.php">Giới thiệu</a>
      <a href="contact.php" class="active">Liên hệ</a>
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
  <h1>Liên Hệ Với Chúng Tôi</h1>
  <p>Chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7</p>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="contact-section">
    <h1>📞 Gửi Thông Tin Liên Hệ</h1>
    <p>Nếu bạn có bất kỳ thắc mắc nào về sản phẩm hoặc cần hỗ trợ kỹ thuật, hãy gửi thông tin cho chúng tôi qua form dưới đây.</p>
</div>

<div class="contact-form">
    <?php if ($success_message): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">✗ <?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        
        <div class="form-group">
            <label for="name"><i class="fa-solid fa-user"></i> Họ và Tên *</label>
            <input type="text" id="name" name="name" placeholder="Nhập họ và tên của bạn" required>
        </div>
        
        <div class="form-group">
            <label for="email"><i class="fa-solid fa-envelope"></i> Email *</label>
            <input type="email" id="email" name="email" placeholder="Nhập địa chỉ email" required>
        </div>
        
        <div class="form-group">
            <label for="message"><i class="fa-solid fa-message"></i> Nội dung *</label>
            <textarea id="message" name="message" placeholder="Nhập nội dung liên hệ..." required></textarea>
        </div>
        
        <button type="submit">
            <i class="fa-solid fa-paper-plane"></i> Gửi Liên Hệ
        </button>
    </form>
</div>

<div class="contact-info">
    <h4><i class="fa-solid fa-building"></i> Văn phòng chính:</h4>
    <p>123 Đường Lê Lợi, Quận 1, TP. Hồ Chí Minh</p>

    <h4><i class="fa-solid fa-envelope"></i> Email:</h4>
    <p>support@buildpc.vn</p>

    <h4><i class="fa-solid fa-phone"></i> Hotline:</h4>
    <p>0909 123 456 (Hỗ trợ 24/7)</p>

    <h4><i class="fa-solid fa-clock"></i> Giờ làm việc:</h4>
    <p>Thứ 2 - Thứ 6: 8:00 - 20:00</p>
    <p>Thứ 7 - Chủ nhật: 9:00 - 18:00</p>
</div>

<div class="map-container">
    <iframe 
        src="https://www.google.com/maps?q=ho%20chi%20minh&output=embed"
        width="100%" height="400" style="border:0;" allowfullscreen loading="lazy">
    </iframe>
</div>

<!-- ===== FOOTER ===== -->
<footer>
  <p>© <?= date('Y') ?> BuildPC.vn — Máy tính & Linh kiện chính hãng</p>
</footer>

</body>
</html>