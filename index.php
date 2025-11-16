<?php

/**
 * index.php - Trang chủ BuildPC.vn
 * Optimized & Fixed Version
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// ================================================
// INITIALIZATION
// ================================================

$pdo = getPDO();
$user_id = getCurrentUserId();
$is_logged_in = isLoggedIn();
$is_admin = isAdmin();

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
// GET PRODUCTS BY CATEGORY - FIXED
// ================================================

try {
  // PC Gaming Products
  $pc_products = $pdo->query("
        SELECT p.*, c.name AS category_name, b.name AS brand_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.category_id = 16
        ORDER BY p.product_id DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);

  // AI Workstation Products
  $ai_products = $pdo->query("
        SELECT p.*, c.name AS category_name, b.name AS brand_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.category_id = 17
        ORDER BY p.product_id DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);

  // Linh kiện máy tính
  $components_products = $pdo->query("
        SELECT p.*, c.name AS category_name, b.name AS brand_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.category_id IN (2, 3, 19)
        ORDER BY p.product_id DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);

  // ⭐ Laptop Products - FIXED (category_id = 18)
  $laptop_products = $pdo->query("
        SELECT p.*, c.name AS category_name, b.name AS brand_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.category_id = 18
        ORDER BY p.product_id DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);

  // New Products (Tất cả sản phẩm mới nhất)
  $new_products = $pdo->query("
        SELECT p.*, c.name AS category_name, b.name AS brand_name
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.category_id IS NOT NULL
        ORDER BY p.product_id DESC 
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Error fetching products: " . $e->getMessage());
  $pc_products = [];
  $ai_products = [];
  $components_products = [];
  $laptop_products = [];
  $new_products = [];
}

// ================================================
// RENDER FUNCTIONS
// ================================================

/**
 * Render product card cho trang chủ
 */
function renderHomeProductCard($product)
{
  $image_path = getProductImage($product);
  $promotion = getProductPromotion($product['product_id']);
  $has_promotion = !empty($promotion);

  $original_price = $product['price'];
  $discount_percent = $has_promotion ? $promotion['discount_percent'] : 0;
  $sale_price = $has_promotion ? calculateSalePrice($original_price, $discount_percent) : $original_price;
  $sold_count = $product['sold_count'] ?? 0;
?>

  <div class="product-card" data-aos="fade-up" data-aos-duration="600">
    <a href="page/product_detail.php?id=<?= $product['product_id'] ?>" class="product-link">
      <!-- Product Image -->
      <div class="product-image">
        <?php if ($has_promotion): ?>
          <div class="discount-badge">-<?= $discount_percent ?>%</div>
        <?php endif; ?>

        <?php if (!empty($product['is_hot'])): ?>
          <div class="hot-badge">HOT</div>
        <?php endif; ?>

        <img src="<?= escape($image_path) ?>"
          alt="<?= escape($product['name']) ?>"
          onerror="this.src='uploads/img/no-image.png'">

        <div class="product-overlay">
          <div class="quick-view">
            <i class="fa-solid fa-eye"></i>
            Xem chi tiết
          </div>
        </div>
      </div>

      <!-- Product Content -->
      <div class="product-content">
        <div class="product-category">
          <?= escape($product['category_name'] ?? 'Sản phẩm') ?>
        </div>

        <h3 class="product-name">
          <?= escape($product['name']) ?>
        </h3>

        <!-- Price Section -->
        <?php if ($has_promotion): ?>
          <div class="price-section-index">
            <div class="price-row-index">
              <span class="original-price-index"><?= formatPriceVND($original_price) ?></span>
              <span class="discount-badge-inline">-<?= $discount_percent ?>%</span>
            </div>
            <div class="sale-price-index"><?= formatPriceVND($sale_price) ?></div>
          </div>
        <?php else: ?>
          <div class="price-section-index">
            <div class="current-price-index"><?= formatPriceVND($original_price) ?></div>
          </div>
        <?php endif; ?>

        <?php if ($sold_count > 0): ?>
          <div class="sold-count-index">
            <i class="fa-solid fa-fire"></i>
            Đã bán: <?= number_format($sold_count) ?>
          </div>
        <?php endif; ?>
      </div>
    </a>
  </div>

<?php
}

/**
 * Render category section
 */
function renderCategorySection($title, $products, $viewMoreLink)
{
?>
  <div class="section" data-aos="fade-up">
    <div class="section-header">
      <h2><?= $title ?></h2>
      <p>Những sản phẩm tốt nhất dành cho bạn</p>
    </div>

    <div class="product-grid">
      <?php if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
          <?php renderHomeProductCard($product); ?>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state" style="grid-column: 1/-1;">
          <i class="fa-solid fa-box-open"></i>
          <p>Chưa có sản phẩm trong danh mục này</p>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($products)): ?>
      <div style="text-align: center; margin-top: 40px;">
        <a href="<?= $viewMoreLink ?>" class="btn-view-more">
          Xem tất cả <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>
    <?php endif; ?>
  </div>
<?php
}

// ================================================
// PAGE CONFIGURATION
// ================================================

$pageTitle = 'Trang chủ - BuildPC.vn | PC Gaming & Linh Kiện Chính Hãng';
$additionalCSS = ['assets/css/home.css'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <meta name="description" content="BuildPC.vn - Chuyên cung cấp PC Gaming, Linh kiện máy tính chính hãng, giá tốt nhất. Hỗ trợ tư vấn xây dựng cấu hình 24/7.">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- AOS Animation -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/header.css">
  <link rel="stylesheet" href="assets/css/home.css">
  <link rel="stylesheet" href="assets/css/footer.css">
</head>

<body>

  <!-- Audio for notifications -->
  <audio id="tingSound" preload="auto">
    <source src="uploads/sound/ting.mp3" type="audio/mpeg">
  </audio>

  <!-- ===== HEADER ===== -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <!-- ===== HERO BANNER ===== -->
  <div class="banner">
    <div class="banner-content">
      <h1 data-aos="fade-up">🖥️ BuildPC - Xây Dựng PC Mơ Ước</h1>
      <p data-aos="fade-up" data-aos-delay="100">
        Linh kiện chính hãng • Giá tốt nhất • Hỗ trợ tư vấn 24/7
      </p>
      <div class="banner-features" data-aos="fade-up" data-aos-delay="200">
        <div class="feature-item">
          <i class="fa-solid fa-shield-halved"></i>
          <span>Chính hãng 100%</span>
        </div>
        <div class="feature-item">
          <i class="fa-solid fa-truck-fast"></i>
          <span>Giao hàng nhanh</span>
        </div>
        <div class="feature-item">
          <i class="fa-solid fa-headset"></i>
          <span>Hỗ trợ 24/7</span>
        </div>
        <div class="feature-item">
          <i class="fa-solid fa-medal"></i>
          <span>Bảo hành tốt nhất</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== PRODUCT SECTIONS ===== -->
  <?php
  renderCategorySection(
    '🖥️ Máy tính bộ PC',
    $pc_products,
    'page/products.php?category_id=1'
  );

  renderCategorySection(
    '🤖 PC AI cao cấp',
    $ai_products,
    'page/products.php?category_id=3'
  );

  renderCategorySection(
    '⚙️ Linh kiện PC chính hãng',
    $components_products,
    'page/products.php?category_id=4'
  );

  renderCategorySection(
    '💻 Laptop gaming',
    $laptop_products,
    'page/products.php?category_id=20'
  );

  renderCategorySection(
    '✨ Sản phẩm mới nhất',
    $new_products,
    'page/products.php'
  );
  ?>

  <!-- ===== FOOTER ===== -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <!-- ===== SCRIPTS ===== -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  <script>
    // Initialize AOS
    AOS.init({
      duration: 800,
      easing: 'ease-out-cubic',
      once: true,
      offset: 50
    });

    // Section title shake animation on click
    document.addEventListener('DOMContentLoaded', function() {
      const sectionTitles = document.querySelectorAll('.section-header h2');

      sectionTitles.forEach(title => {
        title.addEventListener('click', function() {
          this.classList.remove('shake');
          void this.offsetWidth; // Trigger reflow
          this.classList.add('shake');

          setTimeout(() => {
            this.classList.remove('shake');
          }, 600);
        });
      });
    });

    // Cart count update function
    function updateCartCount(count) {
      const cartCountEl = document.querySelector('.cart-count');
      const cartLink = document.querySelector('.cart-link');

      if (count > 0) {
        if (cartCountEl) {
          cartCountEl.textContent = count;
        } else {
          const badge = document.createElement('span');
          badge.className = 'cart-count';
          badge.textContent = count;
          cartLink.appendChild(badge);
        }

        // Shake animation
        cartLink.classList.add('cart-shake');
        setTimeout(() => cartLink.classList.remove('cart-shake'), 600);

        // Play sound
        const sound = document.getElementById('tingSound');
        if (sound) sound.play().catch(e => console.log('Audio play failed:', e));
      } else if (cartCountEl) {
        cartCountEl.remove();
      }
    }

    console.log('✅ BuildPC homepage loaded successfully');
  </script>

</body>

</html>