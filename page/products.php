<?php
session_start();
require_once '../db.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản lý sản phẩm</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body { font-family:'Segoe UI'; background:#f4f7fb; margin:20px; }
table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden; }
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:center; }
th { background:#007bff; color:white; }
a.btn { padding:6px 10px; border-radius:5px; color:white; text-decoration:none; margin:2px; display:inline-block; }
.add { background:#28a745; }
.edit { background:#007bff; }
.del { background:#dc3545; }
</style>
</head>
<body>
<h2>📦 Quản lý sản phẩm</h2>
<p><a class="btn add" href="product_add.php">+ Thêm sản phẩm</a> | <a href="admin.php">⬅ Trở lại</a></p>

<table>
    <tr>
        <th>ID</th>
        <th>Tên</th>
        <th>Danh mục</th>
        <th>Giá</th>
        <th>Mô tả</th>
        <th>Ảnh</th>
        <th>Hành động</th>
    </tr>
    <?php foreach ($products as $p): ?>
    <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><?= htmlspecialchars($p['category']) ?></td>
        <td><?= number_format($p['price'], 0, ',', '.') ?> ₫</td>
        <td><?= htmlspecialchars($p['description']) ?></td>
        <td>
            <?php if ($p['image']): ?>
                <img src="../uploads/<?= htmlspecialchars($p['image']) ?>" width="60">
            <?php endif; ?>
        </td>
        <td>
            <a class="btn edit" href="product_edit.php?id=<?= $p['id'] ?>">Sửa</a>
            <a class="btn del" href="product_delete.php?id=<?= $p['id'] ?>" onclick="return confirm('Xóa sản phẩm này?')">Xóa</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
