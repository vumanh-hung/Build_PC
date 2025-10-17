<?php
session_start();
require_once '../db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Kiểm tra quyền admin
if ($_SESSION['user']['role'] !== 'admin') {
    echo "<h3 style='color:red; text-align:center; margin-top:50px'>🚫 Bạn không có quyền truy cập trang này!</h3>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang quản trị</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            margin: 0;
            padding: 0;
            color: #333;
        }
        header {
            background: rgba(255, 255, 255, 0.9);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        header h1 {
            color: #2575fc;
            margin: 0;
            font-size: 22px;
        }
        header a {
            background: #2575fc;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            transition: 0.3s;
        }
        header a:hover {
            background: #1a5ed8;
        }
        main {
            padding: 40px;
            text-align: center;
        }
        .card {
            display: inline-block;
            width: 250px;
            height: 150px;
            margin: 15px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: 0.3s;
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card h3 {
            margin-top: 50px;
            color: #2575fc;
        }
    </style>
</head>
<body>
    <header>
        <h1>👑 Trang quản trị hệ thống</h1>
        <div>
            <span>Xin chào, <strong><?= htmlspecialchars($_SESSION['user']['full_name']); ?></strong></span>
            <a href="logout.php">Đăng xuất</a>
        </div>
    </header>

    <main>
        <h2>📂 Quản lý hệ thống</h2>
        <div class="card"><h3>Quản lý sản phẩm</h3></div>
        <div class="card"><h3>Quản lý thương hiệu</h3></div>
        <div class="card"><h3>Quản lý nhân viên</h3></div>
        <div class="card"><h3>Quản lý tài khoản</h3></div>
    </main>
</body>
</html>
