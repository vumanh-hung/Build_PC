<?php
/**
 * Product Detail Page
 * Hiển thị chi tiết sản phẩm, reviews, specifications
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ===== CSRF TOKEN =====
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// ===== GET PRODUCT ID =====
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: products.php');
    exit;
}

// ===== DATABASE =====
$pdo = getPDO();

// ===== GET PRODUCT DETAILS =====
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, b.name AS brand_name, b.logo AS brand_logo
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.product_id = :product_id
");
$stmt->execute([':product_id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

// ===== GET PRODUCT IMAGES =====
$stmt = $pdo->prepare("
    SELECT * FROM product_images 
    WHERE product_id = :product_id 
    ORDER BY is_primary DESC, image_id ASC
");
$stmt->execute([':product_id' => $product_id]);
$product_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($product_images) && $product['main_image']) {
    $product_images = [['image_path' => $product['main_image'], 'is_primary' => 1]];
}

// ===== GET PRODUCT SPECIFICATIONS =====
$stmt = $pdo->prepare("
    SELECT * FROM product_specifications 
    WHERE product_id = :product_id
    ORDER BY spec_order ASC, spec_id ASC
");
$stmt->execute([':product_id' => $product_id]);
$specifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== GET REVIEWS =====
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.avatar
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.product_id = :product_id AND r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([':product_id' => $product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== GET REVIEW STATISTICS =====
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
    FROM reviews
    WHERE product_id = :product_id AND status = 'approved'
");
$stmt->execute([':product_id' => $product_id]);
$review_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// ===== GET RELATED PRODUCTS =====
$stmt = $pdo->prepare("
    SELECT p.*, b.name AS brand_name
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.category_id = :category_id 
    AND p.product_id != :product_id
    AND p.stock > 0
    ORDER BY RAND()
    LIMIT 6
");
$stmt->execute([
    ':category_id' => $product['category_id'],
    ':product_id' => $product_id
]);
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== GET CART COUNT =====
$user_id = getCurrentUserId();
$cart_count = $user_id ? getCartCount($user_id) : 0;

// ===== CHECK IF FLASH SALE / PROMOTION =====
$is_flash_sale = false;
$flash_sale_end = null;
$original_price = $product['price'];
$discount_percent = 0;

$stmt = $pdo->prepare("
    SELECT * FROM promotions 
    WHERE product_id = :product_id 
    AND start_date <= NOW() 
    AND end_date >= NOW()
    AND is_active = 1
    ORDER BY discount_percent DESC
    LIMIT 1
");
$stmt->execute([':product_id' => $product_id]);
$promotion = $stmt->fetch(PDO::FETCH_ASSOC);

if ($promotion) {
    $is_flash_sale = true;
    $flash_sale_end = $promotion['end_date'];
    $discount_percent = $promotion['discount_percent'];
    $product['sale_price'] = $product['price'] * (1 - $discount_percent / 100);
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - BuildPC.vn</title>
    <meta name="description" content="<?= htmlspecialchars(substr($product['description'] ?? '', 0, 160)) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/product_detail.css">
    <style>
        .compare-action { margin: 20px 0; }
        .btn-compare {
            width: 100%;
            padding: 15px;
            border: 2px solid #007bff;
            background: white;
            color: #007bff;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-compare:hover {
            background: #007bff;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        .btn-compare.active {
            background: #007bff;
            color: white;
        }
        .btn-compare:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .notification {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 15px 25px;
            background: #27ae60;
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        .notification.success { background: #27ae60; }
        .notification.warning { background: #f39c12; }
        .notification.info { background: #3498db; }
        .notification.error { background: #e74c3c; }
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
        #compareBar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
            padding: 15px 20px;
            z-index: 1000;
            border-top: 3px solid #007bff;
            animation: slideUp 0.3s ease-out;
            display: none;
        }
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .compare-bar-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .compare-bar-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            min-width: 200px;
        }
        .compare-bar-left strong {
            white-space: nowrap;
            font-size: 15px;
            color: #333;
        }
        .compare-bar-left i { color: #007bff; }
        #compareCount {
            color: #007bff;
            font-size: 18px;
            font-weight: 700;
        }
        #compareProductsList {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            flex: 1;
            overflow-x: auto;
            max-height: 60px;
        }
        .compare-product-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #495057;
            border: 1px solid #dee2e6;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .compare-product-item:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-remove-compare {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
            font-size: 14px;
        }
        .btn-remove-compare:hover {
            background: rgba(220, 53, 69, 0.1);
            transform: rotate(90deg);
        }
        .compare-bar-right {
            display: flex;
            gap: 10px;
            white-space: nowrap;
        }
        .compare-bar-right button {
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        .btn-clear {
            background: white;
            color: #dc3545;
            border: 2px solid #dc3545 !important;
        }
        .btn-clear:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        .btn-compare-now {
            background: linear-gradient(135deg, #e30019 0%, #c50015 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(227, 0, 25, 0.3);
        }
        .btn-compare-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(227, 0, 25, 0.4);
        }
        .btn-compare-now:active, .btn-clear:active {
            transform: translateY(0);
        }
        @media (max-width: 768px) {
            .compare-bar-content {
                flex-direction: column;
                gap: 12px;
            }
            .compare-bar-left {
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
            }
            #compareProductsList {
                width: 100%;
                justify-content: flex-start;
            }
            .compare-bar-right {
                width: 100%;
            }
            .compare-bar-right button {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header>
    <div class="header-left">
        <div class="logo">
            <a href="../index.php" style="text-decoration: none;">
                <span>🖥️ BuildPC.vn</span>
            </a>
        </div>
        <nav class="nav">
            <a href="../index.php">Trang chủ</a>
            <a href="products.php">Sản phẩm</a>
            <a href="brands.php">Thương hiệu</a>
            <a href="builds.php">Xây dựng cấu hình</a>
            <a href="about.php">Giới thiệu</a>
            <a href="contact.php">Liên hệ</a>
        </nav>
    </div>

    <div class="header-right">
        <a href="cart.php" class="cart-link">
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

<!-- ===== BREADCRUMB ===== -->
<div class="breadcrumb">
    <div class="container">
        <a href="../index.php">Trang chủ</a>
        <i class="fa-solid fa-chevron-right"></i>
        <a href="products.php">Sản phẩm</a>
        <i class="fa-solid fa-chevron-right"></i>
        <a href="products.php?category_id=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a>
        <i class="fa-solid fa-chevron-right"></i>
        <span><?= htmlspecialchars($product['name']) ?></span>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="container">
    <div class="product-detail">
        
        <!-- ===== LEFT: IMAGES ===== -->
        <div class="product-images">
            <div class="main-image">
                <?php if ($is_flash_sale): ?>
                    <div class="flash-sale-badge">
                        <i class="fa-solid fa-bolt"></i> FLASH SALE
                    </div>
                    <div class="discount-badge">-<?= $discount_percent ?>%</div>
                <?php endif; ?>
                
                <img id="mainImage" 
                     src="../<?= getProductImagePath($product_images[0]['image_path'] ?? $product['main_image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     onerror="this.src='../uploads/img/no-image.png'">
            </div>
            
            <?php if (count($product_images) > 1): ?>
            <div class="thumbnail-images">
                <?php foreach ($product_images as $index => $img): ?>
                <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" 
                     onclick="changeMainImage('<?= getProductImagePath($img['image_path']) ?>', this)">
                    <img src="../<?= getProductImagePath($img['image_path']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         onerror="this.src='../uploads/img/no-image.png'">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ===== RIGHT: INFO & PURCHASE ===== -->
        <div class="product-info">
            <!-- Product Name -->
            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            
            <!-- Rating & Sales -->
            <div class="product-meta">
                <div class="rating-section">
                    <div class="stars">
                        <?php
                        $avg_rating = $review_stats['avg_rating'] ?? 0;
                        for ($i = 1; $i <= 5; $i++):
                            if ($i <= $avg_rating):
                                echo '<i class="fa-solid fa-star"></i>';
                            elseif ($i - $avg_rating < 1):
                                echo '<i class="fa-solid fa-star-half-stroke"></i>';
                            else:
                                echo '<i class="fa-regular fa-star"></i>';
                            endif;
                        endfor;
                        ?>
                    </div>
                    <span class="rating-text"><?= number_format($avg_rating, 1) ?></span>
                    <span class="review-count">(<?= $review_stats['total_reviews'] ?? 0 ?> đánh giá)</span>
                </div>
                
                <div class="sold-count">
                    <i class="fa-solid fa-box"></i> Đã bán: <?= number_format($product['sold_count'] ?? 0) ?>
                </div>
            </div>

            <!-- Brand -->
            <?php if ($product['brand_name']): ?>
            <div class="brand-info">
                <span class="label">Thương hiệu:</span>
                <a href="products.php?brand_id=<?= $product['brand_id'] ?>" class="brand-name">
                    <?= htmlspecialchars($product['brand_name']) ?>
                </a>
                <span class="verified"><i class="fa-solid fa-circle-check"></i> Chính hãng</span>
            </div>
            <?php endif; ?>

            <!-- Price Section -->
            <div class="price-section">
                <?php if ($is_flash_sale): ?>
                <div class="flash-sale-label">
                    <i class="fa-solid fa-bolt"></i> GIÁ LẺ - RẺ NHƯ BÁN BUÔN
                </div>
                <div class="price-row">
                    <div class="sale-price"><?= formatPriceVND($product['sale_price']) ?></div>
                    <div class="original-price"><?= formatPriceVND($original_price) ?></div>
                    <div class="save-badge">Tiết kiệm <?= formatPriceVND($original_price - $product['sale_price']) ?></div>
                </div>
                
                <!-- Flash Sale Timer -->
                <div class="flash-sale-timer" data-end-time="<?= $flash_sale_end ?>">
                    <span class="timer-label">Kết thúc trong:</span>
                    <div class="timer">
                        <div class="time-unit"><span id="hours">00</span><small>Giờ</small></div>
                        <div class="time-unit"><span id="minutes">00</span><small>Phút</small></div>
                        <div class="time-unit"><span id="seconds">00</span><small>Giây</small></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="current-price"><?= formatPriceVND($product['price']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Quantity Selector -->
            <div class="quantity-section">
                <span class="label">Số lượng:</span>
                <div class="quantity-controls">
                    <button class="qty-btn minus" onclick="changeQuantity(-1)">
                        <i class="fa-solid fa-minus"></i>
                    </button>
                    <input type="number" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>" readonly>
                    <button class="qty-btn plus" onclick="changeQuantity(1)">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
                <span class="stock-info">
                    <?php if ($product['stock'] > 0): ?>
                        <i class="fa-solid fa-circle-check"></i> Còn <?= $product['stock'] ?> sản phẩm
                    <?php else: ?>
                        <i class="fa-solid fa-circle-xmark"></i> Hết hàng
                    <?php endif; ?>
                </span>
            </div>

            <!-- Purchase Options -->
            <?php if (isset($_SESSION['user'])): ?>
            <div class="purchase-options">
                <button class="btn-buy-now" onclick="buyNow(<?= $product_id ?>)">
                    <i class="fa-solid fa-shopping-bag"></i>
                    <div>
                        <strong>MUA NGAY</strong>
                        <small>Giao hàng tận nơi hoặc nhận tại cửa hàng</small>
                    </div>
                </button>

                <button class="btn-add-cart" onclick="addToCart(<?= $product_id ?>)">
                    <i class="fa-solid fa-cart-plus"></i>
                    <div>
                        <strong>THÊM VÀO GIỎ HÀNG</strong>
                        <small>Mua thêm sản phẩm khác</small>
                    </div>
                </button>

                <button class="btn-gift-option">
                    <i class="fa-solid fa-gift"></i>
                    <div>
                        <strong>TRẢ GÓP QUA THẺ</strong>
                        <small>Chỉ từ <?= formatPriceVND(($product['sale_price'] ?? $product['price']) / 12) ?>/tháng</small>
                    </div>
                </button>
            </div>
            
            <!-- Compare Button (SINGLE) -->
            <div class="compare-action">
                <button id="compareBtn" 
                        class="btn-compare" 
                        onclick="toggleCompare(<?= $product_id ?>, '<?= htmlspecialchars(addslashes($product['name'])) ?>')">
                    <i class="fa-solid fa-balance-scale"></i>
                    <span>Thêm vào so sánh</span>
                </button>
            </div>
            <?php else: ?>
            <div class="login-prompt">
                <a href="login.php" class="btn-login-prompt">
                    <i class="fa-solid fa-user"></i> Đăng nhập để mua hàng
                </a>
            </div>
            <?php endif; ?>

            <!-- Promotions -->
            <div class="promotions-box">
                <div class="promo-header">
                    <i class="fa-solid fa-gift"></i> Khuyến mãi & Ưu đãi
                </div>
                <div class="promo-list">
                    <div class="promo-item">
                        <i class="fa-solid fa-circle-check"></i>
                        Tặng kèm bàn di chuột trị giá 100.000đ
                    </div>
                    <div class="promo-item">
                        <i class="fa-solid fa-circle-check"></i>
                        Miễn phí giao hàng toàn quốc (COD)
                    </div>
                    <div class="promo-item">
                        <i class="fa-solid fa-circle-check"></i>
                        Bảo hành chính hãng 36 tháng
                    </div>
                    <div class="promo-item">
                        <i class="fa-solid fa-circle-check"></i>
                        1 đổi 1 trong 30 ngày nếu có lỗi phần cứng
                    </div>
                </div>
            </div>

            <!-- Support Info -->
            <div class="support-info">
                <div class="support-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Sản phẩm chính hãng 100%</span>
                </div>
                <div class="support-item">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span>Miễn phí vận chuyển - Giao hàng nhanh</span>
                </div>
                <div class="support-item">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Đổi trả dễ dàng - Hoàn tiền 100%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== RELATED PRODUCTS ===== -->
    <?php if (!empty($related_products)): ?>
    <div class="related-products">
        <h2 class="section-title">SẢN PHẨM TƯƠNG TỰ</h2>
        <div class="products-grid">
            <?php foreach ($related_products as $p): ?>
            <div class="product-card">
                <a href="product_detail.php?id=<?= $p['product_id'] ?>">
                    <div class="product-image">
                        <img src="../<?= getProductImagePath($p['main_image']) ?>" 
                             alt="<?= htmlspecialchars($p['name']) ?>"
                             onerror="this.src='../uploads/img/no-image.png'">
                    </div>
                    <div class="product-info-card">
                        <h3 class="product-name-card"><?= htmlspecialchars($p['name']) ?></h3>
                        <p class="product-brand"><?= htmlspecialchars($p['brand_name'] ?? 'No brand') ?></p>
                        <p class="product-price"><?= formatPriceVND($p['price']) ?></p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ===== COMPARE BAR (Fixed Bottom) ===== -->
<div id="compareBar">
    <div class="compare-bar-content">
        <div class="compare-bar-left">
            <strong>
                <i class="fa-solid fa-balance-scale"></i> 
                Đã chọn <span id="compareCount">0</span>/4 sản phẩm
            </strong>
            <div id="compareProductsList"></div>
        </div>
        <div class="compare-bar-right">
            <button class="btn-clear" onclick="clearCompareList()">
                <i class="fa-solid fa-trash-alt"></i> Xóa tất cả
            </button>
            <button class="btn-compare-now" onclick="goToCompare()">
                <i class="fa-solid fa-exchange-alt"></i> So sánh ngay
            </button>
        </div>
    </div>
</div>

<!-- ===== FOOTER ===== -->
<footer>
    <p>© <?= date('Y') ?> BuildPC.vn — Máy tính & Linh kiện chính hãng</p>
</footer>

<!-- ===== AUDIO SOUND ===== -->
<audio id="tingSound" preload="auto">
  <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<script>
// ===== GLOBAL CONFIG =====
window.PRODUCT_CONFIG = {
  CSRF_TOKEN: <?= json_encode($csrf) ?>,
  PRODUCT_ID: <?= $product_id ?>,
  MAX_STOCK: <?= $product['stock'] ?>,
  IS_FLASH_SALE: <?= json_encode($is_flash_sale) ?>,
  FLASH_SALE_END: <?= json_encode($flash_sale_end) ?>
};

// ===== COMPARE PRODUCTS =====
let compareList = [];

function initCompareList() {
  const saved = localStorage.getItem('compareList');
  compareList = saved ? JSON.parse(saved) : [];
  updateCompareBar();
  updateCompareButton();
}

function toggleCompare(productId, productName) {
  const btn = document.getElementById('compareBtn');
  const index = compareList.findIndex(item => item.id === productId);
  
  if (index > -1) {
    compareList.splice(index, 1);
    btn.classList.remove('active');
    btn.innerHTML = '<i class="fa-solid fa-balance-scale"></i><span>Thêm vào so sánh</span>';
    showNotification('Đã bỏ khỏi danh sách so sánh', 'info');
  } else {
    if (compareList.length >= 4) {
      showNotification('Chỉ có thể so sánh tối đa 4 sản phẩm', 'warning');
      return;
    }
    compareList.push({ id: productId, name: productName });
    btn.classList.add('active');
    btn.innerHTML = '<i class="fa-solid fa-check"></i><span>Đã thêm vào so sánh</span>';
    showNotification('Đã thêm vào danh sách so sánh', 'success');
  }
  
  localStorage.setItem('compareList', JSON.stringify(compareList));
  updateCompareBar();
}

function updateCompareBar() {
  const compareBar = document.getElementById('compareBar');
  const compareCount = document.getElementById('compareCount');
  const compareProductsList = document.getElementById('compareProductsList');
  
  if (compareList.length > 0) {
    compareBar.style.display = 'block';
    compareCount.textContent = compareList.length;
    compareProductsList.innerHTML = '';
    
    compareList.forEach(product => {
      const item = document.createElement('div');
      item.className = 'compare-product-item';
      item.innerHTML = `
        <span>${product.name}</span>
        <button class="btn-remove-compare" onclick="removeFromCompare(${product.id})">
          <i class="fa-solid fa-times"></i>
        </button>
      `;
      compareProductsList.appendChild(item);
    });
  } else {
    compareBar.style.display = 'none';
  }
}

function updateCompareButton() {
  const btn = document.getElementById('compareBtn');
  const currentProductId = <?= $product_id ?>;
  
  if (btn) {
    if (compareList.find(item => item.id === currentProductId)) {
      btn.classList.add('active');
      btn.innerHTML = '<i class="fa-solid fa-check"></i><span>Đã thêm vào so sánh</span>';
    } else {
      btn.classList.remove('active');
      btn.innerHTML = '<i class="fa-solid fa-balance-scale"></i><span>Thêm vào so sánh</span>';
    }
  }
}

function removeFromCompare(productId) {
  compareList = compareList.filter(item => item.id !== productId);
  localStorage.setItem('compareList', JSON.stringify(compareList));
  updateCompareBar();
  updateCompareButton();
  showNotification('Đã xóa sản phẩm khỏi danh sách', 'info');
}

function clearCompareList() {
  if (confirm('Bạn có chắc muốn xóa tất cả sản phẩm khỏi danh sách so sánh?')) {
    compareList = [];
    localStorage.removeItem('compareList');
    updateCompareBar();
    updateCompareButton();
    showNotification('Đã xóa tất cả sản phẩm', 'info');
  }
}

function goToCompare() {
  if (compareList.length < 2) {
    showNotification('Vui lòng chọn ít nhất 2 sản phẩm để so sánh', 'warning');
    return;
  }
  const ids = compareList.map(item => item.id).join(',');
  window.location.href = `product_compare.php?ids=${ids}`;
}

function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  
  const icons = {
    success: 'check-circle',
    warning: 'exclamation-circle',
    info: 'info-circle',
    error: 'times-circle'
  };
  
  notification.innerHTML = `
    <i class="fa-solid fa-${icons[type] || icons.info}"></i>
    <span>${message}</span>
  `;
  
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

function changeQuantity(amount) {
  const input = document.getElementById('quantity');
  let value = parseInt(input.value) + amount;
  if (value < 1) value = 1;
  if (value > window.PRODUCT_CONFIG.MAX_STOCK) value = window.PRODUCT_CONFIG.MAX_STOCK;
  input.value = value;
}

// ===== ADD TO CART - FIXED WITH SOUND & CORRECT PATH =====
function addToCart(productId) {
  const quantity = document.getElementById('quantity').value;
  
  console.log('🛒 Adding to cart:', {
    productId,
    quantity,
    csrfToken: window.PRODUCT_CONFIG?.CSRF_TOKEN
  });
  
  // ✅ FIXED: Đúng tên file là add_to_cart.php (không phải cart_add.php)
  const cartAddPath = './add_to_cart.php';
  
  console.log('📍 Trying path:', cartAddPath);
  
  fetch(cartAddPath, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `product_id=${productId}&quantity=${quantity}&csrf=${encodeURIComponent(window.PRODUCT_CONFIG.CSRF_TOKEN)}`
  })
  .then(response => {
    console.log('📡 Response status:', response.status);
    console.log('📡 Response URL:', response.url);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.text();
  })
  .then(text => {
    console.log('📨 Response text:', text);
    
    try {
      const data = JSON.parse(text);
      console.log('📨 Parsed JSON:', data);
      
      // ✅ FIX: Kiểm tra 'ok' thay vì 'success'
      if (data.ok) {
        console.log('✅ Success!');
        
        // 🔊 Phát âm thanh
        playAddToCartSound();
        
        showNotification(' Đã thêm sản phẩm vào giỏ hàng!', 'success');
        
        // Cập nhật cart-count
        const cartCountEl = document.querySelector('.cart-count');
        if (cartCountEl) {
          let currentCount = parseInt(cartCountEl.textContent) || 0;
          cartCountEl.textContent = currentCount + parseInt(quantity);
        } else {
          const cartLink = document.querySelector('.cart-link');
          if (cartLink) {
            const span = document.createElement('span');
            span.className = 'cart-count';
            span.textContent = quantity;
            cartLink.appendChild(span);
          }
        }
      } else {
        console.log('❌ Error:', data.message);
        showNotification('❌ ' + (data.message || 'Có lỗi xảy ra'), 'error');
      }
    } catch (e) {
      console.error('❌ JSON Parse Error:', e);
      console.error('Response:', text);
      showNotification('❌ Lỗi server: Response không hợp lệ', 'error');
    }
  })
  .catch(error => {
    console.error('❌ Fetch Error:', error);
    showNotification('❌ Không tìm thấy file add_to_cart.php. Kiểm tra đường dẫn!', 'error');
  });
}

// 🔊 FUNCTION PHÁT ÂM THANH (Web Audio API - chắc chắn có tiếng)
function playAddToCartSound() {
  try {
    // Cách 1: Thử dùng file âm thanh
    const sound = document.getElementById('tingSound');
    if (sound) {
      sound.currentTime = 0;
      sound.play().catch(() => {
        console.log('⚠️ Không thể phát file âm thanh, dùng Web Audio API');
        playWebAudioBeep();
      });
    } else {
      console.log('⚠️ Không tìm thấy element audio, dùng Web Audio API');
      playWebAudioBeep();
    }
  } catch (e) {
    console.log('⚠️ Error:', e.message);
    playWebAudioBeep();
  }
}

// Web Audio API Beep (chắc chắn hoạt động)
function playWebAudioBeep() {
  try {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    
    // Tạo 2 tần số liên tiếp (tiếng "tính" + "tắt")
    const now = audioContext.currentTime;
    
    // Beep 1: 800Hz
    const osc1 = audioContext.createOscillator();
    const gain1 = audioContext.createGain();
    osc1.connect(gain1);
    gain1.connect(audioContext.destination);
    
    gain1.gain.setValueAtTime(0.3, now);
    osc1.frequency.setValueAtTime(800, now);
    gain1.gain.exponentialRampToValueAtTime(0.01, now + 0.08);
    
    osc1.start(now);
    osc1.stop(now + 0.08);
    
    // Beep 2: 1000Hz (cao hơn)
    const osc2 = audioContext.createOscillator();
    const gain2 = audioContext.createGain();
    osc2.connect(gain2);
    gain2.connect(audioContext.destination);
    
    gain2.gain.setValueAtTime(0.2, now + 0.1);
    osc2.frequency.setValueAtTime(1000, now + 0.1);
    gain2.gain.exponentialRampToValueAtTime(0.01, now + 0.18);
    
    osc2.start(now + 0.1);
    osc2.stop(now + 0.18);
    
    console.log('🔊 Web Audio Beep phát thành công');
  } catch (e) {
    console.log('⚠️ Không thể phát Web Audio:', e.message);
  }
}

function buyNow(productId) {
  const quantity = document.getElementById('quantity').value;
  window.location.href = `checkout.php?product_id=${productId}&quantity=${quantity}`;
}

document.addEventListener('DOMContentLoaded', function() {
  initCompareList();
});

</script>

</body>
</html>