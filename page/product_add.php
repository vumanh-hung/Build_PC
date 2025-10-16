<?php
session_start();
require_once '../db.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $desc = $_POST['description'];
    $image = '';

    if (!empty($_FILES['image']['name'])) {
        $image = time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image);
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, category, price, description, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category, $price, $desc, $image]);
    header('Location: products.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Thêm sản phẩm</title></head>
<body>
<h2>➕ Thêm sản phẩm</h2>
<form method="POST" enctype="multipart/form-data">
    <label>Tên sản phẩm:</label><br><input type="text" name="name" required><br><br>
    <label>Danh mục:</label><br><input type="text" name="category" required><br><br>
    <label>Giá:</label><br><input type="number" name="price" required><br><br>
    <label>Mô tả:</label><br><textarea name="description"></textarea><br><br>
    <label>Ảnh:</label><br><input type="file" name="image"><br><br>
    <button type="submit">Lưu</button>
    <a href="products.php">Hủy</a>
</form>
</body>
</html>
