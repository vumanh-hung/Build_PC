<?php
// page/products.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->query("
        SELECT p.*, c.name AS category_name, b.name AS brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        ORDER BY p.product_id DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Lỗi truy vấn: ' . $e->getMessage());
}

include_once __DIR__ . '/../includes/header.php';
?>

<main class="products-page">
  <div class="container">
    <h1 class="page-title">💻 DANH SÁCH SẢN PHẨM</h1>

    <?php if (empty($products)): ?>
      <p class="no-products">Chưa có sản phẩm nào trong hệ thống.</p>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($products as $p): ?>
          <div class="product-card">
            <div class="image-wrapper">
              <img src="../uploads/<?php echo htmlspecialchars($p['main_image'] ?? 'default.png'); ?>"
                   alt="<?php echo htmlspecialchars($p['name']); ?>">
            </div>

            <div class="info">
              <h3 class="product-name"><?php echo htmlspecialchars($p['name']); ?></h3>
              <p class="brand-cat">
                <?php echo htmlspecialchars($p['brand_name'] ?? 'Thương hiệu'); ?> • 
                <?php echo htmlspecialchars($p['category_name'] ?? 'Danh mục'); ?>
              </p>
              <p class="price"><?php echo number_format($p['price'], 0, ',', '.'); ?> ₫</p>

              <?php if (isset($_SESSION['user'])): ?>
                <a href="../cart.php?action=add&product_id=<?php echo $p['product_id']; ?>" class="btn-buy">
                  🛒 Mua ngay
                </a>
              <?php else: ?>
                <a href="login.php" class="btn-login">Đăng nhập để mua</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<style>
/* ===== GIAO DIỆN TRANG SẢN PHẨM ===== */
body {
  background: linear-gradient(180deg, #f0f6ff, #ffffff);
  font-family: "Segoe UI", Tahoma, sans-serif;
}

.products-page {
  padding: 60px 20px;
  min-height: 100vh;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.page-title {
  text-align: center;
  font-size: 28px;
  font-weight: 700;
  color: #007bff;
  margin-bottom: 35px;
  text-transform: uppercase;
  letter-spacing: 1px;
  position: relative;
}

.page-title::after {
  content: "";
  display: block;
  width: 100px;
  height: 4px;
  background: #007bff;
  margin: 10px auto 0;
  border-radius: 3px;
}

.no-products {
  text-align: center;
  color: #888;
  font-size: 16px;
  margin-top: 60px;
}

/* ===== GRID & CARD ===== */
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: 30px;
}

.product-card {
  background: #fff;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.08);
  overflow: hidden;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.product-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

/* ===== IMAGE ===== */
.image-wrapper {
  background: #f9fbff;
  padding: 20px;
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-bottom: 1px solid #e6efff;
}

.image-wrapper img {
  max-width: 100%;
  max-height: 160px;
  object-fit: contain;
  transition: transform 0.3s ease;
}

.product-card:hover img {
  transform: scale(1.07);
}

/* ===== INFO ===== */
.info {
  padding: 18px;
  text-align: center;
}

.product-name {
  font-size: 17px;
  color: #333;
  margin: 6px 0;
  font-weight: 600;
}

.brand-cat {
  color: #777;
  font-size: 13px;
  margin-bottom: 8px;
}

.price {
  font-size: 18px;
  color: #007bff;
  font-weight: 700;
  margin-bottom: 15px;
}

/* ===== BUTTONS ===== */
.btn-buy, .btn-login {
  display: inline-block;
  padding: 10px 18px;
  border-radius: 8px;
  color: #fff;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-buy {
  background: linear-gradient(90deg, #007bff, #00aaff);
}

.btn-buy:hover {
  background: linear-gradient(90deg, #0066cc, #0099e6);
  transform: scale(1.03);
}

.btn-login {
  background: linear-gradient(90deg, #28a745, #00c851);
}

.btn-login:hover {
  background: linear-gradient(90deg, #1e7e34, #00a63f);
  transform: scale(1.03);
}
</style>
