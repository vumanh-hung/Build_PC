<?php
session_start();
require_once("../db.php");

// Lấy danh sách sản phẩm theo danh mục
$categories = [
  "CPU" => 1,
  "Mainboard" => 2,
  "RAM" => 3,
  "GPU" => 4,
  "Ổ cứng" => 5,
  "Nguồn" => 6,
  "Vỏ máy" => 7,
];

$productsByCat = [];
foreach ($categories as $name => $catId) {
  $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ?");
  $stmt->execute([$catId]);
  $productsByCat[$name] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Xây dựng cấu hình</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background: #f5f8ff;
  margin: 0;
}
.container {
  max-width: 1000px;
  margin: 40px auto;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  padding: 30px 40px;
}
h2 { text-align:center; color:#007bff; }
.category { margin: 25px 0; }
.category label { font-weight: bold; display: block; margin-bottom: 5px; }
select {
  width: 100%;
  padding: 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 15px;
}
.total-box {
  text-align: center;
  margin-top: 30px;
  background: #eef4ff;
  padding: 20px;
  border-radius: 10px;
  font-size: 18px;
}
button {
  display: block;
  margin: 30px auto 0;
  padding: 10px 25px;
  background: #007bff;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 16px;
  cursor: pointer;
}
button:hover { background: #0056b3; }
</style>
</head>
<body>
<?php include("../layout/header.php"); ?>

<div class="container">
  <h2>🧩 Xây dựng cấu hình máy tính</h2>

  <form id="buildForm">
    <?php foreach ($productsByCat as $catName => $products): ?>
    <div class="category">
      <label><?= htmlspecialchars($catName) ?>:</label>
      <select class="component" name="components[<?= htmlspecialchars($catName) ?>]" onchange="updateTotal()">
        <option value="">-- Chọn <?= htmlspecialchars($catName) ?> --</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= $p['product_id'] ?>" data-price="<?= $p['price'] ?>">
            <?= htmlspecialchars($p['name']) ?> - <?= number_format($p['price'],0,',','.') ?> ₫
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endforeach; ?>

    <div class="total-box">
      Tổng giá: <b id="totalPrice">0</b> ₫
    </div>

    <button type="button" id="saveBuildBtn">💾 Lưu cấu hình</button>
  </form>
</div>

<?php include("../layout/footer.php"); ?>

<script>
function updateTotal() {
  let total = 0;
  document.querySelectorAll(".component").forEach(sel => {
    const price = parseFloat(sel.selectedOptions[0]?.dataset.price || 0);
    total += price;
  });
  document.getElementById("totalPrice").textContent = total.toLocaleString("vi-VN");
}

document.getElementById("saveBuildBtn").addEventListener("click", async () => {
  const components = {};
  document.querySelectorAll(".component").forEach(sel => {
    const name = sel.name.match(/\[(.*?)\]/)[1];
    components[name] = sel.value;
  });

  const res = await fetch("../api/save_build.php", {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({components})
  });
  const data = await res.json();
  alert(data.message);
});
</script>
</body>
</html>
