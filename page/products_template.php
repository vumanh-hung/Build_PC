<?php
/**
 * products_template.php - Product Page Template
 * Chỉ chứa HTML content, tái sử dụng header/footer
 */

// Include Header
include __DIR__ . '/../includes/header.php';
?>

<!-- ===== BUILD MODE BANNER ===== -->
<?php if ($is_build_mode): ?>
<div id="build-mode-banner" class="build-mode-banner active">
    <div class="banner-content">
        <div class="banner-icon">
            <i class="fa fa-tools"></i>
        </div>
        <div class="banner-text">
            <div class="banner-title">
                <?= $build_mode === 'replace' ? '🔄 Đang thay thế linh kiện' : '➕ Đang thêm linh kiện mới' ?>
            </div>
            <div class="banner-desc">Click vào nút "Chọn sản phẩm này" bên dưới sản phẩm bạn muốn</div>
        </div>
        <button class="banner-close" onclick="cancelBuildMode()">
            <i class="fa fa-times"></i> Hủy & Quay lại
        </button>
    </div>
</div>
<?php endif; ?>

<!-- ===== LOADING OVERLAY ===== -->
<div class="loading-overlay" id="loading">
    <div class="spinner"></div>
    <div class="loading-text" id="loading-text">Đang xử lý...</div>
</div>

<!-- ===== PAGE BANNER ===== -->
<?php if (!$is_build_mode): ?>
<div class="page-banner">
    <div class="banner-content">
        <h1>Danh Sách Sản Phẩm</h1>
        <p>Tìm những sản phẩm công nghệ tốt nhất theo nhu cầu của bạn</p>
    </div>
</div>
<?php endif; ?>

<!-- ===== MAIN CONTENT ===== -->
<div class="container">
    <h1 class="page-title">💻 Sản Phẩm</h1>

    <!-- ===== SEARCH & FILTER ===== -->
    <?php renderSearchForm($filters, $categories, $brands, $is_build_mode, $build_mode, $build_id, $item_id); ?>

    <!-- ===== PRODUCT LIST ===== -->
    <?php if (empty($products)): ?>
        <div class="product-grid">
            <div class="no-products">
                <i class="fa-solid fa-magnifying-glass"></i>
                <p>Không tìm thấy sản phẩm nào phù hợp.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php renderProducts($products, $is_build_mode, $build_mode, $build_id, $item_id); ?>
        </div>
    <?php endif; ?>
</div>

<!-- ===== REVIEWS SECTION ===== -->
<?php if (!$is_build_mode): ?>
    <?php renderReviewsSection($review_stats, $recent_reviews); ?>
    <?php renderReviewModal($review_success, $review_error); ?>
<?php endif; ?>

<!-- ===== CART POPUP ===== -->
<div id="cart-popup" class="cart-popup">
    <i class="fa-solid fa-check-circle"></i>
    <span id="popup-text">Đã thêm vào giỏ hàng!</span>
</div>

<!-- ===== AUDIO NOTIFICATION ===== -->
<audio id="tingSound" preload="auto">
    <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<!-- ===== TOAST NOTIFICATION ===== -->
<div id="toast" class="toast"></div>

<!-- ===== PAGE SPECIFIC SCRIPTS ===== -->
<script>
// Products Page Configuration
window.PRODUCTS_CONFIG = {
    CSRF_TOKEN: <?= json_encode($csrf) ?>,
    IS_BUILD_MODE: <?= $is_build_mode ? 'true' : 'false' ?>,
    BUILD_MODE: <?= json_encode($build_mode) ?>,
    BUILD_ID: <?= $build_id ?>,
    ITEM_ID: <?= $item_id ?>,
    IS_LOGGED_IN: <?= isLoggedIn() ? 'true' : 'false' ?>,
    REVIEW_SUCCESS: <?= $review_success ? 'true' : 'false' ?>
};

console.log('✅ Products Config Loaded:', window.PRODUCTS_CONFIG);
</script>

<?php
// Include Footer
include __DIR__ . '/../includes/footer.php';
?>