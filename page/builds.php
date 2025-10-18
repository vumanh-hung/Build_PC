<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

// ===== CSRF TOKEN =====
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

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
    die("Lỗi truy vấn: " . $e->getMessage());
}

// Lấy số lượng giỏ hàng
$cart_count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pid => $it) {
        $cart_count += is_array($it) && isset($it['quantity']) ? (int)$it['quantity'] : (int)$it;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Xây dựng cấu hình - BuildPC.vn</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  color: #333;
  min-height: 100vh;
}

/* ===== HEADER ===== */
header {
  background: linear-gradient(90deg, #007bff 0%, #00aaff 50%, #007bff 100%);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 40px;
  box-shadow: 0 8px 24px rgba(0, 107, 255, 0.15);
  position: sticky;
  top: 0;
  z-index: 999;
  gap: 20px;
  backdrop-filter: blur(10px);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 40px;
}

.logo {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.logo a {
  text-decoration: none;
}

.logo span {
  color: white;
  font-weight: 800;
  font-size: 20px;
  letter-spacing: 0.5px;
  white-space: nowrap;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.nav {
  display: flex;
  align-items: center;
  gap: 28px;
}

.nav a {
  color: white;
  text-decoration: none;
  font-weight: 500;
  font-size: 13px;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.nav a:hover,
.nav a.active {
  color: #ffeb3b;
}

.header-center {
  display: flex;
  align-items: center;
  flex: 1;
  max-width: 400px;
}

.search-container {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 25px;
  overflow: hidden;
  width: 100%;
  height: 38px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
  display: flex;
  align-items: center;
  transition: all 0.3s ease;
}

.search-container:focus-within {
  box-shadow: 0 8px 25px rgba(0, 107, 255, 0.25);
  transform: translateY(-2px);
}

.search-container input {
  flex: 1;
  border: none;
  outline: none;
  padding: 0 16px;
  font-size: 13px;
  color: #333;
  height: 38px;
  background: transparent;
}

.search-container input::placeholder {
  color: #999;
}

.search-container button {
  background: none;
  border: none;
  width: 38px;
  height: 38px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  color: #007bff;
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.search-container button:hover {
  color: #ff9800;
  transform: scale(1.15);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-shrink: 0;
}

.cart-link {
  position: relative;
  background: rgba(255, 255, 255, 0.95);
  color: #007bff;
  padding: 8px 16px;
  border-radius: 20px;
  text-decoration: none;
  font-weight: 600;
  font-size: 12px;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.cart-link:hover {
  background: white;
  box-shadow: 0 6px 20px rgba(0, 107, 255, 0.3);
  transform: translateY(-3px);
}

.cart-count {
  position: absolute;
  top: -8px;
  right: -8px;
  background: linear-gradient(135deg, #ffeb3b, #ff9800);
  color: #111;
  font-size: 10px;
  font-weight: 900;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
}

.login-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 12px;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
  white-space: nowrap;
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
  border: 2px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.login-btn:hover {
  background: #ffffff;
  color: #007bff;
  border-color: #ffffff;
  transform: translateY(-3px);
}

.logout-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 12px;
  text-decoration: none;
  background: linear-gradient(135deg, #ff5252, #ff1744);
  color: white;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
}

.logout-btn:hover {
  background: linear-gradient(135deg, #ff1744, #d50000);
  transform: translateY(-3px);
}

.welcome {
  color: #fff;
  font-size: 12px;
  font-weight: 600;
}

/* ===== BANNER ===== */
.banner {
  background: linear-gradient(135deg, #1a73e8 0%, #1e88e5 50%, #1565c0 100%);
  color: white;
  text-align: center;
  padding: 60px 20px;
  position: relative;
  overflow: hidden;
}

.banner::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(255, 235, 59, 0.15) 0%, transparent 70%);
  pointer-events: none;
  animation: float 20s ease-in-out infinite;
}

.banner::after {
  content: '';
  position: absolute;
  bottom: -50%;
  left: -10%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(33, 150, 243, 0.1) 0%, transparent 70%);
  pointer-events: none;
  animation: float 25s ease-in-out infinite reverse;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(30px); }
}

.banner h1 {
  font-size: 42px;
  margin-bottom: 12px;
  font-weight: 900;
  text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  position: relative;
  z-index: 1;
  letter-spacing: -0.5px;
}

.banner p {
  font-size: 15px;
  opacity: 0.95;
  position: relative;
  z-index: 1;
  font-weight: 300;
  letter-spacing: 0.5px;
}

/* ===== CONTAINER ===== */
.container {
  max-width: 1200px;
  margin: 40px auto;
  padding: 0 20px;
}

.page-title {
  font-size: 28px;
  color: #1a73e8;
  text-align: center;
  margin-bottom: 30px;
  font-weight: 800;
  letter-spacing: -0.5px;
}

/* ===== BUILD FORM ===== */
.build-form {
  background: white;
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  margin-bottom: 40px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: 700;
  color: #1a73e8;
  margin-bottom: 8px;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-group select {
  padding: 10px 12px;
  border: 2px solid #e8e8e8;
  border-radius: 8px;
  font-size: 13px;
  transition: all 0.3s ease;
  background: white;
  font-family: inherit;
  cursor: pointer;
}

.form-group select:focus {
  outline: none;
  border-color: #1a73e8;
  box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
  background: #f0f7ff;
}

.btn-save {
  background: linear-gradient(135deg, #1a73e8, #1565c0);
  color: white;
  border: none;
  padding: 12px 32px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 6px 16px rgba(26, 115, 232, 0.3);
  width: 100%;
  justify-content: center;
}

.btn-save:hover {
  background: linear-gradient(135deg, #1565c0, #0d47a1);
  transform: translateY(-3px);
  box-shadow: 0 10px 24px rgba(26, 115, 232, 0.4);
}

/* ===== TABLE SECTION ===== */
.table-section {
  background: white;
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.section-title {
  font-size: 22px;
  color: #1a73e8;
  font-weight: 800;
  margin-bottom: 24px;
  letter-spacing: -0.5px;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: linear-gradient(135deg, #1a73e8, #1565c0);
  color: white;
}

th {
  padding: 14px;
  text-align: left;
  font-weight: 700;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

td {
  padding: 14px;
  border-bottom: 1px solid #e8e8e8;
  font-size: 13px;
}

tbody tr {
  transition: all 0.3s ease;
}

tbody tr:hover {
  background: #f8faff;
}

.build-name {
  font-weight: 700;
  color: #1a73e8;
}

.build-author {
  color: #666;
  font-size: 12px;
}

.build-price {
  font-weight: 800;
  color: #ff9800;
}

.build-date {
  color: #999;
  font-size: 12px;
}

.action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.btn {
  padding: 6px 12px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 600;
  font-size: 11px;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.btn-view {
  background: #17a2b8;
  color: white;
}

.btn-view:hover {
  background: #138496;
  transform: translateY(-2px);
}

.btn-edit {
  background: #ffc107;
  color: #000;
}

.btn-edit:hover {
  background: #e0a800;
  transform: translateY(-2px);
}

.btn-del {
  background: #dc3545;
  color: white;
}

.btn-del:hover {
  background: #c82333;
  transform: translateY(-2px);
}

.no-builds {
  text-align: center;
  padding: 40px;
  color: #999;
}

.no-builds i {
  font-size: 48px;
  margin-bottom: 16px;
  display: block;
  opacity: 0.5;
}

/* ===== FOOTER ===== */
footer {
  background: linear-gradient(90deg, #007bff 0%, #00aaff 50%, #007bff 100%);
  color: white;
  text-align: center;
  padding: 24px 20px;
  margin-top: 60px;
  font-size: 13px;
  box-shadow: 0 -4px 12px rgba(0, 107, 255, 0.1);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
  header {
    padding: 10px 24px;
    gap: 16px;
  }

  .nav {
    gap: 20px;
  }

  .form-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  table {
    font-size: 12px;
  }

  th, td {
    padding: 10px;
  }
}

@media (max-width: 768px) {
  header {
    padding: 10px 16px;
    flex-wrap: wrap;
  }

  .header-left {
    gap: 16px;
    width: 100%;
  }

  .logo span {
    font-size: 16px;
  }

  .nav {
    gap: 12px;
    font-size: 12px;
    width: 100%;
  }

  .header-center {
    width: 100%;
    max-width: 100%;
    order: 4;
    margin-top: 10px;
  }

  .header-right {
    width: 100%;
    justify-content: flex-start;
    order: 5;
    margin-top: 10px;
  }

  .banner h1 {
    font-size: 28px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .build-form,
  .table-section {
    padding: 16px;
  }

  .page-title,
  .section-title {
    font-size: 20px;
  }

  table {
    font-size: 11px;
  }

  th, td {
    padding: 8px;
  }

  .action-buttons {
    gap: 4px;
  }

  .btn {
    padding: 4px 8px;
    font-size: 10px;
  }
}

@media (max-width: 480px) {
  header {
    padding: 8px 12px;
  }

  .logo span {
    font-size: 14px;
  }

  .nav {
    gap: 8px;
    font-size: 11px;
  }

  .search-container {
    height: 34px;
  }

  .search-container input {
    font-size: 12px;
    padding: 0 12px;
  }

  .search-container button {
    width: 34px;
    height: 34px;
  }

  .cart-link,
  .login-btn,
  .logout-btn {
    font-size: 11px;
    padding: 6px 12px;
  }

  .banner h1 {
    font-size: 24px;
  }

  .banner p {
    font-size: 13px;
  }

  .page-title {
    font-size: 18px;
  }

  .build-form,
  .table-section {
    padding: 12px;
    margin-bottom: 20px;
  }

  .section-title {
    font-size: 16px;
  }

  .form-group select {
    padding: 8px 10px;
    font-size: 12px;
  }

  .btn-save {
    padding: 10px 16px;
    font-size: 12px;
  }

  table {
    font-size: 10px;
  }

  th, td {
    padding: 6px;
  }

  .build-name,
  .build-price {
    display: block;
  }

  .action-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 4px;
  }

  .btn {
    width: 100%;
    justify-content: center;
    padding: 6px;
  }
}
</style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header>
  <div class="header-left">
    <div class="logo">
      <a href="../index.php">
        <span>🖥️ BuildPC.vn</span>
      </a>
    </div>

    <nav class="nav">
      <a href="../index.php">Trang chủ</a>
      <a href="products.php">Sản phẩm</a>
      <a href="brands.php">Thương hiệu</a>
      <a href="builds.php" class="active">Xây dựng cấu hình</a>
      <a href="about.php">Giới thiệu</a>
      <a href="contact.php">Liên hệ</a>
    </nav>
  </div>

  <div class="header-center">
    <form class="search-container" method="GET" action="products.php">
      <input type="text" name="keyword" placeholder="Tìm sản phẩm...">
      <button type="submit">
        <i class="fa-solid fa-search"></i>
      </button>
    </form>
  </div>

  <div class="header-right">
    <a href="../cart.php" class="cart-link">
      <i class="fa-solid fa-cart-shopping"></i> Giỏ hàng
      <?php if ($cart_count > 0): ?>
        <span class="cart-count"><?= $cart_count ?></span>
      <?php endif; ?>
    </a>

    <?php if (isset($_SESSION['user'])): ?>
      <span class="welcome">👋 <?= htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['full_name']) ?></span>
      <a href="logout.php" class="logout-btn">Đăng xuất</a>
    <?php else: ?>
      <a href="login.php" class="login-btn"><i class="fa-solid fa-user"></i> Đăng nhập</a>
    <?php endif; ?>
  </div>
</header>

<!-- ===== BANNER ===== -->
<div class="banner">
  <h1>Xây Dựng Cấu Hình</h1>
  <p>Thiết kế máy tính của bạn theo đúng nhu cầu</p>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="container">
  <h2 class="page-title">Xây dựng cấu hình máy tính</h2>

  <!-- ===== BUILD FORM ===== -->
  <form method="post" action="build_save.php" class="build-form">
    <div class="form-grid">
      <div class="form-group">
        <label for="cpu">CPU</label>
        <select id="cpu" name="cpu" required>
          <option value="">-- Chọn CPU --</option>
        </select>
      </div>

      <div class="form-group">
        <label for="mainboard">Mainboard</label>
        <select id="mainboard" name="mainboard" required>
          <option value="">-- Chọn Mainboard --</option>
        </select>
      </div>

      <div class="form-group">
        <label for="ram">RAM</label>
        <select id="ram" name="ram" required>
          <option value="">-- Chọn RAM --</option>
        </select>
      </div>

      <div class="form-group">
        <label for="gpu">GPU</label>
        <select id="gpu" name="gpu" required>
          <option value="">-- Chọn GPU --</option>
        </select>
      </div>

      <div class="form-group">
        <label for="storage">Ổ cứng</label>
        <select id="storage" name="storage" required>
          <option value="">-- Chọn ổ cứng --</option>
        </select>
      </div>
    </div>

    <button type="submit" class="btn-save">
      <i class="fa-solid fa-floppy-disk"></i> Lưu cấu hình
    </button>
  </form>

  <!-- ===== BUILDS TABLE ===== -->
  <div class="table-section">
    <h3 class="section-title">Danh sách cấu hình đã tạo</h3>

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
            <td class="build-name"><?= htmlspecialchars($b['name']) ?></td>
            <td class="build-author"><?= htmlspecialchars($b['full_name'] ?? 'Không rõ') ?></td>
            <td class="build-price"><?= number_format($b['total_price'], 0, ',', '.') ?> ₫</td>
            <td class="build-date"><?= htmlspecialchars($b['created_at']) ?></td>
            <td>
              <div class="action-buttons">
                <a href="build_detail.php?id=<?= $b['build_id'] ?>" class="btn btn-view">
                  <i class="fa-solid fa-eye"></i> Xem
                </a>
                <a href="build_edit.php?id=<?= $b['build_id'] ?>" class="btn btn-edit">
                  <i class="fa-solid fa-pen"></i> Sửa
                </a>
                <a href="build_delete.php?id=<?= $b['build_id'] ?>" class="btn btn-del" 
                   onclick="return confirm('Xóa cấu hình này?')">
                  <i class="fa-solid fa-trash"></i> Xóa
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="no-builds">
              <i class="fa-solid fa-inbox"></i>
              <p>Chưa có cấu hình nào được tạo.</p>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== FOOTER ===== -->
<footer>
  <p>© <?= date('Y') ?> BuildPC.vn — Máy tính & Linh kiện chính hãng</p>
</footer>

</body>
</html>