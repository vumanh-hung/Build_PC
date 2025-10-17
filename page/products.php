<?php
session_start();
require_once '../db.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$sql = "
SELECT p.*, 
       c.name AS category_name, 
       b.name AS brand_name
FROM products p
LEFT JOIN categories c ON p.category_id = c.category_id
LEFT JOIN brands b ON p.brand_id = b.brand_id
ORDER BY p.product_id DESC
";
$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản lý sản phẩm</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body { font-family:'Segoe UI'; background:#eaf3ff; margin:20px; }
table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; }
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:center; }
th { background:#007bff; color:white; }
a.btn { padding:6px 10px; border-radius:5px; color:white; text-decoration:none; margin:2px; display:inline-block; }
.add { background:#28a745; }
.edit { background:#007bff; }
.del { background:#dc3545; }
img { border-radius:6px; }
h2 { color:#007bff; }
</style>
</head>
<body>
<h2>📦 Danh sách sản phẩm</h2>
<p><a class="btn add" href="product_add.php">+ Thêm sản phẩm</a> | <a href="../admin.php">⬅ Trang quản trị</a></p>

<table>
    <tr>
        <th>ID</th>
        <th>Tên sản phẩm</th>
        <th>Danh mục</th>
        <th>Thương hiệu</th>
        <th>Giá</th>
        <th>Tồn kho</th>
        <th>Ảnh</th>
        <th>Hành động</th>
    </tr>
    <?php foreach ($products as $p): ?>
    <tr>
        <td><?= $p['product_id'] ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
        <td><?= htmlspecialchars($p['brand_name'] ?? '—') ?></td>
        <td><?= number_format($p['price'], 0, ',', '.') ?> ₫</td>
        <td><?= $p['stock'] ?></td>
        <td>
            <?php if ($p['main_image']): ?>
                <img src="../uploads/<?= htmlspecialchars($p['main_image']) ?>" width="60">
            <?php endif; ?>
        </td>
        <td>
            <a class="btn edit" href="product_edit.php?id=<?= $p['product_id'] ?>">Sửa</a>
            <a class="btn del" href="product_delete.php?id=<?= $p['product_id'] ?>" onclick="return confirm('Xóa sản phẩm này?')">Xóa</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
