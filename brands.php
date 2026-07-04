<?php

/**
 * brands.php - Brands Page
 * Trang hiển thị danh sách thương hiệu và sản phẩm theo thương hiệu
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
// GET BRANDS WITH PRODUCT COUNT
// ================================================

try {
  $stmt = $pdo->query("
        SELECT 
            b.*,
            COUNT(DISTINCT p.product_id) as product_count
        FROM brands b
        LEFT JOIN products p ON b.brand_id = p.brand_id
        GROUP BY b.brand_id
        ORDER BY b.name ASC
    ");
  $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Error fetching brands: " . $e->getMessage());
  $brands = [];
}

// ================================================
// GET PRODUCTS BY BRAND (if selected)
// ================================================

$products = [];
$selected_brand = null;

if (isset($_GET['brand_id'])) {
  $brand_id = intval($_GET['brand_id']);

  try {
    // Get brand info
    $stmt = $pdo->prepare("SELECT * FROM brands WHERE brand_id = ?");
    $stmt->execute([$brand_id]);
    $selected_brand = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get products
    if ($selected_brand) {
      $stmt = $pdo->prepare("
                SELECT 
                    p.*,
                    c.name AS category_name,
                    b.name AS brand_name
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.brand_id = ?
                ORDER BY p.created_at DESC
            ");
      $stmt->execute([$brand_id]);
      $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  } catch (PDOException $e) {
    error_log("Error fetching brand products: " . $e->getMessage());
  }
}

// ================================================
// GET CART COUNT
// ================================================

$cart_count = $user_id ? getCartCount($user_id) : 0;

// ================================================
// PAGE CONFIGURATION
// ================================================

$pageTitle = $selected_brand
  ? 'Thương hiệu ' . htmlspecialchars($selected_brand['name']) . ' - BuildPC.vn'
  : 'Thương hiệu - BuildPC.vn';

$additionalCSS = [
  'assets/css/brands.css',
  'assets/css/footer.css'
];

$additionalJS = [
  'assets/js/brands.js'
];

$basePath = '../';

// ================================================
// INCLUDE HEADER
// ================================================

include __DIR__ . '/../includes/header.php';
?>

<!-- CSRF Token Meta Tag -->
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">

<!-- Cart Sound -->
<audio id="cartSound" preload="auto">
  <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<!-- ===== PAGE BANNER ===== -->
<div class="page-banner">
  <div class="banner-content">
    <h1 data-aos="fade-up">
      <?= $selected_brand ? '🏷️ ' . htmlspecialchars($selected_brand['name']) : '🏷️ Thương Hiệu Nổi Bật' ?>
    </h1>
    <p data-aos="fade-up" data-aos-delay="100">
      <?= $selected_brand
        ? 'Khám phá các sản phẩm của ' . htmlspecialchars($selected_brand['name'])
        : 'Các thương hiệu công nghệ hàng đầu thế giới'
      ?>
    </p>

    <?php if ($selected_brand): ?>
      <div class="banner-actions" data-aos="fade-up" data-aos-delay="200">
        <a href="brands.php" class="btn-back">
          <i class="fa-solid fa-arrow-left"></i>
          Quay lại danh sách thương hiệu
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="container">

  <?php if (!$selected_brand): ?>
    <!-- ===== BRANDS GRID ===== -->
    <div class="brands-section" data-aos="fade-up">
      <div class="section-header">
        <h2>Danh Sách Thương Hiệu</h2>
        <p>Tổng cộng <?= count($brands) ?> thương hiệu</p>
      </div>

      <div class="brand-grid">
        <?php if (!empty($brands)): ?>
          <?php foreach ($brands as $index => $brand): ?>
            <div class="brand-card" data-aos="fade-up" data-aos-delay="<?= $index * 50 ?>">
              <a href="?brand_id=<?= $brand['brand_id'] ?>" class="brand-link">
                <div class="brand-logo-wrapper">
                  <?php
                  $brand_image = null;

                  // Check for brand logo in multiple locations
                  $possible_paths = [
                    __DIR__ . '/../uploads/brands/' . $brand['slug'] . '.png',
                    __DIR__ . '/../uploads/brands/' . $brand['slug'] . '.jpg',
                    __DIR__ . '/../uploads/' . $brand['slug'],
                    __DIR__ . '/../uploads/img/brands/' . $brand['slug'] . '.png'
                  ];

                  foreach ($possible_paths as $path) {
                    if (file_exists($path)) {
                      $brand_image = str_replace(__DIR__ . '/../', '', $path);
                      break;
                    }
                  }
                  ?>

                  <div class="brand-logo">
                    <?php if ($brand_image): ?>
                      <img src="../<?= htmlspecialchars($brand_image) ?>"
                        alt="<?= htmlspecialchars($brand['name']) ?>"
                        onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-box\'></i>'">
                    <?php else: ?>
                      <i class="fa-solid fa-box"></i>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="brand-info">
                  <h3 class="brand-name"><?= htmlspecialchars($brand['name']) ?></h3>

                  <?php if (!empty($brand['description'])): ?>
                    <p class="brand-description">
                      <?= htmlspecialchars(truncateText($brand['description'], 80)) ?>
                    </p>
                  <?php endif; ?>

                  <div class="brand-stats">
                    <span class="product-count">
                      <i class="fa-solid fa-box"></i>
                      <?= $brand['product_count'] ?> sản phẩm
                    </span>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state" style="grid-column: 1/-1;">
            <i class="fa-solid fa-box-open"></i>
            <p>Chưa có thương hiệu nào</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  <?php else: ?>
    <!-- ===== BRAND PRODUCTS ===== -->
    <div class="products-section" data-aos="fade-up">
      <div class="section-header">
        <h2>Sản Phẩm <?= htmlspecialchars($selected_brand['name']) ?></h2>
        <p><?= count($products) ?> sản phẩm được tìm thấy</p>
      </div>

      <div class="product-grid">
        <?php if (!empty($products)): ?>
          <?php foreach ($products as $product): ?>
            <?php
            $image_path = getProductImage($product);
            $promotion = getProductPromotion($product['product_id']);
            $has_promotion = !empty($promotion);

            $original_price = $product['price'];
            $discount_percent = $has_promotion ? $promotion['discount_percent'] : 0;
            $sale_price = $has_promotion ? calculateSalePrice($original_price, $discount_percent) : $original_price;
            $sold_count = $product['sold_count'] ?? 0;
            ?>

            <div class="product-card" data-aos="fade-up">
              <a href="product_detail.php?id=<?= $product['product_id'] ?>" class="product-link">
                <!-- Product Image -->
                <div class="product-image">
                  <?php if ($has_promotion): ?>
                    <div class="discount-badge">-<?= $discount_percent ?>%</div>
                  <?php endif; ?>

                  <?php if (!empty($product['is_hot'])): ?>
                    <div class="hot-badge">HOT</div>
                  <?php endif; ?>

                  <img src="../<?= escape($image_path) ?>"
                    alt="<?= escape($product['name']) ?>"
                    onerror="this.src='../uploads/img/no-image.png'">
                </div>

                <!-- Product Info -->
                <div class="product-info">
                  <div class="product-category">
                    <?= escape($product['category_name'] ?? 'Sản phẩm') ?>
                  </div>

                  <h3 class="product-name">
                    <?= escape($product['name']) ?>
                  </h3>

                  <!-- Price -->
                  <?php if ($has_promotion): ?>
                    <div class="price-section">
                      <div class="price-row">
                        <span class="original-price"><?= formatPriceVND($original_price) ?></span>
                        <span class="discount-percent">-<?= $discount_percent ?>%</span>
                      </div>
                      <div class="sale-price"><?= formatPriceVND($sale_price) ?></div>
                    </div>
                  <?php else: ?>
                    <div class="price-section">
                      <div class="current-price"><?= formatPriceVND($original_price) ?></div>
                    </div>
                  <?php endif; ?>

                  <?php if ($sold_count > 0): ?>
                    <div class="sold-count">
                      <i class="fa-solid fa-fire"></i>
                      Đã bán: <?= number_format($sold_count) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </a>

              <!-- Product Actions -->
              <div class="product-actions">
                <input type="number"
                  class="qty-input"
                  value="1"
                  min="1"
                  max="99"
                  data-product-id="<?= $product['product_id'] ?>">
                <button type="button"
                  class="add-to-cart-btn"
                  data-product-id="<?= $product['product_id'] ?>">
                  <i class="fa-solid fa-cart-plus"></i>
                  Thêm
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state" style="grid-column: 1/-1;">
            <i class="fa-solid fa-box-open"></i>
            <h3>Chưa có sản phẩm</h3>
            <p>Thương hiệu này chưa có sản phẩm nào</p>
            <a href="brands.php" class="btn-primary">
              <i class="fa-solid fa-arrow-left"></i>
              Quay lại danh sách thương hiệu
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<!-- ===== TOAST NOTIFICATION ===== -->
<div id="toast" class="toast"></div>

<!-- ===== FOOTER ===== -->
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- ===== AOS ANIMATION LIBRARY ===== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

<!-- ===== BRANDS PAGE SCRIPT ===== -->
<script src="../assets/js/brands.js"></script>

</body>

</html>
require_once '../db.php';
$pdo = getPDO();
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        echo json_encode($pdo->query("SELECT * FROM brands")->fetchAll());
        break;
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("INSERT INTO brands (name, slug) VALUES (?, ?)");
        $stmt->execute([$data['name'], $data['slug']]);
        echo json_encode(['message' => 'Thêm thương hiệu thành công']);
        break;
}
?>
