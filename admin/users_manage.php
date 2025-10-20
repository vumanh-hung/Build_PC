<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Kiểm tra quyền admin
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo "<h3 style='color:red; text-align:center; margin-top:50px'>Bạn không có quyền truy cập trang này!</h3>";
    exit;
}

// CSRF Token
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$message = '';
$message_type = '';

// ===== XỬ LÝ THÊM/CẬP NHẬT/XÓA NGƯỜI DÙNG =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $csrf) {
        $message = 'Token không hợp lệ';
        $message_type = 'error';
    } else {
        $action = $_POST['action'];

        if ($action === 'add') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $phone = trim($_POST['phone'] ?? '');

            if (empty($username)) {
                $message = 'Tên người dùng không được để trống';
                $message_type = 'error';
            } elseif (empty($email)) {
                $message = 'Email không được để trống';
                $message_type = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Email không hợp lệ';
                $message_type = 'error';
            } elseif (empty($password)) {
                $message = 'Mật khẩu không được để trống';
                $message_type = 'error';
            } elseif (strlen($password) < 6) {
                $message = 'Mật khẩu phải ít nhất 6 ký tự';
                $message_type = 'error';
            } elseif ($password !== $confirm_password) {
                $message = 'Mật khẩu không khớp';
                $message_type = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $message = 'Tên người dùng đã tồn tại';
                        $message_type = 'error';
                    } else {
                        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $message = 'Email đã được sử dụng';
                            $message_type = 'error';
                        } else {
                            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                            $stmt = $pdo->prepare("
                                INSERT INTO users (username, email, password, phone, role, created_at) 
                                VALUES (?, ?, ?, ?, ?, NOW())
                            ");
                            $stmt->execute([$username, $email, $hashed_password, $phone, $role]);

                            $message = "Thêm người dùng '$username' thành công!";
                            $message_type = 'success';
                        }
                    }
                } catch (PDOException $e) {
                    $message = 'Lỗi database: ' . $e->getMessage();
                    $message_type = 'error';
                }
            }
        } 
        elseif ($action === 'edit') {
            $user_id = intval($_POST['user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $phone = trim($_POST['phone'] ?? '');

            if ($user_id <= 0) {
                $message = 'ID người dùng không hợp lệ';
                $message_type = 'error';
            } elseif (empty($username)) {
                $message = 'Tên người dùng không được để trống';
                $message_type = 'error';
            } elseif (empty($email)) {
                $message = 'Email không được để trống';
                $message_type = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Email không hợp lệ';
                $message_type = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $old_user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$old_user) {
                        $message = 'Người dùng không tồn tại';
                        $message_type = 'error';
                    } else {
                        if ($username !== $old_user['username']) {
                            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
                            $stmt->execute([$username]);
                            if ($stmt->fetch()) {
                                $message = 'Tên người dùng đã tồn tại';
                                $message_type = 'error';
                            }
                        }

                        if (empty($message) && $email !== $old_user['email']) {
                            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                            $stmt->execute([$email]);
                            if ($stmt->fetch()) {
                                $message = 'Email đã được sử dụng';
                                $message_type = 'error';
                            }
                        }

                        if (empty($message)) {
                            if (!empty($password)) {
                                if (strlen($password) < 6) {
                                    $message = 'Mật khẩu phải ít nhất 6 ký tự';
                                    $message_type = 'error';
                                } elseif ($password !== $confirm_password) {
                                    $message = 'Mật khẩu không khớp';
                                    $message_type = 'error';
                                } else {
                                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                                    $stmt = $pdo->prepare("
                                        UPDATE users 
                                        SET username = ?, email = ?, password = ?, phone = ?, role = ?, updated_at = NOW()
                                        WHERE user_id = ?
                                    ");
                                    $stmt->execute([$username, $email, $hashed_password, $phone, $role, $user_id]);
                                    $message = "Cập nhật người dùng '$username' thành công!";
                                    $message_type = 'success';
                                }
                            } else {
                                $stmt = $pdo->prepare("
                                    UPDATE users 
                                    SET username = ?, email = ?, phone = ?, role = ?, updated_at = NOW()
                                    WHERE user_id = ?
                                ");
                                $stmt->execute([$username, $email, $phone, $role, $user_id]);
                                $message = "Cập nhật người dùng '$username' thành công!";
                                $message_type = 'success';
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $message = 'Lỗi khi cập nhật: ' . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
        elseif ($action === 'delete') {
            $user_id = intval($_POST['user_id'] ?? 0);

            if ($user_id <= 0) {
                $message = 'ID người dùng không hợp lệ';
                $message_type = 'error';
            } elseif ($user_id === $_SESSION['user']['user_id']) {
                $message = 'Không thể xóa tài khoản của chính mình';
                $message_type = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$user) {
                        $message = 'Người dùng không tồn tại';
                        $message_type = 'error';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                        $stmt->execute([$user_id]);

                        $message = "Xóa người dùng '{$user['username']}' thành công!";
                        $message_type = 'success';
                    }
                } catch (PDOException $e) {
                    $message = 'Lỗi khi xóa: ' . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
    }
}

// Lấy danh sách người dùng
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin người dùng để chỉnh sửa
$edit_user = null;
if (isset($_GET['edit'])) {
    $user_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

$total_users = count($users);
$admin_count = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$user_count = $total_users - $admin_count;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người dùng - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .header h1 {
            color: #667eea;
            font-size: 32px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-count {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
        }

        .message {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: none;
            animation: slideDown 0.3s ease-out;
            border-left: 5px solid;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .message.show { display: block; }

        .message.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left-color: #28a745;
        }

        .message.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left-color: #f44336;
        }

        @keyframes slideDown {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card i {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ===== FORM SECTION ===== */
        .form-section {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .form-section h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="tel"],
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group.half {
            margin-bottom: 0;
        }

        .password-hint {
            color: #999;
            font-size: 13px;
            margin-top: 8px;
            font-style: italic;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            flex: 1;
            min-width: 200px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-reset {
            background: #e0e0e0;
            color: #333;
            min-width: 120px;
        }

        .btn-reset:hover {
            background: #d0d0d0;
        }

        .btn-cancel {
            background: #ff9800;
            color: white;
            text-decoration: none;
            min-width: 120px;
        }

        .btn-cancel:hover {
            background: #e68900;
        }

        /* ===== USERS TABLE ===== */
        .users-section {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .users-section h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }

        table tbody tr:hover {
            background: #f9f9f9;
        }

        table td {
            padding: 16px;
            font-size: 14px;
        }

        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .badge-admin {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1a73e8;
        }

        .badge-user {
            background: linear-gradient(135deg, #fff8e1, #ffe082);
            color: #9e9800;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-edit {
            background: linear-gradient(135deg, #4caf50, #45a049);
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        .btn-delete {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-delete:hover:not(:disabled) {
            background: linear-gradient(135deg, #d32f2f, #b71c1c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
        }

        .btn-delete:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .no-users {
            text-align: center;
            padding: 60px 20px;
        }

        .no-users p {
            color: #999;
            font-size: 16px;
        }

        .no-users a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .header h1 { font-size: 24px; }
            .form-section { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .password-group { grid-template-columns: 1fr; }
            .btn-group { flex-direction: column; }
            .btn { width: 100%; }
            table { font-size: 12px; }
            table th, table td { padding: 10px; }
            .actions { flex-direction: column; width: 100%; }
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> Quản lý Người dùng</h1>
            <div class="user-count"><i class="fas fa-database"></i> <?= $total_users ?> người dùng</div>
        </div>

        <?php if ($message): ?>
        <div class="message show <?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3>Tổng Người Dùng</h3>
                <div class="number"><?= $total_users ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-shield"></i>
                <h3>Quản Trị Viên</h3>
                <div class="number"><?= $admin_count ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user"></i>
                <h3>Người Dùng Thường</h3>
                <div class="number"><?= $user_count ?></div>
            </div>
        </div>

        <div class="form-section">
            <h2>
                <i class="fas fa-<?php echo $edit_user ? 'edit' : 'plus-circle'; ?>"></i>
                <?php echo $edit_user ? 'Chỉnh sửa người dùng' : 'Thêm người dùng mới'; ?>
            </h2>

            <form method="POST" id="userForm">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="<?php echo $edit_user ? 'edit' : 'add'; ?>">
                <?php if ($edit_user): ?>
                <input type="hidden" name="user_id" value="<?= $edit_user['user_id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Tên Người Dùng *</label>
                        <input type="text" id="username" name="username" 
                               placeholder="Ví dụ: admin, user123..." 
                               value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" id="email" name="email" 
                               placeholder="Ví dụ: user@example.com" 
                               value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Số Điện Thoại</label>
                        <input type="tel" id="phone" name="phone" 
                               placeholder="Ví dụ: 0987654321" 
                               value="<?php echo $edit_user ? htmlspecialchars($edit_user['phone'] ?? '') : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="role"><i class="fas fa-shield-alt"></i> Vai Trò *</label>
                        <select id="role" name="role" required>
                            <option value="user" <?php echo (!$edit_user || $edit_user['role'] === 'user') ? 'selected' : ''; ?>>👤 Người Dùng Thường</option>
                            <option value="admin" <?php echo ($edit_user && $edit_user['role'] === 'admin') ? 'selected' : ''; ?>>👨‍💼 Quản Trị Viên</option>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label><i class="fas fa-lock"></i> Mật Khẩu <?php echo !$edit_user ? '*' : ''; ?></label>
                        <div class="password-group">
                            <div class="form-group half">
                                <input type="password" name="password" 
                                       placeholder="Nhập mật khẩu" 
                                       <?php echo !$edit_user ? 'required' : ''; ?>>
                            </div>
                            <div class="form-group half">
                                <input type="password" name="confirm_password" 
                                       placeholder="Xác nhận mật khẩu" 
                                       <?php echo !$edit_user ? 'required' : ''; ?>>
                            </div>
                        </div>
                        <?php if ($edit_user): ?>
                        <div class="password-hint">
                            Bỏ trống nếu không muốn thay đổi mật khẩu
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-check"></i>
                        <?php echo $edit_user ? 'Cập nhật người dùng' : 'Thêm người dùng'; ?>
                    </button>
                    <button type="reset" class="btn btn-reset">
                        <i class="fas fa-redo"></i> Xóa
                    </button>
                    <?php if ($edit_user): ?>
                    <a href="users_manage.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="users-section">
            <h2><i class="fas fa-list"></i> Danh Sách Người Dùng</h2>

            <?php if (count($users) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 8%">ID</th>
                            <th style="width: 20%">Tên Người Dùng</th>
                            <th style="width: 25%">Email</th>
                            <th style="width: 18%">Số Điện Thoại</th>
                            <th style="width: 12%">Vai Trò</th>
                            <th style="width: 12%">Ngày Tạo</th>
                            <th style="width: 5%">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong>#<?= $u['user_id'] ?></strong></td>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></td>
                            <td>
                                <span class="user-badge <?= $u['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                    <i class="fas fa-<?php echo $u['role'] === 'admin' ? 'shield-alt' : 'user'; ?>"></i>
                                    <?php echo $u['role'] === 'admin' ? 'Admin' : 'User'; ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="?edit=<?= $u['user_id'] ?>" class="btn-edit" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này? Hành động này không thể hoàn tác!');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                        <button type="submit" class="btn-delete" title="Xóa"
                                                <?php echo $u['user_id'] === $_SESSION['user']['user_id'] ? 'disabled' : ''; ?>>
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-users">
                <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px; display: block;"></i>
                <p>Chưa có người dùng nào. <a href="users_manage.php">Thêm ngay</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto hide message
        const message = document.querySelector('.message');
        if (message && message.classList.contains('show')) {
            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => message.style.display = 'none', 300);
            }, 5000);
        }

        // Form validation
        const userForm = document.getElementById('userForm');
        userForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (!username) {
                e.preventDefault();
                alert('Vui lòng nhập tên người dùng!');
                document.getElementById('username').focus();
                return;
            }

            if (!email || !email.includes('@')) {
                e.preventDefault();
                alert('Vui lòng nhập email hợp lệ!');
                document.getElementById('email').focus();
                return;
            }
        });
    </script>
</body>
</html>