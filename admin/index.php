<?php
session_start();
require_once '../db.php'; // File kết nối cơ sở dữ liệu, có hàm getPDO()

$pdo = getPDO(); // Lấy đối tượng PDO từ db.php

/* ============================================================
   ✅ TẠO ADMIN MẶC ĐỊNH (CHỈ KHI CHƯA CÓ TÀI KHOẢN)
   ============================================================ */
$check = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
if ($check == 0) {
    $defaultEmail = 'admin@example.com';
    $defaultPass = '123456';
    $hash = password_hash($defaultPass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admins (email, password_hash, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$defaultEmail, $hash]);
}

/* ============================================================
   ✅ HÀM KIỂM TRA ĐĂNG NHẬP
   ============================================================ */
function checkLogin($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin && password_verify($password, $admin['password_hash'])) {
        return $admin;
    }
    return false;
}

/* ============================================================
   ✅ XỬ LÝ ĐĂNG XUẤT
   ============================================================ */
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

/* ============================================================
   ✅ NẾU NGƯỜI DÙNG ĐÃ ĐĂNG NHẬP
   ============================================================ */
if (isset($_SESSION['admin'])) {
    $admin = $_SESSION['admin'];
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title>Bảng điều khiển Quản trị viên</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Xin chào, <?php echo htmlspecialchars($admin['email']); ?>!</h2>
                <a href="?logout=true" class="btn btn-danger">Đăng xuất</a>
            </div>

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Bảng điều khiển quản trị</h4>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Tổng quan</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">Đơn hàng</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">Sản phẩm</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">Người dùng</button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="adminTabsContent">
                        <!-- Tổng quan -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <?php
                            $countOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                            $countProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                            $countUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                            ?>
                            <p>Tổng số đơn hàng: <strong><?php echo $countOrders; ?></strong></p>
                            <p>Tổng số sản phẩm: <strong><?php echo $countProducts; ?></strong></p>
                            <p>Tổng số người dùng: <strong><?php echo $countUsers; ?></strong></p>
                        </div>

                        <!-- Đơn hàng -->
                        <div class="tab-pane fade" id="orders" role="tabpanel">
                            <h5>15 đơn hàng gần nhất</h5>
                            <table class="table table-striped">
                                <thead><tr><th>ID</th><th>Khách hàng</th><th>Tổng tiền</th><th>Ngày đặt</th></tr></thead>
                                <tbody>
                                <?php
                                foreach ($pdo->query("SELECT id, user_id, total_amount, created_at FROM orders ORDER BY created_at DESC LIMIT 15") as $row) {
                                    echo "<tr><td>{$row['id']}</td><td>{$row['user_id']}</td><td>{$row['total_amount']}</td><td>{$row['created_at']}</td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Sản phẩm -->
                        <div class="tab-pane fade" id="products" role="tabpanel">
                            <h5>Danh sách 25 sản phẩm mới nhất</h5>
                            <table class="table table-striped">
                                <thead><tr><th>ID</th><th>Tên</th><th>Giá</th><th>Ngày tạo</th></tr></thead>
                                <tbody>
                                <?php
                                foreach ($pdo->query("SELECT id, name, price, created_at FROM products ORDER BY created_at DESC LIMIT 25") as $row) {
                                    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['price']}</td><td>{$row['created_at']}</td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Người dùng -->
                        <div class="tab-pane fade" id="users" role="tabpanel">
                            <h5>Danh sách 25 người dùng mới nhất</h5>
                            <table class="table table-striped">
                                <thead><tr><th>ID</th><th>Email</th><th>Ngày đăng ký</th></tr></thead>
                                <tbody>
                                <?php
                                foreach ($pdo->query("SELECT id, email, created_at FROM users ORDER BY created_at DESC LIMIT 25") as $row) {
                                    echo "<tr><td>{$row['id']}</td><td>{$row['email']}</td><td>{$row['created_at']}</td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

/* ============================================================
   ✅ XỬ LÝ ĐĂNG NHẬP
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $admin = checkLogin($email, $password);
    if ($admin) {
        $_SESSION['admin'] = $admin;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Sai email hoặc mật khẩu!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập Quản trị viên</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Đăng nhập Quản trị viên</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
                        </form>
                        <div class="mt-3 text-muted small">
                            <p>Email mặc định: <code>admin@example.com</code><br>
                            Mật khẩu: <code>123456</code></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
