<?php

/**
 * page/contact.php - Contact Page
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// Page configuration
$pageTitle = 'Liên hệ - BuildPC.vn';
$additionalCSS = ['assets/css/contact.css?v=1.0'];

// CSRF Token
if (!isset($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

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
      // TODO: Lưu vào database hoặc gửi email
      $success_message = 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.';

      // Reset form sau khi gửi thành công
      $_POST = [];
    }
  }
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Banner Section -->
<div class="contact-banner">
  <div class="banner-content">
    <h1 class="banner-title">Liên Hệ Với Chúng Tôi</h1>
    <p class="banner-subtitle">Chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7</p>
  </div>
  <div class="banner-decoration"></div>
  <div class="banner-decoration-2"></div>
</div>

<!-- Main Content -->
<div class="contact-container">

  <!-- Contact Introduction -->
  <section class="contact-intro">
    <h2 class="section-title">📞 Gửi Thông Tin Liên Hệ</h2>
    <p class="section-desc">
      Nếu bạn có bất kỳ thắc mắc nào về sản phẩm hoặc cần hỗ trợ kỹ thuật,
      hãy gửi thông tin cho chúng tôi qua form dưới đây.
    </p>
  </section>

  <!-- Contact Form -->
  <div class="contact-form-wrapper">

    <?php if ($success_message): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= htmlspecialchars($success_message) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error_message) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="" class="contact-form" id="contactForm">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

      <div class="form-group">
        <label for="name">
          <i class="fa-solid fa-user"></i> Họ và Tên *
        </label>
        <input
          type="text"
          id="name"
          name="name"
          placeholder="Nhập họ và tên của bạn"
          value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
          required>
      </div>

      <div class="form-group">
        <label for="email">
          <i class="fa-solid fa-envelope"></i> Email *
        </label>
        <input
          type="email"
          id="email"
          name="email"
          placeholder="Nhập địa chỉ email"
          value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
          required>
      </div>

      <div class="form-group">
        <label for="message">
          <i class="fa-solid fa-message"></i> Nội dung *
        </label>
        <textarea
          id="message"
          name="message"
          placeholder="Nhập nội dung liên hệ..."
          required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
      </div>

      <button type="submit" class="submit-btn">
        <i class="fa-solid fa-paper-plane"></i>
        <span>Gửi Liên Hệ</span>
      </button>
    </form>
  </div>

  <!-- Contact Information -->
  <div class="contact-info-wrapper">
    <div class="contact-info-card">
      <div class="info-item">
        <div class="info-icon">
          <i class="fa-solid fa-building"></i>
        </div>
        <div class="info-content">
          <h4>Văn phòng chính</h4>
          <p>123 Đường Lê Lợi, Quận 1, TP. Hồ Chí Minh</p>
        </div>
      </div>

      <div class="info-item">
        <div class="info-icon">
          <i class="fa-solid fa-envelope"></i>
        </div>
        <div class="info-content">
          <h4>Email</h4>
          <p><a href="mailto:support@buildpc.vn">support@buildpc.vn</a></p>
        </div>
      </div>

      <div class="info-item">
        <div class="info-icon">
          <i class="fa-solid fa-phone"></i>
        </div>
        <div class="info-content">
          <h4>Hotline</h4>
          <p><a href="tel:0909123456">0909 123 456</a> (Hỗ trợ 24/7)</p>
        </div>
      </div>

      <div class="info-item">
        <div class="info-icon">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div class="info-content">
          <h4>Giờ làm việc</h4>
          <p>Thứ 2 - Thứ 6: 8:00 - 20:00</p>
          <p>Thứ 7 - Chủ nhật: 9:00 - 18:00</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Map Section -->
  <div class="map-section">
    <h3 class="map-title">
      <i class="fa-solid fa-location-dot"></i> Vị trí của chúng tôi
    </h3>
    <div class="map-container">
      <iframe
        src="https://www.google.com/maps?q=ho%20chi%20minh&output=embed"
        width="100%"
        height="400"
        style="border:0;"
        allowfullscreen
        loading="lazy">
      </iframe>
    </div>
  </div>

</div>

<script src="../assets/js/contact.js?v=1.0"></script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>