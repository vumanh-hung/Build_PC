<?php
session_start();
require_once '../db.php'; // File kết nối cơ sở dữ liệu, có hàm getPDO()

$pdo = getPDO(); // Lấy đối tượng PDO từ db.php

/* ============================================================
   ✅ TẠO ADMIN MẶC ĐỊNH (CHỈ KHI CHƯA CÓ TÀI KHOẢN)
   ============================================================ */
$check = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
if ($check == 0) {
    $defaultEmail = 'admin@gmail.com';
    $defaultPass = '123456';
    $hash = password_hash($defaultPass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admins (email, password_hash, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$defaultEmail, $hash]);
}

/* ============================================================
   ✅ HÀM KIỂM TRA ĐĂNG NHẬP
   ============================================================ */
function checkLogin($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin && password_verify($password, $admin['password_hash'])) {
        return $admin;
    }
    return false;
}

/* ============================================================
   ✅ XỬ LÝ ĐĂNG XUẤT
   ============================================================ */
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

/* ============================================================
   ✅ NẾU NGƯỜI DÙNG ĐÃ ĐĂNG NHẬP
   ============================================================ */
if (isset($_SESSION['admin'])) {
    $admin = $_SESSION['admin'];
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title>Bảng điều khiển Quản trị viên</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Xin chào, <?php echo htmlspecialchars($admin['email']); ?>!</h2>
                <a href="?logout=true" class="btn btn-danger">Đăng xuất</a>
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

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Bảng điều khiển quản trị</h4>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Tổng quan</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">Đơn hàng</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">Sản phẩm</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">Người dùng</button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="adminTabsContent">
                        <!-- Tổng quan -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <?php
                            $countOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                            $countProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                            $countUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                            ?>
                            <p>Tổng số đơn hàng: <strong><?php echo $countOrders; ?></strong></p>
                            <p>Tổng số sản phẩm: <strong><?php echo $countProducts; ?></strong></p>
                            <p>Tổng số người dùng: <strong><?php echo $countUsers; ?></strong></p>
                        </div>

                        <!-- Đơn hàng -->
                        <div class="tab-pane fade" id="orders" role="tabpanel">
                            <h5>15 đơn hàng gần nhất</h5>
                            <table class="table table-striped">
                                <thead><tr><th>ID</th><th>Khách hàng</th><th>Tổng tiền</th><th>Ngày đặt</th></tr></thead>
                                <tbody>
                                <?php
                                foreach ($pdo->query("SELECT id, user_id, total_amount, created_at FROM orders ORDER BY created_at DESC LIMIT 15") as $row) {
                                    echo "<tr><td>{$row['id']}</td><td>{$row['user_id']}</td><td>{$row['total_amount']}</td><td>{$row['created_at']}</td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Sản phẩm -->
                        <div class="tab-pane fade" id="products" role="tabpanel">
                            <h5>Danh sách 25 sản phẩm mới nhất</h5>
                            <table class="table table-striped">
                                <thead><tr><th>ID</th><th>Tên</th><th>Giá</th><th>Ngày tạo</th></tr></thead>
                                <tbody>
                                <?php
                                foreach ($pdo->query("SELECT id, name, price, created_at FROM products ORDER BY created_at DESC LIMIT 25") as $row) {
                                    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['price']}</td><td>{$row['created_at']}</td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Người dùng -->
                        <div class="tab-pane fade" id="users" role="tabpanel">
                            <h5>Danh sách 25 người dùng mới nhất</h5>
                            <table class="table table-striped">
                                <thead><tr><th>ID</th><th>Email</th><th>Ngày đăng ký</th></tr></thead>
                                <tbody>
                                <?php
                                foreach ($pdo->query("SELECT id, email, created_at FROM users ORDER BY created_at DESC LIMIT 25") as $row) {
                                    echo "<tr><td>{$row['id']}</td><td>{$row['email']}</td><td>{$row['created_at']}</td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

/* ============================================================
   ✅ XỬ LÝ ĐĂNG NHẬP
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $admin = checkLogin($email, $password);
    if ($admin) {
        $_SESSION['admin'] = $admin;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Sai email hoặc mật khẩu!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập Quản trị viên</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Đăng nhập Quản trị viên</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
                        </form>
                        <div class="mt-3 text-muted small">
                            <p>Email mặc định: <code>admin@example.com</code><br>
                            Mật khẩu: <code>123456</code></p>
                        </div>
                    </div>
                </div>
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
