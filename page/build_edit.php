<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
$pdo = getPDO();

$build_id = $_GET['id'] ?? 0;
if (!$build_id) die("Thiếu ID cấu hình");

// Gọi API để lấy chi tiết
$apiUrl = dirname(SITE_URL) . '/api/build_detail.php?id=' . urlencode($build_id);
$api = @file_get_contents($apiUrl);
$data = json_decode($api, true);

$build = $data['build'] ?? null;
$items = $data['items'] ?? [];
if (!$build) die("Không tìm thấy cấu hình này");

// Lấy danh sách tất cả sản phẩm để chọn lại
$products = $pdo->query("SELECT product_id, name, price FROM products ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Sửa cấu hình - <?= htmlspecialchars($build['name']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { font-family: Arial; margin: 20px; background: #f4f7fb; color: #333; }
.container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);}
h1 { color: #1a73e8; margin-bottom: 20px; }
select, input[type=text] { padding: 8px; width: 100%; border: 1px solid #ccc; border-radius: 4px; }
button { background: #1a73e8; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
button:hover { background: #0d47a1; }
a.btn { display: inline-block; padding: 10px 20px; background: #999; color: white; border-radius: 6px; text-decoration: none; margin-left: 10px; }
a.btn:hover { background: #555; }
.total { text-align: right; font-weight: bold; font-size: 18px; margin-top: 20px; color: #ff9800; }
</style>
</head>
<body>
<div class="container">
  <h1>✏️ Sửa cấu hình: <?= htmlspecialchars($build['name']) ?></h1>

  <label for="build-name">Tên cấu hình:</label>
  <input type="text" id="build-name" value="<?= htmlspecialchars($build['name']) ?>" required>

  <h3 style="margin-top:20px;">Danh sách linh kiện</h3>
  <table style="width:100%; border-collapse:collapse; margin-top:10px;">
    <thead>
      <tr><th>Danh mục</th><th>Sản phẩm</th><th>Giá</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['category_name']) ?></td>
        <td>
          <select class="part-select">
            <option value="">-- Chọn sản phẩm --</option>
            <?php foreach ($products as $p): ?>
              <option value="<?= $p['product_id'] ?>"
                <?= $p['name'] === $it['product_name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?> - <?= number_format($p['price'], 0, ',', '.') ?> ₫
              </option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><?= number_format($it['price'], 0, ',', '.') ?> ₫</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div style="margin-top:25px;">
    <button id="save-build-btn">💾 Lưu cấu hình</button>
    <a href="builds.php" class="btn">⬅ Quay lại</a>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const saveBtn = document.getElementById("save-build-btn");

  saveBtn.addEventListener("click", async () => {
    const buildId = <?= (int)$build['build_id'] ?>;
    const name = document.querySelector("#build-name").value.trim();
    const parts = [];

    document.querySelectorAll(".part-select").forEach(sel => {
      const val = sel.value;
      if (val) parts.push(parseInt(val));
    });

    if (parts.length === 0) {
      alert("⚠️ Vui lòng chọn ít nhất 1 linh kiện!");
      return;
    }

    try {
      const res = await fetch("../api/update_build.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ build_id: buildId, name, parts })
      });

      const data = await res.json();
      if (data.success) {
        alert(data.message || "Đã cập nhật cấu hình thành công!");
        window.location.href = "builds.php";
      } else {
        alert("❌ " + (data.error || "Có lỗi khi cập nhật cấu hình"));
      }
    } catch (err) {
      alert("Lỗi kết nối tới máy chủ!");
      console.error(err);
    }
  });
});
</script>
</body>
</html>
