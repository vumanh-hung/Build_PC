<?php

/**
 * page/product_detail.php - Product Detail Page
 * Hiển thị chi tiết sản phẩm, reviews, specifications
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

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

// ===== CSRF TOKEN =====
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// Page configuration
$pageTitle = htmlspecialchars($product['name']) . ' - BuildPC.vn';
$additionalCSS = ['assets/css/product_detail.css?v=1.0'];

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb-section">
    <div class="container-custom">
        <nav class="breadcrumb-nav">
            <a href="../index.php" class="breadcrumb-item">
                <i class="fa-solid fa-house"></i> Trang chủ
            </a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="products.php" class="breadcrumb-item">Sản phẩm</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="products.php?category_id=<?= $product['category_id'] ?>" class="breadcrumb-item">
                <?= htmlspecialchars($product['category_name']) ?>
            </a>
            <i class="fa-solid fa-chevron-right"></i>
            <span class="breadcrumb-current"><?= htmlspecialchars($product['name']) ?></span>
        </nav>
    </div>
</div>

<!-- Main Product Section -->
<div class="container-custom">
    <div class="product-detail-wrapper">

        <!-- Left: Product Images -->
        <div class="product-gallery">
            <div class="main-image-wrapper">
                <?php if ($is_flash_sale): ?>
                    <div class="flash-sale-badge">
                        <i class="fa-solid fa-bolt"></i> FLASH SALE
                    </div>
                    <div class="discount-badge">-<?= $discount_percent ?>%</div>
                <?php endif; ?>

                <img id="mainProductImage"
                    class="main-product-image"
                    src="../<?= getProductImagePath($product_images[0]['image_path'] ?? $product['main_image']) ?>"
                    alt="<?= htmlspecialchars($product['name']) ?>"
                    onerror="this.src='../uploads/img/no-image.png'">
            </div>

            <?php if (count($product_images) > 1): ?>
                <div class="thumbnail-gallery">
                    <?php foreach ($product_images as $index => $img): ?>
                        <div class="thumbnail-item <?= $index === 0 ? 'active' : '' ?>"
                            data-image="../<?= getProductImagePath($img['image_path']) ?>">
                            <img src="../<?= getProductImagePath($img['image_path']) ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                onerror="this.src='../uploads/img/no-image.png'">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Product Information -->
        <div class="product-details">
            <!-- Product Title -->
            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>

            <!-- Rating & Sales Meta -->
            <div class="product-meta-info">
                <div class="rating-display">
                    <div class="stars-wrapper">
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
                    <span class="rating-number"><?= number_format($avg_rating, 1) ?></span>
                    <span class="review-total">(<?= $review_stats['total_reviews'] ?? 0 ?> đánh giá)</span>
                </div>

                <div class="sales-count">
                    <i class="fa-solid fa-box"></i>
                    <span>Đã bán: <?= number_format($product['sold_count'] ?? 0) ?></span>
                </div>
            </div>

            <!-- Brand Information -->
            <?php if ($product['brand_name']): ?>
                <div class="brand-section">
                    <span class="brand-label">Thương hiệu:</span>
                    <a href="products.php?brand_id=<?= $product['brand_id'] ?>" class="brand-link">
                        <?= htmlspecialchars($product['brand_name']) ?>
                    </a>
                    <span class="verified-badge">
                        <i class="fa-solid fa-circle-check"></i> Chính hãng
                    </span>
                </div>
            <?php endif; ?>

            <!-- Price Display -->
            <div class="pricing-section">
                <?php if ($is_flash_sale): ?>
                    <div class="flash-sale-header">
                        <i class="fa-solid fa-bolt"></i> GIÁ LẺ - RẺ NHƯ BÁN BUÔN
                    </div>
                    <div class="price-display">
                        <div class="sale-price-main"><?= formatPriceVND($product['sale_price']) ?></div>
                        <div class="original-price-strike"><?= formatPriceVND($original_price) ?></div>
                        <div class="savings-badge">
                            Tiết kiệm <?= formatPriceVND($original_price - $product['sale_price']) ?>
                        </div>
                    </div>

                    <!-- Flash Sale Countdown -->
                    <div class="flash-sale-countdown" data-end-time="<?= $flash_sale_end ?>">
                        <span class="countdown-label">Kết thúc trong:</span>
                        <div class="countdown-timer">
                            <div class="timer-block">
                                <span class="timer-value" id="flashHours">00</span>
                                <small class="timer-unit">Giờ</small>
                            </div>
                            <div class="timer-block">
                                <span class="timer-value" id="flashMinutes">00</span>
                                <small class="timer-unit">Phút</small>
                            </div>
                            <div class="timer-block">
                                <span class="timer-value" id="flashSeconds">00</span>
                                <small class="timer-unit">Giây</small>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="regular-price"><?= formatPriceVND($product['price']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Quantity Selector -->
            <div class="quantity-selector">
                <label class="quantity-label">Số lượng:</label>
                <div class="quantity-input-group">
                    <button class="qty-decrease" type="button">
                        <i class="fa-solid fa-minus"></i>
                    </button>
                    <input type="number"
                        id="productQuantity"
                        class="qty-input"
                        value="1"
                        min="1"
                        max="<?= $product['stock'] ?>"
                        readonly>
                    <button class="qty-increase" type="button">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
                <span class="stock-status">
                    <?php if ($product['stock'] > 0): ?>
                        <i class="fa-solid fa-circle-check"></i>
                        Còn <?= $product['stock'] ?> sản phẩm
                    <?php else: ?>
                        <i class="fa-solid fa-circle-xmark"></i>
                        Hết hàng
                    <?php endif; ?>
                </span>
            </div>

            <!-- Action Buttons -->
            <?php if (isset($_SESSION['user']) && $product['stock'] > 0): ?>
                <div class="action-buttons">
                    <button class="btn-primary-action" id="buyNowBtn">
                        <i class="fa-solid fa-shopping-bag"></i>
                        <div class="btn-text">
                            <strong>MUA NGAY</strong>
                            <small>Giao hàng tận nơi hoặc nhận tại cửa hàng</small>
                        </div>
                    </button>

                    <button class="btn-secondary-action" id="addToCartBtn">
                        <i class="fa-solid fa-cart-plus"></i>
                        <div class="btn-text">
                            <strong>THÊM VÀO GIỎ HÀNG</strong>
                            <small>Mua thêm sản phẩm khác</small>
                        </div>
                    </button>

                    <button class="btn-installment-action">
                        <i class="fa-solid fa-gift"></i>
                        <div class="btn-text">
                            <strong>TRẢ GÓP QUA THẺ</strong>
                            <small>Chỉ từ <?= formatPriceVND(($product['sale_price'] ?? $product['price']) / 12) ?>/tháng</small>
                        </div>
                    </button>
                </div>

                <!-- Compare Button -->
                <div class="compare-section">
                    <button id="compareToggleBtn" class="btn-compare-toggle">
                        <i class="fa-solid fa-balance-scale"></i>
                        <span>Thêm vào so sánh</span>
                    </button>
                </div>
            <?php else: ?>
                <div class="login-required">
                    <a href="login.php" class="btn-login-required">
                        <i class="fa-solid fa-user"></i> Đăng nhập để mua hàng
                    </a>
                </div>
            <?php endif; ?>

            <!-- Promotions Box -->
            <div class="promotions-list">
                <div class="promotions-header">
                    <i class="fa-solid fa-gift"></i> Khuyến mãi & Ưu đãi
                </div>
                <div class="promo-items">
                    <div class="promo-single">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Tặng kèm bàn di chuột trị giá 100.000đ</span>
                    </div>
                    <div class="promo-single">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Miễn phí giao hàng toàn quốc (COD)</span>
                    </div>
                    <div class="promo-single">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Bảo hành chính hãng 36 tháng</span>
                    </div>
                    <div class="promo-single">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>1 đổi 1 trong 30 ngày nếu có lỗi phần cứng</span>
                    </div>
                </div>
            </div>

            <!-- Support Features -->
            <div class="support-features">
                <div class="feature-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Sản phẩm chính hãng 100%</span>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span>Miễn phí vận chuyển - Giao hàng nhanh</span>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Đổi trả dễ dàng - Hoàn tiền 100%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Information Tabs -->
    <div class="product-tabs-section">
        <div class="tabs-header">
            <button class="tab-btn active" data-tab="description">
                <i class="fa-solid fa-file-lines"></i>
                Giới thiệu sản phẩm
            </button>
            <button class="tab-btn" data-tab="specifications">
                <i class="fa-solid fa-list-check"></i>
                Thông số kỹ thuật
            </button>
            <button class="tab-btn" data-tab="reviews">
                <i class="fa-solid fa-star"></i>
                Đánh giá (<?= $review_stats['total_reviews'] ?? 0 ?>)
            </button>
        </div>

        <!-- Description Tab -->
        <div class="tab-panel active" id="description-panel">
            <div class="description-wrapper">
                <?php if ($product['description']): ?>
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                <?php else: ?>
                    <p class="no-content">Thông tin chi tiết đang được cập nhật...</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Specifications Tab -->
        <div class="tab-panel" id="specifications-panel">
            <?php if (!empty($specifications)): ?>
                <table class="specifications-table">
                    <tbody>
                        <?php foreach ($specifications as $spec): ?>
                            <tr>
                                <td class="spec-label"><?= htmlspecialchars($spec['spec_name']) ?></td>
                                <td class="spec-data"><?= htmlspecialchars($spec['spec_value']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-specs-message">
                    <i class="fa-solid fa-circle-info"></i>
                    <p>Thông số kỹ thuật đang được cập nhật</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reviews Tab -->
        <div class="tab-panel" id="reviews-panel">
            <?php if ($review_stats['total_reviews'] > 0): ?>
                <!-- Review Summary -->
                <div class="reviews-summary">
                    <div class="rating-overview-box">
                        <div class="overall-rating">
                            <div class="rating-score"><?= number_format($review_stats['avg_rating'], 1) ?></div>
                            <div class="rating-stars-large">
                                <?php
                                for ($i = 1; $i <= 5; $i++):
                                    echo ($i <= $review_stats['avg_rating'])
                                        ? '<i class="fa-solid fa-star"></i>'
                                        : '<i class="fa-regular fa-star"></i>';
                                endfor;
                                ?>
                            </div>
                            <div class="total-reviews"><?= $review_stats['total_reviews'] ?> đánh giá</div>
                        </div>

                        <div class="rating-breakdown">
                            <?php for ($i = 5; $i >= 1; $i--):
                                $count = $review_stats["rating_$i"] ?? 0;
                                $percent = $review_stats['total_reviews'] > 0
                                    ? ($count / $review_stats['total_reviews']) * 100
                                    : 0;
                            ?>
                                <div class="rating-bar-item">
                                    <span class="star-label">
                                        <?= $i ?> <i class="fa-solid fa-star"></i>
                                    </span>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= $percent ?>%"></div>
                                    </div>
                                    <span class="rating-count"><?= $count ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['user'])): ?>
                        <button class="btn-write-review" onclick="openReviewModal()">
                            <i class="fa-solid fa-pen"></i> Viết đánh giá
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Reviews List -->
                <div class="reviews-container">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-top">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <?php if ($review['avatar']): ?>
                                            <img src="../<?= htmlspecialchars($review['avatar']) ?>"
                                                alt="<?= htmlspecialchars($review['full_name']) ?>">
                                        <?php else: ?>
                                            <i class="fa-solid fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="reviewer-details">
                                        <div class="reviewer-name"><?= htmlspecialchars($review['full_name']) ?></div>
                                        <div class="review-time">
                                            <?= date('d/m/Y H:i', strtotime($review['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?= ($i <= $review['rating'])
                                            ? '<i class="fa-solid fa-star"></i>'
                                            : '<i class="fa-regular fa-star"></i>' ?>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <?php if ($review['title']): ?>
                                <div class="review-heading"><?= htmlspecialchars($review['title']) ?></div>
                            <?php endif; ?>

                            <div class="review-text"><?= nl2br(htmlspecialchars($review['content'])) ?></div>

                            <div class="review-actions">
                                <button class="btn-helpful-vote" onclick="markHelpful(<?= $review['review_id'] ?>)">
                                    <i class="fa-regular fa-thumbs-up"></i>
                                    Hữu ích (<?= $review['helpful_count'] ?? 0 ?>)
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-reviews-state">
                    <i class="fa-regular fa-star"></i>
                    <p>Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
                    <?php if (isset($_SESSION['user'])): ?>
                        <button class="btn-write-review" onclick="openReviewModal()">
                            <i class="fa-solid fa-pen"></i> Viết đánh giá ngay
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <div class="related-section">
            <h2 class="section-heading">
                <i class="fa-solid fa-layer-group"></i>
                SẢN PHẨM TƯƠNG TỰ
            </h2>
            <div class="related-grid">
                <?php foreach ($related_products as $p): ?>
                    <div class="related-card">
                        <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="related-link">
                            <div class="related-image">
                                <img src="../<?= getProductImagePath($p['main_image']) ?>"
                                    alt="<?= htmlspecialchars($p['name']) ?>"
                                    onerror="this.src='../uploads/img/no-image.png'">
                            </div>
                            <div class="related-info">
                                <h3 class="related-name"><?= htmlspecialchars($p['name']) ?></h3>
                                <p class="related-brand"><?= htmlspecialchars($p['brand_name'] ?? 'No brand') ?></p>
                                <p class="related-price"><?= formatPriceVND($p['price']) ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Compare Bar (Fixed Bottom) -->
<div id="compareFixedBar" class="compare-fixed-bar">
    <div class="compare-bar-inner">
        <div class="compare-bar-info">
            <strong>
                <i class="fa-solid fa-balance-scale"></i>
                Đã chọn <span id="compareCounter">0</span>/4 sản phẩm
            </strong>
            <div id="compareItemsList"></div>
        </div>
        <div class="compare-bar-actions">
            <button class="btn-clear-compare" id="clearCompareBtn">
                <i class="fa-solid fa-trash-alt"></i> Xóa tất cả
            </button>
            <button class="btn-go-compare" id="goCompareBtn">
                <i class="fa-solid fa-exchange-alt"></i> So sánh ngay
            </button>
        </div>
    </div>
</div>

<!-- Audio Sound Effect -->
<audio id="cartSound" preload="auto">
    <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<!-- JavaScript Configuration -->
<script>
    window.PRODUCT_DATA = {
        PRODUCT_ID: <?= $product_id ?>,
        PRODUCT_NAME: <?= json_encode($product['name']) ?>,
        MAX_STOCK: <?= $product['stock'] ?>,
        IS_FLASH_SALE: <?= json_encode($is_flash_sale) ?>,
        FLASH_SALE_END: <?= json_encode($flash_sale_end) ?>,
        CSRF_TOKEN: <?= json_encode($csrf) ?>
    };
</script>

<script src="../assets/js/product_detail.js?v=1.0"></script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>