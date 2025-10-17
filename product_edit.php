<?php
session_start();
require_once 'functions.php';
require_once 'config.php';

if (!isset($_SESSION['is_admin'])) {
    header('Location: admin.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $image = $product['image'];

    if (!empty($_FILES['image']['name'])) {
        $targetDir = __DIR__ . '/uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $filename = basename($_FILES['image']['name']);
        $targetFile = $targetDir . time() . '_' . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image = 'uploads/' . basename($targetFile);
        }
    }

    $stmt = $pdo->prepare('UPDATE products SET name=?, category=?, price=?, description=?, image=? WHERE id=?');
    $stmt->execute([$name, $category, $price, $description, $image, $id]);
    header('Location: admin.php');
    exit;
}

include 'views_header.php';
?>
<div class="container">
  <h1>Sửa sản phẩm</h1>
  <form method="post" enctype="multipart/form-data">
    <label>Tên: <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required></label><br><br>
    <label>Danh mục: <input type="text" name="category" value="<?= htmlspecialchars($product['category']) ?>" required></label><br><br>
    <label>Giá: <input type="number" name="price" value="<?= htmlspecialchars($product['price']) ?>" required></label><br><br>
    <label>Mô tả:<br><textarea name="description" rows="4"><?= htmlspecialchars($product['description']) ?></textarea></label><br><br>
    <label>Ảnh hiện tại: <?= htmlspecialchars($product['image']) ?></label><br>
    <label>Thay ảnh: <input type="file" name="image"></label><br><br>
    <button class="button" type="submit">Lưu</button>
  </form>
  <p><a href="admin.php">Back</a></p>
</div>
<?php include 'views_footer.php'; ?>
