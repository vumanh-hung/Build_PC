<?php
require_once __DIR__ . '/../db.php';
$conn = getPDO();
session_start();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $full_name = trim($_POST["full_name"] ?? "");

    if ($username === "" || $email === "" || $password === "" || $full_name === "") {
        $error = "⚠️ Vui lòng nhập đầy đủ thông tin!";
    } else {
        try {
            // Kiểm tra trùng username hoặc email
            $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            
            if ($check->fetch()) {
                $error = "⚠️ Tên đăng nhập hoặc email đã tồn tại!";
            } else {
                // Mã hóa mật khẩu
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $role = "customer";

                $insert = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, role, created_at)
                                          VALUES (?, ?, ?, ?, ?, NOW())");
                $insert->execute([$username, $email, $hash, $full_name, $role]);

                $success = "✅ Tạo tài khoản thành công! <a href='login.php'>Đăng nhập ngay</a>";
            }
        } catch (PDOException $e) {
            $error = "❌ Lỗi hệ thống: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký tài khoản</title>
    <link rel="icon" href="../assets/images/icon.png">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #5cb8ff, #007bff);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .register-box {
            background: #fff;
            border-radius: 16px;
            padding: 40px 35px;
            width: 420px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            text-align: center;
        }

        h2 {
            color: #007bff;
            margin-bottom: 25px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            font-size: 15px;
            transition: 0.3s;
        }

        input:focus {
            border-color: #007bff;
            box-shadow: 0 0 6px rgba(0,123,255,0.3);
        }

        button {
            width: 100%;
            background: linear-gradient(90deg, #007bff, #0099ff);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        .error {
            color: #ff3333;
            margin-bottom: 10px;
            background: #ffe5e5;
            padding: 8px;
            border-radius: 6px;
        }

        .success {
            color: #009900;
            margin-bottom: 10px;
            background: #e6ffe6;
            padding: 8px;
            border-radius: 6px;
        }

        .login-link {
            margin-top: 15px;
            display: block;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="register-box">
    <h2>🧠 BuildPC.vn</h2>
    <h3>Tạo tài khoản mới</h3>

    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Tên đăng nhập" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="full_name" placeholder="Họ và tên" required>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <button type="submit">Đăng ký</button>
    </form>

    <a href="login.php" class="login-link">Đã có tài khoản? Đăng nhập ngay</a>
</div>
</body>
</html>