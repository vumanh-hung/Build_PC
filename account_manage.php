<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';

// ===== Kiểm tra đăng nhập & quyền =====
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo "<h3 style='color:red; text-align:center; margin-top:50px'>🚫 Bạn không có quyền truy cập trang này!</h3>";
    exit;
}

// ===== CSRF =====
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$message = '';
$message_type = '';

// ===== XỬ LÝ XÓA TÀI KHOẢN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $csrf) {
        $message = '❌ Token không hợp lệ!';
        $message_type = 'error';
    } else {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        // Không cho xóa chính mình
        if ($user_id === $_SESSION['user']['user_id']) {
            $message = '❌ Bạn không thể xóa tài khoản của chính mình!';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'");
                $stmt->execute([$user_id]);
                if ($stmt->rowCount() > 0) {
                    $message = "✅ Đã xóa tài khoản #$user_id thành công!";
                    $message_type = 'success';
                } else {
                    $message = '❌ Không thể xóa tài khoản admin!';
                    $message_type = 'error';
                }
            } catch (PDOException $e) {
                $message = '❌ Lỗi khi xóa: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// ===== XỬ LÝ CẬP NHẬT VAI TRÒ =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $csrf) {
        $message = '❌ Token không hợp lệ!';
        $message_type = 'error';
    } else {
        $user_id = intval($_POST['user_id'] ?? 0);
        $role = trim($_POST['role'] ?? '');
        
        $allowed_roles = ['user', 'admin'];
        if (!in_array($role, $allowed_roles)) {
            $message = '❌ Vai trò không hợp lệ!';
            $message_type = 'error';
        } elseif ($user_id === $_SESSION['user']['user_id']) {
            $message = '❌ Bạn không thể thay đổi vai trò của chính mình!';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$role, $user_id]);
                $message = "✅ Cập nhật vai trò tài khoản #$user_id thành công!";
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = '❌ Lỗi khi cập nhật: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// ===== XỬ LÝ KHÓA/MỞ KHÓA TÀI KHOẢN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $csrf) {
        $message = '❌ Token không hợp lệ!';
        $message_type = 'error';
    } else {
        $user_id = intval($_POST['user_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        
        $allowed_status = ['active', 'blocked'];
        if (!in_array($status, $allowed_status)) {
            $message = '❌ Trạng thái không hợp lệ!';
            $message_type = 'error';
        } elseif ($user_id === $_SESSION['user']['user_id']) {
            $message = '❌ Bạn không thể khóa tài khoản của chính mình!';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$status, $user_id]);
                $action_text = $status === 'blocked' ? 'Đã khóa' : 'Đã mở khóa';
                $message = "✅ $action_text tài khoản #$user_id thành công!";
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = '❌ Lỗi khi cập nhật: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// ===== LẤY DANH SÁCH TÀI KHOẢN =====
$filter_role = $_GET['role'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

try {
    $sql = "SELECT user_id, username, email, role, status, created_at, updated_at FROM users WHERE 1=1";
    $params = [];
    
    if ($filter_role !== 'all') {
        $sql .= " AND role = ?";
        $params[] = $filter_role;
    }
    
    if ($filter_status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $filter_status;
    }
    
    if ($search !== '') {
        $sql .= " AND (username LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $message = '❌ Lỗi truy vấn dữ liệu: ' . $e->getMessage();
    $message_type = 'error';
}

// Thống kê
$total_users = count($users);
$admin_count = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$user_count = count(array_filter($users, fn($u) => $u['role'] === 'user'));
$blocked_count = count(array_filter($users, fn($u) => $u['status'] === 'blocked'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tài khoản - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {margin:0; padding:0; box-sizing:border-box;}
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: auto;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #2d3748;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 i {
            color: #667eea;
            font-size: 36px;
        }
        
        .breadcrumb {
            color: #718096;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .stat-card.blue::before {background: linear-gradient(180deg, #667eea, #764ba2);}
        .stat-card.green::before {background: linear-gradient(180deg, #48bb78, #38a169);}
        .stat-card.orange::before {background: linear-gradient(180deg, #ed8936, #dd6b20);}
        .stat-card.red::before {background: linear-gradient(180deg, #f56565, #e53e3e);}
        
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-card.blue .icon {background: linear-gradient(135deg, #667eea20, #764ba220); color: #667eea;}
        .stat-card.green .icon {background: linear-gradient(135deg, #48bb7820, #38a16920); color: #48bb78;}
        .stat-card.orange .icon {background: linear-gradient(135deg, #ed893620, #dd6b2020); color: #ed8936;}
        .stat-card.red .icon {background: linear-gradient(135deg, #f5656520, #e53e3e20); color: #f56565;}
        
        .stat-card .label {
            color: #718096;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-card .value {
            color: #2d3748;
            font-size: 28px;
            font-weight: 700;
        }
        
        /* Message */
        .message {
            padding: 18px 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: none;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {transform: translateY(-20px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        
        .message.show {display: flex;}
        .message.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .message.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .message i {font-size: 20px;}
        
        /* Users Section */
        .users-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border-bottom: 2px solid #e2e8f0;
        }
        
        .section-header h2 {
            color: #2d3748;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Filters */
        .filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-group label {
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
        }
        
        .filter-group select {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: #4a5568;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-box {
            position: relative;
            flex: 1;
            max-width: 300px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }
        
        .btn-filter {
            padding: 10px 18px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        /* Table */
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        table th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        table td {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
            font-size: 14px;
        }
        
        table tbody tr {
            transition: all 0.2s ease;
        }
        
        table tbody tr:hover {
            background: #f7fafc;
        }
        
        .user-id {
            color: #667eea;
            font-weight: 700;
            font-size: 15px;
        }
        
        .user-info strong {
            color: #2d3748;
            display: block;
            margin-bottom: 3px;
        }
        
        .user-info small {
            color: #a0aec0;
            font-size: 12px;
        }
        
        /* Role Badge */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-admin {background: linear-gradient(135deg, #ed893620, #dd6b2020); color: #ed8936;}
        .role-user {background: linear-gradient(135deg, #667eea20, #764ba220); color: #667eea;}
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {background: #d4edda; color: #155724;}
        .status-blocked {background: #f8d7da; color: #721c24;}
        
        /* Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }
        
        .btn-edit:hover {
            background: linear-gradient(135deg, #38a169, #2f855a);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 187, 120, 0.3);
        }
        
        .btn-block {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
            color: white;
        }
        
        .btn-block:hover {
            background: linear-gradient(135deg, #dd6b20, #c05621);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(237, 137, 54, 0.3);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
        }
        
        .btn-delete:hover {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 101, 101, 0.3);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            max-width: 450px;
            width: 90%;
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from {transform: translateY(50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .modal-header i {
            font-size: 32px;
            color: #f56565;
        }
        
        .modal-header h3 {
            color: #2d3748;
            font-size: 20px;
        }
        
        .modal-body {
            color: #4a5568;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn-cancel {
            padding: 10px 20px;
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-cancel:hover {
            background: #cbd5e0;
        }
        
        .btn-confirm {
            padding: 10px 20px;
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-confirm:hover {
            background: linear-gradient(135deg, #e53e3e, #c53030);
        }
        
        /* No Users */
        .no-users {
            text-align: center;
            padding: 80px 20px;
            color: #a0aec0;
        }
        
        .no-users i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #cbd5e0;
        }
        
        .no-users h3 {
            color: #718096;
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        /* Responsive */
        @media(max-width: 768px) {
            body {padding: 15px;}
            .header {padding: 20px;}
            .header h1 {font-size: 24px;}
            .stats-grid {grid-template-columns: 1fr;}
            .filters {flex-direction: column; align-items: stretch;}
            .search-box {max-width: 100%;}
            table {font-size: 12px;}
            table th, table td {padding: 12px 10px;}
            .action-buttons {flex-direction: column;}
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-users-cog"></i> Quản lý Tài khoản</h1>
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a> 
                <i class="fas fa-chevron-right" style="font-size:10px; margin:0 5px;"></i> 
                Tài khoản
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="label">Tổng tài khoản</div>
                <div class="value"><?= number_format($total_users) ?></div>
            </div>
            <div class="stat-card orange">
                <div class="icon"><i class="fas fa-user-shield"></i></div>
                <div class="label">Quản trị viên</div>
                <div class="value"><?= $admin_count ?></div>
            </div>
            <div class="stat-card green">
                <div class="icon"><i class="fas fa-user"></i></div>
                <div class="label">Người dùng</div>
                <div class="value"><?= $user_count ?></div>
            </div>
            <div class="stat-card red">
                <div class="icon"><i class="fas fa-user-lock"></i></div>
                <div class="label">Tài khoản bị khóa</div>
                <div class="value"><?= $blocked_count ?></div>
            </div>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
            <div class="message show <?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= $message ?></span>
            </div>
        <?php endif; ?>

        <!-- Users Section -->
        <div class="users-section">
            <div class="section-header">
                <h2><i class="fas fa-list"></i> Danh sách Tài khoản</h2>
                
                <form method="GET" class="filters">
                    <div class="filter-group">
                        <label><i class="fas fa-user-tag"></i> Vai trò:</label>
                        <select name="role" onchange="this.form.submit()">
                            <option value="all" <?= $filter_role === 'all' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="user" <?= $filter_role === 'user' ? 'selected' : '' ?>>User</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-toggle-on"></i> Trạng thái:</label>
                        <select name="status" onchange="this.form.submit()">
                            <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                            <option value="blocked" <?= $filter_status === 'blocked' ? 'selected' : '' ?>>Bị khóa</option>
                        </select>
                    </div>
                    
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm theo tên hoặc email...">
                    </div>
                    
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                </form>
            </div>

            <?php if (count($users) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user"></i> Thông tin</th>
                            <th><i class="fas fa-user-tag"></i> Vai trò</th>
                            <th><i class="fas fa-toggle-on"></i> Trạng thái</th>
                            <th><i class="fas fa-calendar"></i> Ngày tạo</th>
                            <th><i class="fas fa-cog"></i> Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="user-id">#<?= $u['user_id'] ?></td>
                            <td class="user-info">
                                <strong><?= htmlspecialchars($u['username']) ?></strong>
                                <small><?= htmlspecialchars($u['email']) ?></small>
                            </td>
                            <td>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <select name="role" onchange="this.form.submit()" 
                                            <?= $u['user_id'] === $_SESSION['user']['user_id'] ? 'disabled' : '' ?>>
                                        <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>👑 Admin</option>
                                        <option value="user" <?= $u['role']=='user'?'selected':'' ?>>👤 User</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $u['status'] ?>">
                                    <i class="fas fa-<?= $u['status'] === 'active' ? 'check-circle' : 'ban' ?>"></i>
                                    <?= $u['status'] === 'active' ? 'Hoạt động' : 'Bị khóa' ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <!-- Toggle Status -->
                                    <?php if ($u['user_id'] !== $_SESSION['user']['user_id']): ?>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                        <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'blocked' : 'active' ?>">
                                        <button type="submit" class="btn btn-block">
                                            <i class="fas fa-<?= $u['status'] === 'active' ? 'lock' : 'unlock' ?>"></i>
                                            <?= $u['status'] === 'active' ? 'Khóa' : 'Mở' ?>
                                        </button>
                                    </form>
                                    
                                    <!-- Delete -->
                                    <?php if ($u['role'] !== 'admin'): ?>
                                    <button type="button" class="btn btn-delete" 
                                            onclick="showDeleteModal(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="no-users">
                    <i class="fas fa-user-slash"></i>
                    <h3>Không tìm thấy tài khoản</h3>
                    <p>Thử điều chỉnh bộ lọc hoặc tìm kiếm khác</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Xác nhận xóa tài khoản</h3>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa tài khoản <strong id="deleteUsername"></strong>?</p>
                <p style="color:#e53e3e; margin-top:10px;">⚠️ Hành động này không thể hoàn tác!</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="hideDeleteModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <form method="POST" style="display:inline-block;" id="deleteForm">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn-confirm">
                        <i class="fas fa-trash"></i> Xóa ngay
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Message auto hide
        const msg = document.querySelector('.message');
        if (msg && msg.classList.contains('show')) {
            setTimeout(() => {
                msg.style.opacity = '0';
                msg.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => msg.style.display = 'none', 300);
            }, 5000);
        }

        // Delete Modal
        function showDeleteModal(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUsername').textContent = username;
            document.getElementById('deleteModal').classList.add('show');
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteModal();
            }
        });

        // Confirm before status change
        document.querySelectorAll('select[name="role"]').forEach(select => {
            select.addEventListener('change', function(e) {
                const newRole = this.value;
                const confirmed = confirm(`Bạn có chắc muốn thay đổi vai trò thành ${newRole === 'admin' ? 'Admin' : 'User'}?`);
                if (!confirmed) {
                    e.preventDefault();
                    this.value = this.getAttribute('data-original') || this.value;
                    return false;
                }
                this.style.opacity = '0.5';
                this.disabled = true;
            });
            select.setAttribute('data-original', select.value);
        });

        // Confirm before toggle status
        document.querySelectorAll('form button.btn-block').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const action = this.textContent.includes('Khóa') ? 'khóa' : 'mở khóa';
                const confirmed = confirm(`Bạn có chắc muốn ${action} tài khoản này?`);
                if (!confirmed) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // Loading animation for form submission
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                if (btn && !btn.classList.contains('btn-cancel')) {
                    btn.style.opacity = '0.6';
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                }
            });
        });

        // Search highlight
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput && searchInput.value) {
            const searchTerm = searchInput.value.toLowerCase();
            document.querySelectorAll('table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.background = 'rgba(102, 126, 234, 0.05)';
                }
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC to close modal
            if (e.key === 'Escape') {
                hideDeleteModal();
            }
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.querySelector('input[name="search"]')?.focus();
            }
        });

        // Auto-refresh stats (optional)
        let autoRefreshEnabled = false;
        function toggleAutoRefresh() {
            autoRefreshEnabled = !autoRefreshEnabled;
            if (autoRefreshEnabled) {
                setInterval(() => {
                    fetch(window.location.href)
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            // Update stats only
                            document.querySelectorAll('.stat-card .value').forEach((el, i) => {
                                const newValue = doc.querySelectorAll('.stat-card .value')[i]?.textContent;
                                if (newValue && el.textContent !== newValue) {
                                    el.style.transform = 'scale(1.2)';
                                    el.textContent = newValue;
                                    setTimeout(() => el.style.transform = 'scale(1)', 300);
                                }
                            });
                        });
                }, 30000); // Refresh every 30 seconds
            }
        }

        // Smooth scroll to top
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Add scroll to top button if page is long
        if (document.body.scrollHeight > window.innerHeight * 2) {
            const scrollBtn = document.createElement('button');
            scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            scrollBtn.style.cssText = `
                position: fixed;
                bottom: 30px;
                right: 30px;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                border: none;
                cursor: pointer;
                box-shadow: 0 5px 20px rgba(0,0,0,0.2);
                display: none;
                z-index: 999;
                transition: all 0.3s ease;
            `;
            scrollBtn.onclick = scrollToTop;
            document.body.appendChild(scrollBtn);

            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    scrollBtn.style.display = 'block';
                } else {
                    scrollBtn.style.display = 'none';
                }
            });
        }

        // Table row animation on load
        document.querySelectorAll('table tbody tr').forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            setTimeout(() => {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 50);
        });
    </script>
</body>
</html>