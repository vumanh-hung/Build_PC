<?php
session_start();
require_once '../db.php';

// Kiểm tra nếu chưa đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: ../page/login.php');
    exit;
}

// Kiểm tra quyền admin
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$user = $_SESSION['user'];

// ===== Lấy số liệu thống kê =====
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $total_products = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM brands");
    $total_brands = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $total_categories = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_products = $total_brands = $total_users = $total_categories = 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang quản trị | BuildPC</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI", sans-serif;}
        body { background: #f5f7fa; color:#333; }

        /* Navbar */
        .navbar {
            background: #1a73e8;
            color: #fff;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
        }
        .navbar a { color:#fff; margin-left:15px; text-decoration:none; font-weight:bold; }
        .navbar a:hover { text-decoration: underline; }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: 220px;
            height: calc(100% - 60px);
            background: #fff;
            border-right: 1px solid #ddd;
            padding-top: 20px;
        }
        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            margin-bottom: 5px;
            border-radius: 6px;
            transition: 0.2s;
        }
        .sidebar a:hover { background: #1a73e8; color: #fff; }

        /* Content */
        .content {
            margin-left: 240px;
            padding: 80px 40px 40px 40px;
        }

        h1 { margin-bottom: 30px; color:#1a73e8; }

        /* Cards */
        .card-container { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 40px; }
        .card {
            flex: 1 1 200px;
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }
        .card:hover { transform: translateY(-5px); }
        .card h3 { font-size: 28px; margin-bottom: 10px; color:#1a73e8; }
        .card p { font-size: 16px; color:#555; }

        /* Quick Links */
        .quick-links {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .quick-links ul { list-style:none; }
        .quick-links li { margin-bottom: 12px; }
        .quick-links a {
            text-decoration:none;
            color:#1a73e8;
            font-weight:600;
        }
        .quick-links a:hover { text-decoration:underline; }

        @media(max-width:768px){
            .sidebar{position:relative;width:100%;height:auto;padding-top:0;}
            .content{margin-left:0;padding:120px 20px 20px 20px;}
            .card-container{flex-direction:column;}
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div><strong>ADMIN </strong></div>
        <div>
            Xin chào, <b><?= htmlspecialchars($user['full_name']) ?></b>
            <a href="../page/logout.php">Đăng xuất</a>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="products_manage.php">📦 Quản lý sản phẩm</a>
        <a href="brands_manage.php">🏷️ Quản lý thương hiệu</a>
        <a href="categories_manage.php">📁 Quản lý danh mục</a>
        <a href="users_manage.php">👤 Quản lý người dùng</a>
        <a href="orders_manage.php">🛒 Quản lý đơn hàng</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h1>Bảng điều khiển</h1>

        <div class="card-container">
            <div class="card">
                <h3><?= $total_products ?></h3>
                <p>Sản phẩm</p>
            </div>
            <div class="card">
                <h3><?= $total_brands ?></h3>
                <p>Thương hiệu</p>
            </div>
            <div class="card">
                <h3><?= $total_categories ?></h3>
                <p>Danh mục</p>
            </div>
            <div class="card">
                <h3><?= $total_users ?></h3>
                <p>Người dùng</p>
            </div>
        </div>

    
    </div>
</body>
</html>
