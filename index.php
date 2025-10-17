<?php
include 'includes/header.php';
require_once 'db.php';

// Lấy sản phẩm mới nhất
$new_products = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    ORDER BY p.product_id DESC 
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Lấy PC Gaming
$gaming_products = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE c.name LIKE '%PC Gaming%' 
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Lấy PC Đồ họa - Làm việc
$work_products = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE c.name LIKE '%Đồ họa%' OR c.name LIKE '%Workstation%' 
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Trang chủ - BuildPC</title>
<style>
body {
  font-family: "Segoe UI", sans-serif;
  margin: 0;
  background: #f7faff;
}

/* Banner */
.banner {
  background: linear-gradient(135deg, #007bff, #00c6ff);
  color: white;
  text-align: center;
  padding: 60px 20px;
}
.banner h1 {
  font-size: 40px;
  margin-bottom: 10px;
}
.banner p {
  font-size: 18px;
}

/* Danh mục nổi bật */
.section {
  max-width: 1200px;
  margin: 60px auto;
  padding: 0 20px;
}
.section h2 {
  font-size: 26px;
  color: #007bff;
  text-align: center;
  margin-bottom: 30px;
}

/* Danh sách sản phẩm */
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 25px;
}
.product {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}
.product:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 15px rgba(0,0,0,0.15);
}
.product img {
  width: 100%;
  height: 200px;
  object-fit: cover;
}
.product-info {
  padding: 15px;
  text-align: center;
}
.product-info h3 {
  font-size: 16px;
  margin: 10px 0;
  color: #333;
  height: 40px;
  overflow: hidden;
}
.product-info p {
  color: #007bff;
  font-weight: bold;
}

/* Nút xem thêm */
.btn {
  display: inline-block;
  background: #007bff;
  color: white;
  padding: 10px 18px;
  border-radius: 6px;
  text-decoration: none;
  transition: 0.2s;
}
.btn:hover {
  background: #0056d2;
}
</style>
</head>

<body>

<!-- Banner -->
<div class="banner">
  <h1>BuildPC.vn</h1>
  <p>Máy tính - Linh kiện - PC Gaming chính hãng, giá tốt mỗi ngày</p>
</div>

<!-- PC Gaming -->
<div class="section">
  <h2>🔥 PC Gaming Nổi Bật</h2>
  <div class="product-grid">
    <?php foreach ($gaming_products as $p): ?>
      <div class="product">
        <img src="uploads/<?php echo htmlspecialchars($p['image']); ?>" alt="">
        <div class="product-info">
          <h3><?php echo htmlspecialchars($p['name']); ?></h3>
          <p><?php echo number_format($p['price']); ?>₫</p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- PC Đồ họa - Làm việc -->
<div class="section">
  <h2>💼 PC Đồ Họa - Làm Việc</h2>
  <div class="product-grid">
    <?php foreach ($work_products as $p): ?>
      <div class="product">
        <img src="uploads/<?php echo htmlspecialchars($p['image']); ?>" alt="">
        <div class="product-info">
          <h3><?php echo htmlspecialchars($p['name']); ?></h3>
          <p><?php echo number_format($p['price']); ?>₫</p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Sản phẩm mới nhất -->
<div class="section">
  <h2>🆕 Sản Phẩm Mới Nhất</h2>
  <div class="product-grid">
    <?php foreach ($new_products as $p): ?>
      <div class="product">
        <img src="uploads/<?php echo htmlspecialchars($p['image']); ?>" alt="">
        <div class="product-info">
          <h3><?php echo htmlspecialchars($p['name']); ?></h3>
          <p><?php echo number_format($p['price']); ?>₫</p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
