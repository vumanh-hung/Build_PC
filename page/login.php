<?php
session_start();
require_once '../db.php'; // Đảm bảo file này tồn tại đúng đường dẫn

// Nếu đã đăng nhập, chuyển hướng thẳng vào trang admin
if (isset($_SESSION['user'])) {
    header('Location: admin.php');
    exit;
}

// Khởi tạo biến lỗi
$error = "";

// Xử lý khi nhấn nút đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dùng toán tử ?? để tránh lỗi undefined key
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Kiểm tra trống
    if ($username === '' || $password === '') {
        $error = "⚠️ Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!";
    } else {
        // Truy vấn người dùng
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // So sánh mật khẩu thuần (không mã hoá)
        if ($user && $password === $user['password_hash']) {
            $_SESSION['user'] = [
                'user_id'   => $user['user_id'],
                'username'  => $user['username'],
                'full_name' => $user['full_name'],
                'role'      => $user['role']
            ];

            // Chuyển hướng theo vai trò
            if ($user['role'] === 'admin') {
                header('Location: ../admin.php');
            } else {
                header('Location: ../index.php');
            }
            exit;
        } else {
            $error = "⚠️ Sai tên đăng nhập hoặc mật khẩu!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập hệ thống</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #3a8ef6, #70c1ff);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-box {
            background: #fff;
            border-radius: 12px;
            padding: 40px 50px;
            width: 380px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            text-align: center;
        }
        .login-box h2 {
            color: #007bff;
            margin-bottom: 25px;
        }
        .login-box input {
            width: 100%;
            padding: 10px;
            margin: 8px 0 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            outline: none;
            font-size: 15px;
        }
        .login-box button {
            width: 100%;
            background: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        .login-box button:hover {
            background: #0056b3;
        }
        .error {
            color: red;
            margin-bottom: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="login-box">
    <h2>Đăng nhập hệ thống</h2>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST" action="">
        <label>Tên đăng nhập</label>
        <input type="text" name="username" required placeholder="Nhập tên đăng nhập">

        <label>Mật khẩu</label>
        <input type="password" name="password" required placeholder="Nhập mật khẩu">

        <button type="submit">Đăng nhập</button>
    </form>
</div>
</body>
</html>
