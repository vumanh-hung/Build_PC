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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang quản trị | BuildPC</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #c2e9fb, #81a4fd);
            margin: 0;
            padding: 0;
        }
        .navbar {
            background: #007bff;
            color: #fff;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            margin-left: 15px;
            text-decoration: none;
            font-weight: bold;
        }
        .content {
            padding: 40px;
        }
        .card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div><strong>BuildPC Admin Panel</strong></div>
        <div>
            Xin chào, <b><?= htmlspecialchars($user['full_name']) ?></b>
            <a href="../page/logout.php">Đăng xuất</a>
        </div>
    </div>

    <div class="content">
        <div class="card">
            <h2>Bảng điều khiển</h2>
            <p>Chào mừng bạn đến trang quản trị BuildPC!</p>

            <ul>
                <li><a href="products.php">Quản lý sản phẩm</a></li>
                <li><a href="brands.php">Quản lý thương hiệu</a></li>
                <li><a href="users.php">Quản lý người dùng</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
