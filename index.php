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
// FLASH SALE WIDGET - Lấy khuyến mãi đang chạy sớm kết thúc nhất
// ================================
$flash_sale = null;
try {
  $stmt = $pdo->query("SELECT pr.*, p.name AS product_name, p.price AS product_price, p.main_image, p.image
        FROM promotions pr
        JOIN products p ON pr.product_id = p.product_id
        WHERE pr.is_active = 1
          AND pr.start_date <= NOW()
          AND pr.end_date >= NOW()
          AND (pr.max_quantity = 0 OR pr.used_quantity < pr.max_quantity)
        ORDER BY pr.end_date ASC
        LIMIT 1
    ");
  $flash_sale = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
  $flash_sale = null;
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

  <!-- ===== FLASH SALE WIDGET (góc phải) ===== -->
  <?php if ($flash_sale):
    $fs_image = getProductImage($flash_sale);
    $fs_original = (float)$flash_sale['product_price'];
    $fs_sale = calculateSalePrice($fs_original, $flash_sale['discount_percent'], $flash_sale);
    $fs_percent = getPromotionDiscountPercent($fs_original, $flash_sale);
    $fs_end_iso = date('c', strtotime($flash_sale['end_date']));
  ?>
    <div id="flashSaleWidget" class="flash-sale-widget" data-end="<?= htmlspecialchars($fs_end_iso) ?>">
      <button type="button" class="flash-sale-close" onclick="document.getElementById('flashSaleWidget').classList.add('hidden')" aria-label="Đóng">&times;</button>
      <div class="flash-sale-head"><i class="fa-solid fa-bolt"></i> FLASH SALE</div>
      <a href="page/product_detail.php?id=<?= (int)$flash_sale['product_id'] ?>" class="flash-sale-body">
        <div class="flash-sale-img">
          <img src="<?= escape($fs_image) ?>" alt="<?= escape($flash_sale['product_name']) ?>" onerror="this.src='uploads/img/no-image.png'">
          <span class="flash-sale-badge">-<?= $fs_percent ?>%</span>
        </div>
        <div class="flash-sale-info">
          <div class="flash-sale-name"><?= escape($flash_sale['product_name']) ?></div>
          <div class="flash-sale-price">
            <span class="fs-sale"><?= formatPriceVND($fs_sale) ?></span>
            <span class="fs-original"><?= formatPriceVND($fs_original) ?></span>
          </div>
        </div>
      </a>
      <div class="flash-sale-timer">
        <span class="fs-timer-label">Kết thúc sau</span>
        <div class="fs-countdown">
          <span class="fs-unit"><b id="fsDays">00</b><small>Ngày</small></span>
          <span class="fs-sep">:</span>
          <span class="fs-unit"><b id="fsHours">00</b><small>Giờ</small></span>
          <span class="fs-sep">:</span>
          <span class="fs-unit"><b id="fsMinutes">00</b><small>Phút</small></span>
          <span class="fs-sep">:</span>
          <span class="fs-unit"><b id="fsSeconds">00</b><small>Giây</small></span>
        </div>
      </div>
    </div>

    <style>
      .flash-sale-widget {
        position: fixed; right: 20px; bottom: 20px; width: 300px;
        background: #fff; border-radius: 16px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
        z-index: 999; overflow: hidden; animation: fsSlideIn 0.5s ease-out;
      }
      .flash-sale-widget.hidden { display: none; }
      @keyframes fsSlideIn {
        from { transform: translateX(120%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      .flash-sale-close {
        position: absolute; top: 8px; right: 10px;
        background: rgba(255, 255, 255, 0.3); border: none; color: #fff;
        font-size: 20px; line-height: 1; width: 26px; height: 26px;
        border-radius: 50%; cursor: pointer; z-index: 2;
      }
      .flash-sale-head {
        background: linear-gradient(135deg, #f44336, #ff9800);
        color: #fff; font-weight: 700; letter-spacing: 1px; padding: 12px 16px;
        display: flex; align-items: center; gap: 8px; font-size: 15px;
      }
      .flash-sale-body { display: flex; gap: 12px; padding: 14px; text-decoration: none; color: inherit; }
      .flash-sale-img {
        position: relative; flex-shrink: 0; width: 80px; height: 80px;
        border-radius: 10px; overflow: hidden; background: #f5f5f5;
      }
      .flash-sale-img img { width: 100%; height: 100%; object-fit: cover; }
      .flash-sale-badge {
        position: absolute; top: 4px; left: 4px; background: #f44336; color: #fff;
        font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 6px;
      }
      .flash-sale-info { display: flex; flex-direction: column; justify-content: center; min-width: 0; }
      .flash-sale-name {
        font-size: 14px; font-weight: 600; color: #333; margin-bottom: 6px;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
      }
      .flash-sale-price { display: flex; flex-direction: column; }
      .fs-sale { color: #f44336; font-weight: 700; font-size: 16px; }
      .fs-original { color: #999; text-decoration: line-through; font-size: 12px; }
      .flash-sale-timer { background: #1a1a2e; padding: 12px 14px; text-align: center; }
      .fs-timer-label { color: #ffd700; font-size: 12px; display: block; margin-bottom: 8px; }
      .fs-countdown { display: flex; align-items: center; justify-content: center; gap: 4px; }
      .fs-unit { display: flex; flex-direction: column; align-items: center; }
      .fs-unit b {
        background: #f44336; color: #fff; font-size: 18px; padding: 4px 8px;
        border-radius: 6px; min-width: 34px; display: inline-block;
      }
      .fs-unit small { color: #ccc; font-size: 10px; margin-top: 3px; }
      .fs-sep { color: #fff; font-weight: 700; align-self: flex-start; margin-top: 4px; }
      .flash-sale-widget.ended .flash-sale-timer { background: #555; }
      .flash-sale-widget.ended .fs-timer-label { color: #fff; }
      @media (max-width: 480px) {
        .flash-sale-widget { width: calc(100% - 30px); right: 15px; bottom: 15px; }
      }
    </style>

    <script>
      (function() {
        const widget = document.getElementById('flashSaleWidget');
        if (!widget) return;
        const endTime = new Date(widget.dataset.end).getTime();
        const elDays = document.getElementById('fsDays');
        const elHours = document.getElementById('fsHours');
        const elMinutes = document.getElementById('fsMinutes');
        const elSeconds = document.getElementById('fsSeconds');
        function pad(n) { return String(n).padStart(2, '0'); }
        function tick() {
          const now = Date.now();
          let diff = Math.floor((endTime - now) / 1000);
          if (diff <= 0) {
            elDays.textContent = elHours.textContent = elMinutes.textContent = elSeconds.textContent = '00';
            widget.classList.add('ended');
            widget.querySelector('.fs-timer-label').textContent = 'Đã kết thúc';
            clearInterval(timer);
            return;
          }
          const days = Math.floor(diff / 86400); diff -= days * 86400;
          const hours = Math.floor(diff / 3600); diff -= hours * 3600;
          const minutes = Math.floor(diff / 60);
          const seconds = diff - minutes * 60;
          elDays.textContent = pad(days);
          elHours.textContent = pad(hours);
          elMinutes.textContent = pad(minutes);
          elSeconds.textContent = pad(seconds);
        }
        tick();
        const timer = setInterval(tick, 1000);
      })();
    </script>
  <?php endif; ?>

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