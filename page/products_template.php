<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sản phẩm - BuildPC.vn</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/products.css">
</head>
<body <?= $is_build_mode ? 'class="build-mode"' : '' ?>>

<?php
// ✅ Debug: Kiểm tra build mode
if ($is_build_mode) {
    error_log("✅ BUILD MODE ACTIVE");
    error_log("   mode: " . $build_mode);
    error_log("   build_id: " . $build_id);
    error_log("   item_id: " . $item_id);
}
?>

<!-- ===== BUILD MODE BANNER ===== -->
<?php if ($is_build_mode): ?>
<div id="build-mode-banner" class="build-mode-banner active">
  <div class="banner-content">
    <div class="banner-icon">
      <i class="fa fa-tools"></i>
    </div>
    <div class="banner-text">
      <div class="banner-title" id="banner-title">
        <?php if ($build_mode === 'replace'): ?>
          🔄 Đang thay thế linh kiện
        <?php else: ?>
          ➕ Đang thêm linh kiện mới
        <?php endif; ?>
      </div>
      <div class="banner-desc" id="banner-desc">Click vào nút "Chọn sản phẩm này" bên dưới sản phẩm bạn muốn</div>
    </div>
    <button class="banner-close" onclick="cancelBuildMode()">
      <i class="fa fa-times"></i> Hủy & Quay lại
    </button>
  </div>
</div>
<?php endif; ?>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loading">
  <div class="spinner"></div>
  <div class="loading-text" id="loading-text">Đang xử lý...</div>
</div>

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
      <a href="products.php" class="active">Sản phẩm</a>
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

<!-- ===== BANNER ===== -->
<?php if (!$is_build_mode): ?>
<div class="banner">
  <h1>Danh Sách Sản Phẩm</h1>
  <p>Tìm những sản phẩm công nghệ tốt nhất theo nhu cầu của bạn</p>
</div>
<?php endif; ?>

<!-- ===== MAIN CONTENT ===== -->
<div class="container">
  <h1 class="page-title">💻 Sản Phẩm</h1>

  <!-- ===== SEARCH & FILTER ===== -->
  <form method="GET" class="search-bar">
    <?php if ($is_build_mode): ?>
      <input type="hidden" name="mode" value="<?= escape($build_mode) ?>">
      <input type="hidden" name="build_id" value="<?= escape($build_id) ?>">
      <?php if ($item_id): ?>
        <input type="hidden" name="item_id" value="<?= escape($item_id) ?>">
      <?php endif; ?>
    <?php endif; ?>
    
    <input type="text" name="keyword" placeholder="Tìm sản phẩm..." value="<?= htmlspecialchars($keyword) ?>">
    
    <select name="category_id">
      <option value="">-- Danh mục --</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= $c['category_id'] ?>" <?= ($category_id == $c['category_id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="brand_id">
      <option value="">-- Thương hiệu --</option>
      <?php foreach ($brands as $b): ?>
        <option value="<?= $b['brand_id'] ?>" <?= ($brand_id == $b['brand_id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($b['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <input type="number" name="min_price" placeholder="Giá từ..." value="<?= htmlspecialchars($min_price > 0 ? $min_price : '') ?>">
    <input type="number" name="max_price" placeholder="Giá đến..." value="<?= htmlspecialchars($max_price > 0 ? $max_price : '') ?>">

    <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</button>
  </form>

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
      <?php renderProducts($products, $csrf, isLoggedIn(), $is_build_mode); ?>
    </div>
  <?php endif; ?>
</div>

<!-- ===== REVIEWS SECTION (chỉ hiện khi không ở build mode) ===== -->
<?php if (!$is_build_mode): ?>
<div class="reviews-section">
  <div class="reviews-header">
    <h2>⭐ Đánh Giá Từ Khách Hàng</h2>
    <div style="display: flex; gap: 10px;">
      <button class="btn-write-review" onclick="openReviewModal()" title="Viết đánh giá">
        <i class="fa-solid fa-pen"></i> Viết đánh giá
      </button>
      <a href="product-reviews.php" class="btn-view-all-reviews">Xem Tất Cả →</a>
    </div>
  </div>

  <?php if ($review_stats && $review_stats['total_reviews'] > 0): ?>
    <div class="reviews-stats">
      <div class="rating-summary">
        <div class="rating-value-large"><?= number_format($review_stats['avg_rating'], 1) ?></div>
        <div class="rating-stars-large"><?= renderStarsBadge($review_stats['avg_rating']) ?></div>
        <div class="rating-count-text"><?= $review_stats['total_reviews'] ?> đánh giá</div>
      </div>

      <div class="rating-distribution">
        <?php for ($i = 5; $i >= 1; $i--):
            $count = $review_stats["rating_$i"] ?? 0;
            $percentage = $review_stats['total_reviews'] > 0 ? ($count / $review_stats['total_reviews']) * 100 : 0;
        ?>
            <div class="rating-bar-row">
              <span class="rating-bar-label"><?= $i ?>★</span>
              <div class="rating-bar-track">
                <div class="rating-bar-fill" style="width: <?= $percentage ?>%;"></div>
              </div>
              <span class="rating-bar-count"><?= $count ?></span>
            </div>
        <?php endfor; ?>
      </div>
    </div>

    <div class="reviews-list">
      <?php foreach ($recent_reviews as $review): 
        $images = getReviewImages($pdo, $review['review_id']);
      ?>
        <div class="review-item">
          <div class="review-item-header">
            <div>
              <div class="review-item-author"><?= htmlspecialchars($review['full_name']) ?></div>
              <div class="review-item-date"><?= date('d/m/Y', strtotime($review['created_at'])) ?></div>
            </div>
            <span class="review-badge">✓ Đã mua</span>
          </div>

          <div class="review-item-rating"><?= renderStarsBadge($review['rating']) ?></div>
          <div class="review-item-title"><?= htmlspecialchars($review['title']) ?></div>
          <div class="review-item-content"><?= htmlspecialchars($review['content']) ?></div>

          <?php if (!empty($images) && count($images) > 0): ?>
            <div class="review-item-images">
              <?php foreach (array_slice($images, 0, 3) as $img): ?>
                <div class="review-item-img">
                  <img src="../<?= htmlspecialchars($img['image_path']) ?>" alt="Review" onerror="this.src='../assets/images/placeholder.jpg'">
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="review-item-footer">
            <span>📦 <?= htmlspecialchars(substr($review['product_name'], 0, 20)) ?>...</span>
            <span>👍 <?= $review['helpful_count'] ?? 0 ?> hữu ích</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="no-reviews">
      <i class="fa-solid fa-star"></i>
      <p>Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
    </div>
  <?php endif; ?>
</div>

<!-- ===== REVIEW MODAL ===== -->
<div id="reviewModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Viết Đánh Giá</h2>
      <button class="modal-close" onclick="closeReviewModal()">×</button>
    </div>

    <?php if ($review_success): ?>
      <div class="success-msg">
        <span>✓</span>
        <span>Đánh giá của bạn đã được gửi thành công! Sẽ được kiểm duyệt trong 24 giờ.</span>
      </div>
    <?php elseif (!empty($review_error)): ?>
      <div class="error-msg">
        <span>⚠️</span>
        <span><?= htmlspecialchars($review_error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="write_review">
      <input type="hidden" name="product_id" id="modalProductId" value="">
      
      <!-- Rating -->
      <div class="form-group">
        <label>Đánh giá <span class="required">*</span></label>
        <div class="rating-input" id="ratingInput">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="button" class="rating-btn <?= $i <= 5 ? 'active' : '' ?>" data-rating="<?= $i ?>" onclick="setRating(<?= $i ?>, event)">★</button>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" value="5" id="ratingValue">
      </div>

      <!-- Title -->
      <div class="form-group">
        <label>Tiêu đề <span class="required">*</span></label>
        <input type="text" name="title" placeholder="Ví dụ: Sản phẩm rất tốt, giao hàng nhanh" maxlength="200" required oninput="updateCount(this, 'titleCount')">
        <div class="char-count"><span id="titleCount">0</span>/200</div>
      </div>

      <!-- Content -->
      <div class="form-group">
        <label>Nội dung <span class="required">*</span></label>
        <textarea name="content" placeholder="Hãy kể chi tiết về sản phẩm này..." maxlength="2000" required oninput="updateCount(this, 'contentCount')"></textarea>
        <div class="char-count"><span id="contentCount">0</span>/2000</div>
      </div>

      <!-- Images -->
      <div class="form-group">
        <label>Thêm ảnh (tùy chọn)</label>
        <div class="upload-area" onclick="document.getElementById('reviewImageInput').click()" ondragover="this.style.background='#f0f7ff'" ondragleave="this.style.background='white'" ondrop="handleImageDrop(event)">
          <div><i class="fa-solid fa-image"></i></div>
          <div>Kéo và thả ảnh hoặc click để chọn</div>
          <small>Tối đa 5 ảnh, mỗi ảnh dưới 5MB (JPG, PNG, WebP)</small>
        </div>
        <input type="file" id="reviewImageInput" name="images[]" multiple accept="image/*" style="display: none;" onchange="previewReviewImages(this.files)">
        <div id="previewImages" class="preview-images"></div>
      </div>

      <!-- Actions -->
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeReviewModal()">Hủy</button>
        <button type="submit" class="btn-submit">✓ Gửi Đánh Giá</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- 🪄 Cart Popup -->
<div id="cart-popup" class="cart-popup">
  <i class="fa-solid fa-check-circle"></i> <span id="popup-text">Đã thêm vào giỏ hàng!</span>
</div>

<!-- 🔊 Audio for notification sound -->
<audio id="tingSound" preload="auto">
  <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<div id="toast" class="toast"></div>

<!-- ===== FOOTER ===== -->
<footer>
  <p>© <?= date('Y') ?> BuildPC.vn — Máy tính & Linh kiện chính hãng</p>
</footer>
<script>
// ===== CONFIG =====
window.PRODUCTS_CONFIG = {
  CSRF_TOKEN: <?= json_encode($csrf ?? '') ?>,
  IS_BUILD_MODE: <?= $is_build_mode ? 'true' : 'false' ?>,
  BUILD_MODE: <?= json_encode($build_mode ?? '') ?>,
  BUILD_ID: <?= intval($build_id ?? 0) ?>,
  ITEM_ID: <?= intval($item_id ?? 0) ?>,
  IS_LOGGED_IN: <?= isset($_SESSION['user']) ? 'true' : 'false' ?>,
  REVIEW_SUCCESS: false
};

// ===== IMMEDIATE CHECK =====
console.log('✅ Config set:', window.PRODUCTS_CONFIG);
console.log('   IS_BUILD_MODE:', window.PRODUCTS_CONFIG.IS_BUILD_MODE);
console.log('   BUILD_MODE:', window.PRODUCTS_CONFIG.BUILD_MODE);
console.log('   BUILD_ID:', window.PRODUCTS_CONFIG.BUILD_ID);
</script>

<!-- Load JS AFTER config -->
<script src="../assets/js/products.js"></script>
</body>
</html>