<?php

/**
 * builds.php - PC Build Configuration Page
 * Trang xây dựng cấu hình PC
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ================================================
// INITIALIZATION
// ================================================

$pdo = getPDO();
$user_id = getCurrentUserId();

// ================================================
// CSRF TOKEN
// ================================================

if (!isset($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// ================================================
// GET ALL CATEGORIES
// ================================================

try {
  $categories = $pdo->query("
        SELECT * FROM categories 
        ORDER BY name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Error fetching categories: " . $e->getMessage());
  $categories = [];
}

// ================================================
// GET USER'S SAVED BUILDS
// ================================================

$builds = [];
if ($user_id) {
  try {
    $builds = getUserBuilds($user_id);
  } catch (Exception $e) {
    error_log("Error fetching user builds: " . $e->getMessage());
  }
}

// ================================================
// GET CART COUNT
// ================================================

$cart_count = $user_id ? getCartCount($user_id) : 0;

// ================================================
// PAGE CONFIGURATION
// ================================================

$pageTitle = 'Xây dựng cấu hình PC - BuildPC.vn';
$additionalCSS = [
  'assets/css/builds.css',
  'assets/css/footer.css'
];
$additionalJS = [
  'assets/js/builds.js'
];
$basePath = '../';

// ================================================
// INCLUDE HEADER
// ================================================

include __DIR__ . '/../includes/header.php';
?>

<!-- CSRF Token & User State -->
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
<meta name="user-id" content="<?= $user_id ? htmlspecialchars($user_id) : '' ?>">
<meta name="user-logged-in" content="<?= $user_id ? 'true' : 'false' ?>">

<!-- Cart Sound -->
<audio id="tingSound" preload="auto">
  <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<!-- ===== PAGE BANNER ===== -->
<div class="page-banner">
  <div class="banner-content">
    <h1 data-aos="fade-up">
      <i class="fa-solid fa-screwdriver-wrench"></i>
      Xây dựng cấu hình PC
    </h1>
    <p data-aos="fade-up" data-aos-delay="100">
      Chọn linh kiện phù hợp để tạo nên bộ máy mạnh mẽ nhất
    </p>
  </div>
</div>

<!-- ===== MAIN CONTAINER ===== -->
<div class="container">

  <!-- ===== BUILD MODE INDICATOR ===== -->
  <div class="build-mode-indicator" id="buildModeIndicator">
    <div class="indicator-content">
      <div class="indicator-icon">
        <i class="fa-solid fa-screwdriver-wrench fa-spin"></i>
      </div>
      <div class="indicator-text">
        <h4>Đang tạo cấu hình mới</h4>
        <p>Click vào linh kiện bên dưới để chọn sản phẩm</p>
      </div>
    </div>
    <div class="indicator-actions">
      <button class="btn-save-build" onclick="BuildsPage.finishBuild()">
        <i class="fa-solid fa-check-circle"></i>
        Hoàn thành & Lưu
      </button>
      <button class="btn-cancel-build" onclick="BuildsPage.cancelBuild()">
        <i class="fa-solid fa-times"></i>
        Hủy
      </button>
    </div>
  </div>

  <!-- ===== INSTRUCTION BOX ===== -->
  <div class="instruction-box" id="instructionBox" data-aos="fade-up">
    <div class="instruction-icon">
      <i class="fa-solid fa-lightbulb"></i>
    </div>
    <div class="instruction-content">
      <h3>Cách tạo cấu hình mới</h3>
      <p>Nhấn nút bên dưới, sau đó click vào các linh kiện để chọn sản phẩm cho cấu hình của bạn</p>
      <button class="btn-new-build" onclick="BuildsPage.startNewBuild()">
        <i class="fa-solid fa-plus-circle"></i>
        <span>Bắt đầu tạo cấu hình</span>
      </button>
    </div>
  </div>

  <!-- ===== CATEGORIES GRID ===== -->
  <div class="section-header" data-aos="fade-up">
    <h2>Chọn linh kiện</h2>
    <p>Click vào từng danh mục để chọn sản phẩm</p>
  </div>

  <div class="categories-grid" id="categoriesGrid" data-aos="fade-up">
    <?php if (!empty($categories)): ?>
      <?php foreach ($categories as $index => $category): ?>
        <div class="category-card"
          data-category-id="<?= $category['category_id'] ?>"
          data-category-name="<?= escape($category['name']) ?>"
          data-aos="fade-up"
          data-aos-delay="<?= $index * 50 ?>">

          <div class="category-icon">
            <?php
            // Category icon mapping
            $icons = [
              'CPU' => 'fa-microchip',
              'RAM' => 'fa-memory',
              'Mainboard' => 'fa-server',
              'VGA' => 'fa-display',
              'SSD' => 'fa-hard-drive',
              'PSU' => 'fa-plug',
              'Case' => 'fa-box',
              'PC Gaming' => 'fa-desktop',
              'Laptop' => 'fa-laptop',
              'Màn hình' => 'fa-tv'
            ];
            $icon = $icons[$category['name']] ?? 'fa-cube';
            ?>
            <i class="fa-solid <?= $icon ?>"></i>
          </div>

          <h3><?= escape($category['name']) ?></h3>

          <?php if (!empty($category['description'])): ?>
            <p class="category-description">
              <?= escape(truncateText($category['description'], 60)) ?>
            </p>
          <?php endif; ?>

          <div class="category-action">
            <span>Chọn sản phẩm</span>
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">
        <i class="fa-solid fa-box-open"></i>
        <p>Không có danh mục nào</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- ===== SAVED BUILDS SECTION ===== -->
  <div class="section-header" style="margin-top: 80px;" data-aos="fade-up">
    <h2>Cấu hình đã lưu</h2>
    <p>Quản lý các cấu hình PC của bạn</p>
  </div>

  <?php if (!$user_id): ?>
    <div class="login-prompt" data-aos="fade-up">
      <i class="fa-solid fa-lock"></i>
      <h3>Vui lòng đăng nhập</h3>
      <p>Đăng nhập để xem và quản lý các cấu hình đã lưu của bạn</p>
      <a href="login.php" class="btn-login">
        <i class="fa-solid fa-user"></i>
        Đăng nhập ngay
      </a>
    </div>
  <?php elseif (empty($builds)): ?>
    <div class="empty-builds" data-aos="fade-up">
      <i class="fa-solid fa-folder-open"></i>
      <h3>Chưa có cấu hình nào</h3>
      <p>Bạn chưa tạo cấu hình PC nào. Hãy bắt đầu ngay!</p>
    </div>
  <?php else: ?>
    <div class="builds-grid" data-aos="fade-up">
      <?php foreach ($builds as $index => $build): ?>
        <div class="build-card" data-aos="fade-up" data-aos-delay="<?= $index * 50 ?>">
          <div class="build-header">
            <h3><?= escape($build['name']) ?></h3>
            <div class="build-badge">
              <i class="fa-solid fa-calendar"></i>
              <?= formatDate($build['created_at']) ?>
            </div>
          </div>

          <div class="build-price">
            <span class="price-label">Tổng giá:</span>
            <span class="price-value"><?= formatPriceVND($build['total_price']) ?></span>
          </div>

          <?php if (!empty($build['description'])): ?>
            <div class="build-description">
              <?= escape($build['description']) ?>
            </div>
          <?php endif; ?>

          <div class="build-actions">
            <a href="build_manage.php?id=<?= $build['build_id'] ?>" class="btn-action btn-edit">
              <i class="fa-solid fa-edit"></i>
              Quản lý
            </a>
            <button class="btn-action btn-cart"
              onclick="BuildsPage.addBuildToCart(<?= $build['build_id'] ?>)">
              <i class="fa-solid fa-cart-plus"></i>
              Thêm vào giỏ
            </button>
            <button class="btn-action btn-delete"
              onclick="BuildsPage.deleteBuild(<?= $build['build_id'] ?>)">
              <i class="fa-solid fa-trash"></i>
              Xóa
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<!-- ===== TOAST NOTIFICATION ===== -->
<div id="toast" class="toast"></div>

<!-- ===== FOOTER ===== -->
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- ===== AOS ANIMATION LIBRARY ===== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

<!-- ===== BUILDS PAGE SCRIPT ===== -->
<script src="../assets/js/builds.js"></script>

</body>

</html>