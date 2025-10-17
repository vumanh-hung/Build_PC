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
        /* ===== Toàn trang ===== */
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #5cb8ff, #007bff);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            animation: fadeIn 0.8s ease-in;
        }

        /* ===== Khung chính ===== */
        .login-box {
            background: #ffffff;
            border-radius: 16px;
            padding: 50px 45px;
            width: 400px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            text-align: center;
            animation: slideUp 0.9s ease-out;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .login-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        /* ===== Logo & tiêu đề ===== */
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .logo img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        h2 {
            color: #007bff;
            margin-bottom: 25px;
        }

        /* ===== Input ===== */
        .login-box input {
            width: 100%;
            padding: 12px 14px;
            margin: 8px 0 18px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .login-box input:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0,123,255,0.4);
        }

        /* ===== Nút đăng nhập ===== */
        .login-box button {
            width: 100%;
            background: linear-gradient(90deg, #007bff, #0099ff);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.4s, transform 0.2s;
        }

        .login-box button:hover {
            background: linear-gradient(90deg, #0056b3, #0088ff);
            transform: scale(1.03);
        }

        /* ===== Lỗi ===== */
        .error {
            color: #ff3333;
            margin-bottom: 10px;
            font-size: 14px;
            background: #ffe5e5;
            padding: 8px;
            border-radius: 6px;
        }

        /* ===== Hiệu ứng animation ===== */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* ===== Responsive ===== */
        @media (max-width: 480px) {
            .login-box {
                width: 90%;
                padding: 35px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            🧠 <span>BuildPC.vn</span>
        </div>
        <h2>Đăng nhập hệ thống</h2>

        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Tên đăng nhập" required>
            <input type="password" name="password" placeholder="Mật khẩu" required>
            <button type="submit">Đăng nhập</button>
        </form>
    </div>
</body>
</html>
