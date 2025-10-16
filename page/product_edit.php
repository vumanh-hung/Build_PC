<?php
session_start();
require_once '../db.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $desc = $_POST['description'];
    $image = $product['image'];

    if (!empty($_FILES['image']['name'])) {
        $image = time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image);
    }

    $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, description=?, image=? WHERE id=?");
    $stmt->execute([$name, $category, $price, $desc, $image, $id]);
    header('Location: products.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Sửa sản phẩm</title></head>
<body>
<h2>✏️ Sửa sản phẩm</h2>
<form method="POST" enctype="multipart/form-data">
    <label>Tên:</label><br><input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>"><br><br>
    <label>Danh mục:</label><br><input type="text" name="category" value="<?= htmlspecialchars($product['category']) ?>"><br><br>
    <label>Giá:</label><br><input type="number" name="price" value="<?= $product['price'] ?>"><br><br>
    <label>Mô tả:</label><br><textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea><br><br>
    <label>Ảnh:</label><br>
    <input type="file" name="image"><br>
    <?php if ($product['image']): ?><img src="../uploads/<?= htmlspecialchars($product['image']) ?>" width="100"><br><?php endif; ?>
    <button type="submit">Cập nhật</button>
    <a href="products.php">Hủy</a>
</form>
</body>
</html>
