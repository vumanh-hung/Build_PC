<?php
session_start();
require_once '../db.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Không tìm thấy sản phẩm!");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $brand_id = $_POST['brand_id'] ?: null;
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];
    $main_image = $product['main_image'];

    if (!empty($_FILES['main_image']['name'])) {
        $main_image = time() . "_" . basename($_FILES['main_image']['name']);
        move_uploaded_file($_FILES['main_image']['tmp_name'], "../uploads/$main_image");
    }

    $stmt = $pdo->prepare("
        UPDATE products
        SET name=?, category_id=?, brand_id=?, price=?, stock=?, description=?, main_image=?
        WHERE product_id=?
    ");
    $stmt->execute([$name, $category_id, $brand_id, $price, $stock, $description, $main_image, $id]);
    header("Location: products.php");
    exit;
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$brands = $pdo->query("SELECT * FROM brands")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Sửa sản phẩm</title>
<style>
body { font-family:'Segoe UI'; background:#f0f6ff; display:flex; justify-content:center; padding-top:50px; }
form { background:#fff; padding:25px 35px; border-radius:10px; width:400px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
h2 { text-align:center; color:#007bff; margin-bottom:20px; }
input, select, textarea { width:100%; margin-bottom:12px; padding:8px; border:1px solid #ccc; border-radius:6px; }
button { width:100%; background:#007bff; color:white; padding:10px; border:none; border-radius:6px; cursor:pointer; }
button:hover { background:#0056b3; }
img { max-width:100px; margin-bottom:10px; border-radius:6px; }
</style>
</head>
<body>
<form method="POST" enctype="multipart/form-data">
<h2>✏️ Sửa sản phẩm</h2>
<input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
<select name="category_id" required>
    <?php foreach ($categories as $c): ?>
        <option value="<?= $c['category_id'] ?>" <?= $c['category_id']==$product['category_id']?'selected':'' ?>>
            <?= $c['name'] ?>
        </option>
    <?php endforeach; ?>
</select>
<select name="brand_id">
    <option value="">-- Thương hiệu --</option>
    <?php foreach ($brands as $b): ?>
        <option value="<?= $b['brand_id'] ?>" <?= $b['brand_id']==$product['brand_id']?'selected':'' ?>>
            <?= $b['name'] ?>
        </option>
    <?php endforeach; ?>
</select>
<input type="number" name="price" value="<?= $product['price'] ?>" required>
<input type="number" name="stock" value="<?= $product['stock'] ?>" required>
<textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
<?php if ($product['main_image']): ?>
    <img src="../uploads/<?= htmlspecialchars($product['main_image']) ?>">
<?php endif; ?>
<input type="file" name="main_image" accept="image/*">
<button type="submit">Cập nhật</button>
</form>
</body>
</html>
