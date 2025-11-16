<?php

/**
 * page/login.php - Login Page
 * Trang đăng nhập hệ thống
 */

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', 'localhost');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    $redirect = $_SESSION['user']['role'] === 'admin' ? 'admin.php' : '../index.php';
    header("Location: $redirect");
    exit;
}

// Error message
$error = "";

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!";
    } else {
        $user = authenticate_user($username, $password);

        if ($user) {
            login_user_session($user);

            $redirect = $user['role'] === 'admin' ? 'admin.php' : '../index.php';
            header("Location: $redirect");
            exit;
        } else {
            $error = "Sai tên đăng nhập hoặc mật khẩu!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - BuildPC.vn</title>
    <link rel="icon" href="../assets/images/icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css?v=1.0">
    <link rel="stylesheet" href="../assets/css/auth-blue.css?v=1.0">
</head>

<body>
    <!-- Background Animation -->
    <div class="auth-background">
        <div class="bg-shape shape-1"></div>
        <div class="bg-shape shape-2"></div>
        <div class="bg-shape shape-3"></div>
    </div>

    <!-- Login Container -->
    <div class="auth-container">
        <div class="auth-box">
            <!-- Logo & Header -->
            <div class="auth-header">
                <div class="logo">
                    <i class="fa-solid fa-desktop"></i>
                    <span>BuildPC.vn</span>
                </div>
                <h1 class="auth-title">Đăng nhập</h1>
                <p class="auth-subtitle">Chào mừng bạn quay trở lại!</p>
            </div>

            <!-- Error Alert -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" id="errorAlert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fa-solid fa-user"></i>
                        Tên đăng nhập
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        placeholder="Nhập tên đăng nhập"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required
                        autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fa-solid fa-lock"></i>
                        Mật khẩu
                    </label>
                    <div class="password-input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Nhập mật khẩu"
                            required
                            autocomplete="current-password">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="remember" id="rememberMe">
                        <span class="checkbox-label">Ghi nhớ đăng nhập</span>
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Đăng nhập</span>
                </button>
            </form>

            <!-- Register Link (Simple Text) -->
            <div class="auth-footer-simple">
                <p class="footer-text-center">
                    Chưa có tài khoản?
                    <a href="register.php" class="link-primary">Đăng ký ngay</a>
                </p>
            </div>
        </div>
    </div>

    <script src="../assets/js/auth.js?v=1.0"></script>
</body>

</html>