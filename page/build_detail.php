<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
$pdo = getPDO();

$build_id = $_GET['id'] ?? 0;
if (!$build_id) {
    die("Thiếu ID cấu hình");
}

// Gọi API để lấy chi tiết cấu hình
$apiUrl = SITE_URL . '/api/build_detail.php?id=' . urlencode($build_id);
//echo "<pre>🔍 API URL đang gọi: $apiUrl</pre>";
//$api = @file_get_contents($apiUrl);
//var_dump($api);
//exit;
$api = @file_get_contents($apiUrl);
$data = json_decode($api, true);

$build = $data['build'] ?? null;
$items = $data['items'] ?? [];
if (!$build) die("Không tìm thấy cấu hình này");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Chi tiết cấu hình - <?= htmlspecialchars($build['name']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
  font-family: Arial, sans-serif;
  margin: 30px;
  background: #f4f7fb;
  color: #333;
}
.container {
  max-width: 1000px;
  margin: auto;
  background: white;
  padding: 40px;
  border-radius: 14px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}
h1 {
  color: #1a73e8;
  margin-bottom: 20px;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
}
th, td {
  padding: 12px;
  border-bottom: 1px solid #ddd;
  text-align: center;
}
th {
  background: #1a73e8;
  color: white;
}
img.product-img {
  width: 70px;
  height: 70px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #ddd;
  transition: transform 0.2s;
}
img.product-img:hover {
  transform: scale(1.25);
}
.total {
  text-align: right;
  font-weight: bold;
  font-size: 18px;
  margin-top: 20px;
  color: #ff9800;
}
a.btn {
  display: inline-block;
  padding: 10px 20px;
  background: #1a73e8;
  color: white;
  border-radius: 6px;
  text-decoration: none;
  margin-top: 20px;
}
a.btn:hover {
  background: #0d47a1;
}
</style>
</head>
<body>
<div class="container">
  <h1>💻 Chi tiết cấu hình: <?= htmlspecialchars($build['name']) ?></h1>
  <p><strong>Người tạo:</strong> <?= htmlspecialchars($build['full_name'] ?? 'Không rõ') ?></p>
  <p><strong>Ngày tạo:</strong> <?= htmlspecialchars($build['created_at']) ?></p>

  <table>
    <thead>
      <tr>
        <th>Ảnh</th>
        <th>Danh mục</th>
        <th>Sản phẩm</th>
        <th>Giá</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $it): ?>
<?php

$stmt = $pdo->prepare("SELECT main_image FROM products WHERE name = ?");
$stmt->execute([$it['product_name']]);
$image = $stmt->fetchColumn();

// 🧠 Nếu trong DB chỉ có tên file → thêm đường dẫn /uploads/
if ($image && file_exists(__DIR__ . '/../uploads/' . $image)) {
    $imageUrl = SITE_URL . '/uploads/' . rawurlencode($image);
} else {
    // Ảnh mặc định nếu không tồn tại
    $imageUrl = SITE_URL . '/uploads/no-image.png';
}

?>
<tr>
  <td style="text-align:center">
    <img src="<?= htmlspecialchars($imageUrl) ?>" 
         alt="Ảnh sản phẩm" 
         style="width:80px; height:80px; object-fit:cover; border-radius:8px; box-shadow:0 0 5px rgba(0,0,0,0.2)">
  </td>
  <td><?= htmlspecialchars($it['category_name']) ?></td>
  <td><?= htmlspecialchars($it['product_name']) ?></td>
  <td><?= number_format($it['price'], 0, ',', '.') ?> ₫</td>
</tr>
<?php endforeach; ?>

    </tbody>
  </table>

  <p class="total">Tổng cộng: <?= number_format($build['total_price'], 0, ',', '.') ?> ₫</p>

  <a href="builds.php" class="btn"><i class="fa fa-arrow-left"></i> Quay lại</a>
</div>
</body>
</html>
