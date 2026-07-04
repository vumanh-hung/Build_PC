<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

$category_id = $_GET['category_id'] ?? 0;
$cat = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
$cat->execute([$category_id]);
$category = $cat->fetch();

if (!$category) die("Không tìm thấy danh mục!");
$keyword = $_GET['keyword'] ?? '';

$stmt = $pdo->prepare("
  SELECT p.*, b.name AS brand_name
  FROM products p
  LEFT JOIN brands b ON p.brand_id = b.brand_id
  WHERE p.category_id = ? AND (p.name LIKE ? OR b.name LIKE ?)
");
$stmt->execute([$category_id, "%$keyword%", "%$keyword%"]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Chọn linh kiện <?= htmlspecialchars($category['name']) ?></title>
<style>
body{font-family:sans-serif;background:#f0f3f8;margin:0;}
.container{max-width:1100px;margin:40px auto;padding:20px;}
.search-bar{margin-bottom:20px;text-align:center;}
input[type=text]{padding:10px 14px;width:60%;border-radius:8px;border:1px solid #ccc;}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;}
.card{background:white;border-radius:12px;padding:16px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,.08);transition:.3s;}
.card:hover{transform:translateY(-4px);box-shadow:0 6px 18px rgba(26,115,232,.25);}
.card img{width:150px;height:150px;object-fit:contain;cursor:pointer;}
.price{color:#ff9800;font-weight:700;margin:8px 0;}
.btn{background:#1a73e8;color:white;padding:8px 16px;border:none;border-radius:8px;cursor:pointer;}
</style>
</head>
<body>
<div class="container">
<h2>🧩 Chọn <?= htmlspecialchars($category['name']) ?></h2>
<div class="search-bar">
<form method="GET">
<input type="hidden" name="category_id" value="<?= $category_id ?>">
<input type="text" name="keyword" placeholder="Tìm kiếm sản phẩm..." value="<?= htmlspecialchars($keyword) ?>">
<button class="btn">Tìm</button>
</form>
</div>

<div class="grid">
<?php foreach ($products as $p): ?>
  <div class="card">
    <img src="../uploads/<?= htmlspecialchars($p['main_image']) ?>" onclick="window.location.href='product_detail.php?id=<?= $p['product_id'] ?>'">
    <h4><?= htmlspecialchars($p['name']) ?></h4>
    <p class="price"><?= number_format($p['price'],0,',','.') ?> ₫</p>
    <button class="btn" onclick='selectPart(<?= json_encode($p) ?>)'>Chọn</button>
  </div>
<?php endforeach; ?>
</div>
</div>

<script>
function selectPart(p){
  let parts = JSON.parse(sessionStorage.getItem("selectedParts") || "{}");
  parts[p.category_id] = {product_id:p.product_id, name:p.name, price:p.price, category:p.category_id};
  sessionStorage.setItem("selectedParts", JSON.stringify(parts));
  alert("Đã chọn "+p.name);
  window.location.href="builds.php";
}
</script>
</body>
</html>
