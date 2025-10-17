<?php
session_start();
require_once '../includes/auth.php';

// Nếu đã đăng nhập rồi → chuyển hướng
if (isset($_SESSION['user'])) {
    header('Location: ../admin.php');
    exit;
}

$error = "";

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "⚠️ Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!";
    } else {
        $user = authenticate_user($username, $password);
        if ($user) {
            login_user_session($user);

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
    <link rel="icon" href="../assets/images/icon.png">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #42a5f5, #1e88e5);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            background: #fff;
            border-radius: 16px;
            padding: 40px 35px;
            width: 380px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            text-align: center;
        }

        .logo {
            font-size: 26px;
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 20px;
        }

        h2 {
            color: #1565c0;
            margin-bottom: 25px;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            margin: 10px 0 18px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            transition: 0.3s;
        }

        input:focus {
            border-color: #1976d2;
            box-shadow: 0 0 6px rgba(25,118,210,0.4);
        }

        button {
            width: 100%;
            background: linear-gradient(90deg, #1976d2, #2196f3);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, background 0.3s;
        }

        button:hover {
            transform: scale(1.03);
            background: linear-gradient(90deg, #1565c0, #1e88e5);
        }

        .error {
            color: #e53935;
            margin-bottom: 10px;
            background: #ffe5e5;
            padding: 8px;
            border-radius: 6px;
        }

        /* --- Nút đăng ký --- */
        .register-link {
            display: inline-block;
            margin-top: 18px;
            padding: 10px 0;
            width: 100%;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .register-link:hover {
            background: #bbdefb;
        }

    </style>
</head>
<body>
<div class="login-box">
    <div class="logo">🧠 BuildPC.vn</div>
    <h2>Đăng nhập hệ thống</h2>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Tên đăng nhập" required>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <button type="submit">Đăng nhập</button>
    </form>

    <!-- Nút chuyển sang đăng ký -->
    <a href="register.php" class="register-link">Tạo tài khoản mới</a>
</div>
</body>
</html>