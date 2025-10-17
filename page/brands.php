<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';

// ✅ Lấy danh sách thương hiệu
$stmt = $pdo->query("SELECT * FROM brands ORDER BY name ASC");
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Nếu có chọn 1 thương hiệu cụ thể
$products = [];
$brand_title = null;
if (isset($_GET['brand_id'])) {
    $brand_id = intval($_GET['brand_id']);

    $stmt = $pdo->prepare("
        SELECT p.product_id, p.name, p.price, p.main_image, b.name AS brand, c.name AS category
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.brand_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$brand_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $brand_name = $pdo->prepare("SELECT name FROM brands WHERE brand_id = ?");
    $brand_name->execute([$brand_id]);
    $brand_title = $brand_name->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Thương hiệu - BuildPC</title>
<style>
body {
  font-family: "Segoe UI", Tahoma, sans-serif;
  background: linear-gradient(135deg, #a2d2ff, #89c2ff);
  margin: 0;
  padding: 0;
}

/* ===== NAVBAR ===== */
.navbar {
  background: #007bff;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 40px;
  flex-wrap: wrap;
}
.navbar .logo {
  font-weight: bold;
  font-size: 22px;
}
.navbar ul {
  list-style: none;
  display: flex;
  margin: 0;
  padding: 0;
}
.navbar li { margin: 0 15px; }
.navbar a {
  color: white;
  text-decoration: none;
  font-weight: 500;
  transition: 0.3s;
}
.navbar a:hover {
  text-decoration: underline;
}
.user a {
  color: #fff;
  font-weight: 600;
}

/* ===== CONTAINER ===== */
.container {
  background: #fff;
  margin: 40px auto;
  padding: 30px;
  border-radius: 16px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.1);
  max-width: 1100px;
}
h2 {
  text-align: center;
  color: #007bff;
  font-size: 26px;
  margin-bottom: 25px;
}

/* ===== BRAND GRID ===== */
.brand-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 25px;
  padding: 10px;
}

.brand-card {
  background: #f8faff;
  border-radius: 14px;
  padding: 25px 15px;
  text-align: center;
  box-shadow: 0 3px 8px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}
.brand-card:hover {
  transform: translateY(-6px) scale(1.02);
  background: linear-gradient(135deg, #e3f2fd, #bbdefb);
  box-shadow: 0 6px 14px rgba(0,0,0,0.15);
}
.brand-card img {
  width: 120px;
  height: 120px;
  object-fit: contain;
  border-radius: 50%;
  background: #fff;
  padding: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  margin-bottom: 12px;
}
.brand-card div {
  font-size: 16px;
  font-weight: 600;
  color: #007bff;
  transition: 0.3s;
}
.brand-card:hover div {
  color: #004aad;
}

/* ===== PRODUCT GRID ===== */
.products {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
  gap: 20px;
  margin-top: 30px;
}
.product-card {
  background: #f8faff;
  border-radius: 12px;
  padding: 15px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: 0.3s;
}
.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.product-card img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  border-radius: 8px;
}
.product-card h4 {
  margin: 10px 0 5px;
  color: #007bff;
}
.product-card p {
  color: #555;
  font-size: 14px;
}
.price {
  color: #dc3545;
  font-weight: bold;
}
.btn {
  display: inline-block;
  margin-top: 8px;
  padding: 8px 14px;
  background: linear-gradient(90deg, #007bff, #00b4ff);
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  transition: 0.3s;
}
.btn:hover {
  background: linear-gradient(90deg, #0069d9, #0099e6);
}

/* ===== FOOTER ===== */
footer {
  background: #007bff;
  color: white;
  text-align: center;
  padding: 15px 0;
  margin-top: 40px;
  font-size: 14px;
}
</style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<div class="navbar">
  <div class="logo">🖥️ BuildPC</div>
  <ul>
    <li><a href="../index.php">Trang chủ</a></li>
    <li><a href="products.php">Sản phẩm</a></li>
    <li><a href="brands.php" style="text-decoration: underline;">Thương hiệu</a></li>
    <li><a href="builds.php">Xây dựng cấu hình</a></li>
    <li><a href="about.php">Giới thiệu</a></li>
    <li><a href="contact.php">Liên hệ</a></li>
  </ul>
  <div class="user">
    <?php if (isset($_SESSION['user'])): ?>
        👋 Xin chào, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong> |
        <a href="logout.php" style="color:#ffcc00;">Đăng xuất</a>
    <?php else: ?>
        <a href="login.php">Đăng nhập</a>
    <?php endif; ?>
  </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="container">
  <h2>🏷️ Thương hiệu nổi bật</h2>

  <div class="brand-list">
    <?php foreach ($brands as $b): ?>
    <div class="brand-card">
      <a href="?brand_id=<?= $b['brand_id'] ?>" style="text-decoration:none;">
        <img src="../uploads/<?= htmlspecialchars($b['slug'] ?: 'default_brand.png') ?>" alt="<?= htmlspecialchars($b['name']) ?>">
        <div><?= htmlspecialchars($b['name']) ?></div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if (!empty($products)): ?>
  <h2 style="margin-top:40px;">🧩 Sản phẩm của thương hiệu: <?= htmlspecialchars($brand_title) ?></h2>
  <div class="products">
    <?php foreach ($products as $p): ?>
    <div class="product-card">
      <img src="../uploads/<?= htmlspecialchars($p['main_image'] ?: 'default.jpg') ?>" alt="<?= htmlspecialchars($p['name']) ?>">
      <h4><?= htmlspecialchars($p['name']) ?></h4>
      <p><?= htmlspecialchars($p['category'] ?? 'Không rõ danh mục') ?></p>
      <p class="price"><?= number_format($p['price'], 0, ',', '.') ?> ₫</p>
      <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="btn">Xem chi tiết</a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<footer>
  © <?= date('Y') ?> BuildPC — Thiết kế & lắp ráp máy tính theo yêu cầu
</footer>
</body>
</html>
