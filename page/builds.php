<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php'; // Kết nối CSDL

// ⚙️ Tạm bỏ kiểm tra đăng nhập để không bị chuyển hướng
// if (!isset($_SESSION['user'])) {
//     header('Location: login.php');
//     exit;
// }

// Lấy danh sách cấu hình đã tạo
try {
    $stmt = $pdo->prepare("
        SELECT b.*, u.full_name 
        FROM builds b
        LEFT JOIN users u ON b.user_id = u.user_id
        ORDER BY b.build_id DESC
    ");
    $stmt->execute();
    $builds = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ Lỗi truy vấn: " . $e->getMessage());
}

// Gọi header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <h2 class="page-title">🧩 Xây dựng cấu hình máy tính</h2>

  <form method="post" action="build_save.php" class="build-form">
    <label>CPU:</label>
    <select name="cpu" required>
      <option value="">-- Chọn CPU --</option>
    </select>

    <label>Mainboard:</label>
    <select name="mainboard" required>
      <option value="">-- Chọn Mainboard --</option>
    </select>

    <label>RAM:</label>
    <select name="ram" required>
      <option value="">-- Chọn RAM --</option>
    </select>

    <label>GPU:</label>
    <select name="gpu" required>
      <option value="">-- Chọn GPU --</option>
    </select>

    <label>Ổ cứng:</label>
    <select name="storage" required>
      <option value="">-- Chọn ổ cứng --</option>
    </select>

    <button type="submit" class="btn-save">💾 Lưu cấu hình</button>
  </form>

  <h3 class="sub-title">📋 Danh sách cấu hình đã tạo</h3>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Tên cấu hình</th>
        <th>Người tạo</th>
        <th>Tổng giá</th>
        <th>Ngày tạo</th>
        <th>Hành động</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($builds)): ?>
        <?php foreach ($builds as $b): ?>
        <tr>
          <td><?= htmlspecialchars($b['build_id']) ?></td>
          <td><?= htmlspecialchars($b['name']) ?></td>
          <td><?= htmlspecialchars($b['full_name'] ?? 'Không rõ') ?></td>
          <td><?= number_format($b['total_price'], 0, ',', '.') ?> ₫</td>
          <td><?= htmlspecialchars($b['created_at']) ?></td>
          <td>
            <a href="build_detail.php?id=<?= $b['build_id'] ?>" class="btn view">👁 Xem</a>
            <a href="build_edit.php?id=<?= $b['build_id'] ?>" class="btn edit">✏ Sửa</a>
            <a href="build_delete.php?id=<?= $b['build_id'] ?>" class="btn del" onclick="return confirm('Xóa cấu hình này?')">🗑 Xóa</a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6">Chưa có cấu hình nào được tạo.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<style>
body {
  font-family: "Segoe UI", Tahoma, sans-serif;
  background: linear-gradient(135deg, #a2d2ff, #89c2ff);
  margin: 0;
  padding: 20px;
}
.container {
  background: #fff;
  padding: 30px;
  border-radius: 16px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.1);
  max-width: 900px;
  margin: auto;
}
.page-title {
  text-align: center;
  color: #007bff;
  margin-bottom: 25px;
  font-size: 26px;
}
label {
  display: block;
  margin-top: 10px;
  font-weight: bold;
  color: #0056b3;
}
select {
  width: 100%;
  padding: 8px;
  border: 1px solid #bcd0f7;
  border-radius: 8px;
  margin-bottom: 12px;
  transition: 0.2s;
}
select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 5px rgba(0,123,255,0.5);
}
.btn-save {
  margin-top: 15px;
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  background: linear-gradient(90deg, #007bff, #00b4ff);
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s;
}
.btn-save:hover {
  background: linear-gradient(90deg, #0069d9, #0099e6);
}
.sub-title {
  margin-top: 40px;
  color: #007bff;
  font-size: 20px;
  text-align: center;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
}
th, td {
  padding: 10px;
  border-bottom: 1px solid #e0e0e0;
  text-align: center;
}
th {
  background: #007bff;
  color: white;
}
td {
  background: #f8faff;
}
.btn {
  padding: 6px 10px;
  border-radius: 6px;
  color: white;
  text-decoration: none;
  font-weight: 600;
  transition: 0.3s;
}
.view { background: #17a2b8; }
.edit { background: #ffc107; color: black; }
.del { background: #dc3545; }
.view:hover { background: #138496; }
.edit:hover { background: #e0a800; }
.del:hover { background: #c82333; }
</style>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
