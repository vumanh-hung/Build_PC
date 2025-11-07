<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['name']) ?> - BuildPC.vn</title>
<meta name="description" content="<?= htmlspecialchars(substr($product['description'] ?? '', 0, 160)) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/product_detail.css">
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

  <!-- ===== TABS: DESCRIPTION, SPECS, REVIEWS ===== -->
  <div class="product-tabs">
    <div class="tab-headers">
      <button class="tab-header active" onclick="switchTab('description')">
        Giới thiệu sản phẩm
      </button>
      <button class="tab-header" onclick="switchTab('specifications')">
        Thông số kỹ thuật
      </button>
      <button class="tab-header" onclick="switchTab('reviews')">
        Đánh giá (<?= $review_stats['total_reviews'] ?? 0 ?>)
      </button>
    </div>

    <!-- Description Tab -->
    <div id="description-tab" class="tab-content active">
      <div class="description-content">
        <?php if ($product['description']): ?>
          <?= nl2br(htmlspecialchars($product['description'])) ?>
        <?php else: ?>
          <p>Thông tin chi tiết đang được cập nhật...</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Specifications Tab -->
    <div id="specifications-tab" class="tab-content">
      <?php if (!empty($specifications)): ?>
      <table class="specs-table">
        <?php foreach ($specifications as $spec): ?>
        <tr>
          <td class="spec-name"><?= htmlspecialchars($spec['spec_name']) ?></td>
          <td class="spec-value"><?= htmlspecialchars($spec['spec_value']) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
      <div class="no-specs">
        <i class="fa-solid fa-circle-info"></i>
        <p>Thông số kỹ thuật đang được cập nhật</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Reviews Tab -->
    <div id="reviews-tab" class="tab-content">
      <!-- Review Summary -->
      <?php if ($review_stats['total_reviews'] > 0): ?>
      <div class="review-summary">
        <div class="rating-overview">
          <div class="average-rating">
            <div class="rating-number"><?= number_format($review_stats['avg_rating'], 1) ?></div>
            <div class="rating-stars">
              <?php
              for ($i = 1; $i <= 5; $i++):
                echo ($i <= $review_stats['avg_rating']) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
              endfor;
              ?>
            </div>
            <div class="rating-count"><?= $review_stats['total_reviews'] ?> đánh giá</div>
          </div>

          <div class="rating-bars">
            <?php for ($i = 5; $i >= 1; $i--): 
              $count = $review_stats["rating_$i"] ?? 0;
              $percent = $review_stats['total_reviews'] > 0 ? ($count / $review_stats['total_reviews']) * 100 : 0;
            ?>
            <div class="rating-bar-row">
              <span class="stars"><?= $i ?> <i class="fa-solid fa-star"></i></span>
              <div class="bar-track">
                <div class="bar-fill" style="width: <?= $percent ?>%"></div>
              </div>
              <span class="count"><?= $count ?></span>
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

      <!-- Review List -->
      <div class="review-list">
        <?php foreach ($reviews as $review): ?>
        <div class="review-item">
          <div class="review-header">
            <div class="user-info">
              <div class="user-avatar">
                <?php if ($review['avatar']): ?>
                  <img src="../<?= htmlspecialchars($review['avatar']) ?>" alt="<?= htmlspecialchars($review['full_name']) ?>">
                <?php else: ?>
                  <i class="fa-solid fa-user"></i>
                <?php endif; ?>
              </div>
              <div>
                <div class="user-name"><?= htmlspecialchars($review['full_name']) ?></div>
                <div class="review-date"><?= date('d/m/Y H:i', strtotime($review['created_at'])) ?></div>
              </div>
            </div>
            <div class="review-rating">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <?= ($i <= $review['rating']) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>' ?>
              <?php endfor; ?>
            </div>
          </div>
          
          <?php if ($review['title']): ?>
          <div class="review-title"><?= htmlspecialchars($review['title']) ?></div>
          <?php endif; ?>
          
          <div class="review-content"><?= nl2br(htmlspecialchars($review['content'])) ?></div>
          
          <?php
          // Get review images
          $stmt_imgs = $pdo->prepare("SELECT * FROM review_images WHERE review_id = :review_id");
          $stmt_imgs->execute([':review_id' => $review['review_id']]);
          $review_images = $stmt_imgs->fetchAll(PDO::FETCH_ASSOC);
          
          if (!empty($review_images)):
          ?>
          <div class="review-images">
            <?php foreach ($review_images as $img): ?>
            <div class="review-image">
              <img src="../<?= htmlspecialchars($img['image_path']) ?>" 
                   alt="Review image"
                   onclick="openImageModal(this.src)">
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          
          <div class="review-footer">
            <button class="btn-helpful" onclick="markHelpful(<?= $review['review_id'] ?>)">
              <i class="fa-regular fa-thumbs-up"></i> 
              Hữu ích (<?= $review['helpful_count'] ?? 0 ?>)
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="no-reviews">
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

<!-- Popups & Modals -->
<div id="cart-popup" class="cart-popup">
  <i class="fa-solid fa-check-circle"></i> <span id="popup-text">Đã thêm vào giỏ hàng!</span>
</div>

<div id="image-modal" class="image-modal" onclick="closeImageModal()">
  <span class="close">&times;</span>
  <img class="modal-image" id="modalImage">
</div>

<audio id="tingSound" preload="auto">
  <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<!-- ===== FOOTER ===== -->
<footer>
  <p>© <?= date('Y') ?> BuildPC.vn — Máy tính & Linh kiện chính hãng</p>
</footer>

<script src="../assets/js/product_detail.js"></script>
<script>
window.PRODUCT_CONFIG = {
  CSRF_TOKEN: <?= json_encode($csrf) ?>,
  PRODUCT_ID: <?= $product_id ?>,
  MAX_STOCK: <?= $product['stock'] ?>,
  IS_FLASH_SALE: <?= json_encode($is_flash_sale) ?>,
  FLASH_SALE_END: <?= json_encode($flash_sale_end) ?>
};
</script>

</body>
</html>