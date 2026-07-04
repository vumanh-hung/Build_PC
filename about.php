<?php

/**
 * about.php - About Us Page
 * Trang giới thiệu về BuildPC.vn
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
// GET CART COUNT
// ================================================

$cart_count = $user_id ? getCartCount($user_id) : 0;

// ================================================
// TEAM MEMBERS DATA
// ================================================

$team_members = [
  [
    'name' => 'Xuân Minh',
    'role' => 'Backend Developer',
    'description' => 'Phát triển hệ thống backend, API và database',
    'icon' => 'fa-code',
    'color' => '#007bff'
  ],
  [
    'name' => 'Mạnh Hùng',
    'role' => 'Admin & Project Manager',
    'description' => 'Quản lý dự án và phát triển tính năng admin',
    'icon' => 'fa-user-shield',
    'color' => '#28a745'
  ],
  [
    'name' => 'Hoàng Nam',
    'role' => 'Database Architect',
    'description' => 'Thiết kế và tối ưu hóa cơ sở dữ liệu',
    'icon' => 'fa-database',
    'color' => '#dc3545'
  ]
];

// ================================================
// FEATURES DATA
// ================================================

$features = [
  [
    'icon' => 'fa-shield-halved',
    'title' => 'Chính hãng 100%',
    'description' => 'Cam kết sản phẩm chính hãng từ nhà phân phối uy tín'
  ],
  [
    'icon' => 'fa-truck-fast',
    'title' => 'Giao hàng nhanh',
    'description' => 'Vận chuyển toàn quốc, giao hàng trong 24-48h'
  ],
  [
    'icon' => 'fa-headset',
    'title' => 'Hỗ trợ 24/7',
    'description' => 'Tư vấn nhiệt tình, hỗ trợ khách hàng mọi lúc mọi nơi'
  ],
  [
    'icon' => 'fa-medal',
    'title' => 'Bảo hành tốt',
    'description' => 'Chính sách bảo hành ưu việt, đổi trả linh hoạt'
  ],
  [
    'icon' => 'fa-tags',
    'title' => 'Giá cạnh tranh',
    'description' => 'Giá tốt nhất thị trường, nhiều ưu đãi hấp dẫn'
  ],
  [
    'icon' => 'fa-screwdriver-wrench',
    'title' => 'Tư vấn chuyên nghiệp',
    'description' => 'Đội ngũ kỹ thuật giàu kinh nghiệm hỗ trợ tư vấn'
  ]
];

// ================================================
// STATS DATA
// ================================================

$stats = [
  [
    'number' => '10,000+',
    'label' => 'Khách hàng',
    'icon' => 'fa-users'
  ],
  [
    'number' => '5,000+',
    'label' => 'Sản phẩm',
    'icon' => 'fa-box'
  ],
  [
    'number' => '50+',
    'label' => 'Thương hiệu',
    'icon' => 'fa-tag'
  ],
  [
    'number' => '99%',
    'label' => 'Hài lòng',
    'icon' => 'fa-heart'
  ]
];

// ================================================
// PAGE CONFIGURATION
// ================================================

$pageTitle = 'Giới thiệu - BuildPC.vn | Về chúng tôi';
$additionalCSS = [
  'assets/css/about.css',
  'assets/css/footer.css'
];
$additionalJS = [
  'assets/js/about.js'
];
$basePath = '../';

// ================================================
// INCLUDE HEADER
// ================================================

include __DIR__ . '/../includes/header.php';
?>

<!-- ===== HERO BANNER ===== -->
<div class="hero-banner">
  <div class="hero-content">
    <h1 data-aos="fade-up">
      <i class="fa-solid fa-building"></i>
      Về BuildPC.vn
    </h1>
    <p data-aos="fade-up" data-aos-delay="100">
      Nền tảng xây dựng cấu hình PC hàng đầu Việt Nam
    </p>
  </div>
</div>

<!-- ===== MAIN CONTAINER ===== -->
<div class="container">

  <!-- ===== ABOUT SECTION ===== -->
  <section class="about-section" data-aos="fade-up">
    <div class="about-content">
      <div class="about-text">
        <h2>Chúng tôi là ai?</h2>
        <p>
          <strong>BuildPC.vn</strong> là nền tảng hỗ trợ người dùng dễ dàng lựa chọn,
          cấu hình và mua sắm linh kiện máy tính phù hợp nhất. Với giao diện thân thiện,
          thông tin minh bạch và tính năng so sánh linh kiện thông minh, chúng tôi giúp
          bạn tự tin tạo nên bộ PC mạnh mẽ, tối ưu hiệu năng và chi phí.
        </p>
        <p>
          Sứ mệnh của chúng tôi là mang đến cho người dùng trải nghiệm mua sắm linh kiện
          trực tuyến <strong>nhanh chóng - chính xác - chuyên nghiệp</strong>. Mỗi sản phẩm
          được chọn lọc kỹ càng từ các thương hiệu uy tín hàng đầu như
          <em>ASUS, MSI, GIGABYTE, Intel, AMD</em>...
        </p>
      </div>
      <div class="about-image">
        <img src="../assets/img/about-illustration.svg"
          alt="BuildPC Illustration"
          onerror="this.src='../uploads/img/pc-building.png'">
      </div>
    </div>
  </section>

  <!-- ===== STATS SECTION ===== -->
  <section class="stats-section" data-aos="fade-up">
    <div class="stats-grid">
      <?php foreach ($stats as $index => $stat): ?>
        <div class="stat-card" data-aos="zoom-in" data-aos-delay="<?= $index * 100 ?>">
          <div class="stat-icon">
            <i class="fa-solid <?= $stat['icon'] ?>"></i>
          </div>
          <div class="stat-number"><?= $stat['number'] ?></div>
          <div class="stat-label"><?= $stat['label'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ===== FEATURES SECTION ===== -->
  <section class="features-section" data-aos="fade-up">
    <div class="section-header">
      <h2>Tại sao chọn chúng tôi?</h2>
      <p>Những lý do bạn nên tin tưởng BuildPC.vn</p>
    </div>

    <div class="features-grid">
      <?php foreach ($features as $index => $feature): ?>
        <div class="feature-card" data-aos="fade-up" data-aos-delay="<?= $index * 50 ?>">
          <div class="feature-icon">
            <i class="fa-solid <?= $feature['icon'] ?>"></i>
          </div>
          <h3><?= $feature['title'] ?></h3>
          <p><?= $feature['description'] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ===== MISSION SECTION ===== -->
  <section class="mission-section" data-aos="fade-up">
    <div class="mission-content">
      <div class="mission-card">
        <div class="mission-icon">
          <i class="fa-solid fa-bullseye"></i>
        </div>
        <h3>Sứ mệnh</h3>
        <p>
          Mang đến cho mọi người cơ hội sở hữu một chiếc PC hoàn hảo với giá cả
          hợp lý nhất, kèm theo dịch vụ tư vấn chuyên nghiệp và hỗ trợ tận tâm.
        </p>
      </div>

      <div class="mission-card">
        <div class="mission-icon">
          <i class="fa-solid fa-eye"></i>
        </div>
        <h3>Tầm nhìn</h3>
        <p>
          Trở thành nền tảng số 1 Việt Nam trong lĩnh vực tư vấn và cung cấp
          linh kiện máy tính, được khách hàng tin tưởng và lựa chọn hàng đầu.
        </p>
      </div>

      <div class="mission-card">
        <div class="mission-icon">
          <i class="fa-solid fa-handshake"></i>
        </div>
        <h3>Giá trị cốt lõi</h3>
        <p>
          Uy tín - Chất lượng - Chuyên nghiệp. Đặt lợi ích khách hàng lên hàng đầu,
          luôn đồng hành và hỗ trợ tận tình trong mọi giai đoạn.
        </p>
      </div>
    </div>
  </section>

  <!-- ===== TEAM SECTION ===== -->
  <section class="team-section" data-aos="fade-up">
    <div class="section-header">
      <h2>Đội ngũ phát triển</h2>
      <p>Những người đứng sau BuildPC.vn</p>
    </div>

    <div class="team-grid">
      <?php foreach ($team_members as $index => $member): ?>
        <div class="team-card" data-aos="flip-left" data-aos-delay="<?= $index * 100 ?>">
          <div class="team-avatar" style="background: <?= $member['color'] ?>">
            <i class="fa-solid <?= $member['icon'] ?>"></i>
          </div>
          <h3><?= $member['name'] ?></h3>
          <div class="team-role"><?= $member['role'] ?></div>
          <p><?= $member['description'] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ===== CTA SECTION ===== -->
  <section class="cta-section" data-aos="zoom-in">
    <div class="cta-content">
      <h2>Bắt đầu xây dựng PC của bạn ngay hôm nay!</h2>
      <p>Hàng ngàn sản phẩm chính hãng đang chờ bạn khám phá</p>
      <div class="cta-actions">
        <a href="builds.php" class="btn-primary">
          <i class="fa-solid fa-screwdriver-wrench"></i>
          Tạo cấu hình
        </a>
        <a href="products.php" class="btn-secondary">
          <i class="fa-solid fa-box"></i>
          Xem sản phẩm
        </a>
      </div>
    </div>
  </section>

</div>

<!-- ===== FOOTER ===== -->
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- ===== AOS ANIMATION LIBRARY ===== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

<!-- ===== ABOUT PAGE SCRIPT ===== -->
<script src="../assets/js/about.js"></script>

</body>

</html>