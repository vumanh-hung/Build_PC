<?php
session_start();
require_once '../db.php';

// Lấy danh sách thương hiệu
$stmt = $pdo->query("SELECT * FROM brands ORDER BY name ASC");
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nếu có chọn 1 thương hiệu cụ thể
$products = [];
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

    // Lấy tên thương hiệu hiện tại
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
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f3f7fb;
}
.navbar {
    background: #0d6efd;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 40px;
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
.navbar a { color: white; text-decoration: none; font-weight: 500; }
.navbar a:hover { text-decoration: underline; }

.container {
    width: 90%;
    margin: 30px auto;
}
h2 {
    color: #0d6efd;
    text-align: center;
    margin-bottom: 20px;
}
.brand-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 20px;
}
.brand-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: 0.3s;
}
.brand-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}
.brand-card img {
    width: 100%;
    height: 100px;
    object-fit: contain;
    margin-bottom: 10px;
}
.brand-card a {
    text-decoration: none;
    color: #0d6efd;
    font-weight: 600;
}
.products {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
    gap: 20px;
    margin-top: 30px;
}
.product-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: 0.3s;
}
.product-card:hover {
    transform: translateY(-5px);
}
.product-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 8px;
}
.product-card h4 { margin: 10px 0 5px; color: #333; }
.product-card p { color: #666; font-size: 14px; }
.price {
    color: #dc3545;
    font-weight: bold;
}
footer {
    background: #0d6efd;
    color: white;
    text-align: center;
    padding: 15px 0;
    margin-top: 40px;
}
</style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="logo">🖥️ BuildPC</div>
    <ul>
        <li><a href="../index.php">Trang chủ</a></li>
        <li><a href="products.php">Sản phẩm</a></li>
        <li><a href="brands.php" style="text-decoration: underline;">Thương hiệu</a></li>
        <li><a href="build_pc.php">Xây dựng cấu hình</a></li>
        <li><a href="about.php">Giới thiệu</a></li>
        <li><a href="contact.php">Liên hệ</a></li>
    </ul>
    <div class="user">
        <?php if (isset($_SESSION['user'])): ?>
            👋 Xin chào, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong> |
            <a href="logout.php" style="color:#ffcc00;">Đăng xuất</a>
        <?php else: ?>
            <a href="login.php" style="color:#fff;">Đăng nhập</a>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <h2>Thương hiệu nổi bật</h2>

    <!-- DANH SÁCH THƯƠNG HIỆU -->
    <div class="brand-list">
        <?php foreach ($brands as $b): ?>
        <div class="brand-card">
            <a href="?brand_id=<?= $b['brand_id'] ?>">
                <img src="../uploads/<?= htmlspecialchars($b['slug'] ?: 'default_brand.png') ?>" alt="<?= htmlspecialchars($b['name']) ?>">
                <div><?= htmlspecialchars($b['name']) ?></div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- SẢN PHẨM THEO THƯƠNG HIỆU -->
    <?php if (!empty($products)): ?>
    <h2 style="margin-top:40px;">Sản phẩm của thương hiệu: <?= htmlspecialchars($brand_title) ?></h2>
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
    © <?= date('Y') ?> BuildPC - Thiết kế và lắp ráp máy tính theo yêu cầu
</footer>

</body>
</html>
