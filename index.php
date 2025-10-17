<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>BuildPC Configurator</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/main.js" defer></script>
</head>
<body>
  <div class="container">
    <h1>🧠 Build Your PC</h1>

    <div id="product-list"></div>

    <button id="reload-btn">Tải lại sản phẩm</button>
  </div>
</body>
</html>
<?php
// Kết nối database
require_once __DIR__ . '/db.php';
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/banner.php'; ?>

<main class="main-content">
  <div class="container">
    <div class="content-layout">
      
      <!-- ===== Sidebar (Danh mục, Thương hiệu) ===== -->
      <?php include __DIR__ . '/includes/sidebar.php'; ?>

      <!-- ===== Nội dung chính ===== -->
      <section class="main-section">
        <h1>🧠 Build Your PC</h1>

        <div id="product-list" class="product-list">
          <?php
          try {
              $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 8");
              while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  echo "
                  <div class='product-card'>
                      <img src='assets/images/{$product['main_image']}' alt='{$product['name']}'>
                      <h3>{$product['name']}</h3>
                      <p class='price'>" . number_format($product['price'], 0, ',', '.') . " đ</p>
                      <a href='page/product_detail.php?id={$product['product_id']}' class='btn-view'>Xem chi tiết</a>
                  </div>
                  ";
              }
          } catch (PDOException $e) {
              echo "<p class='error'>Lỗi tải sản phẩm: " . htmlspecialchars($e->getMessage()) . "</p>";
          }
          ?>
        </div>

        <div class="reload-btn-wrap">
          <button id="reload-btn" onclick="window.location.reload()">Tải lại sản phẩm</button>
        </div>
      </section>

    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php
require_once 'includes/functions.php';
$products = getProducts();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>BuildPC Configurator</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/main.js" defer></script>
</head>
<body>

  <?php include 'includes/header.php'; ?>
  <?php include 'includes/navbar.php'; ?>

  <div class="banner">
    <div class="banner-text">
      <h1>Xây dựng PC theo cách của bạn 💻</h1>
      <p>Chọn từng linh kiện để tạo nên dàn máy tối ưu hiệu năng</p>
      <a href="#" class="btn-primary">Bắt đầu ngay</a>
    </div>
  </div>

  <main class="container">
    <h2 class="section-title">Sản phẩm mới nhất</h2>
    <div class="product-grid">
      <?php foreach ($products as $p): ?>
        <div class="product-card">
          <img src="uploads/<?php echo $p['image']; ?>" alt="<?php echo $p['name']; ?>">
          <h3><?php echo $p['name']; ?></h3>
          <p class="price"><?php echo number_format($p['price']); ?> đ</p>
          <button class="btn">Thêm vào giỏ</button>
        </div>
      <?php endforeach; ?>
    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

</body>
</html>
<?php include 'includes/header.php'; ?>

<h1>Chào mừng đến với BuildPC.vn</h1>
<p>Trang chủ...</p>

<?php include 'includes/footer.php'; ?>
