<?php

/**
 * page/register.php - Registration Page
 * Trang đăng ký tài khoản mới
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    $redirect = $_SESSION['user']['role'] === 'admin' ? 'admin.php' : '../index.php';
    header("Location: $redirect");
    exit;
}

// Initialize messages
$error = "";
$success = "";

// Process registration
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $password_confirm = trim($_POST["password_confirm"] ?? "");
    $full_name = trim($_POST["full_name"] ?? "");

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ!";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự!";
    } elseif ($password !== $password_confirm) {
        $error = "Mật khẩu xác nhận không khớp!";
    } else {
        try {
            $conn = getPDO();

            // Check for existing username or email
            $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);

            if ($check->fetch()) {
                $error = "Tên đăng nhập hoặc email đã tồn tại!";
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $role = "customer";

                // Insert new user
                $insert = $conn->prepare("
                    INSERT INTO users (username, email, password_hash, full_name, role, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $insert->execute([$username, $email, $password_hash, $full_name, $role]);

                $success = "Tạo tài khoản thành công! Đang chuyển hướng...";

                // Auto redirect after 2 seconds
                header("refresh:2;url=login.php");
            }
        } catch (PDOException $e) {
            $error = "Lỗi hệ thống: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - BuildPC.vn</title>
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

    <!-- Register Container -->
    <div class="auth-container">
        <div class="auth-box register-box">
            <!-- Logo & Header -->
            <div class="auth-header">
                <div class="logo">
                    <i class="fa-solid fa-desktop"></i>
                    <span>BuildPC.vn</span>
                </div>
                <h1 class="auth-title">Đăng ký tài khoản</h1>
                <p class="auth-subtitle">Tạo tài khoản mới để trải nghiệm đầy đủ</p>
            </div>

            <!-- Error Alert -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" id="errorAlert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Success Alert -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" id="successAlert">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <!-- Register Form -->
            <form method="POST" class="auth-form" id="registerForm">
                <div class="form-group">
                    <label for="full_name" class="form-label">
                        <i class="fa-solid fa-signature"></i>
                        Họ và tên
                    </label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="form-input"
                        placeholder="Nhập họ và tên đầy đủ"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                        required
                        autocomplete="name">
                </div>

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
                        autocomplete="username"
                        pattern="[a-zA-Z0-9_]{3,20}"
                        title="Tên đăng nhập chỉ chứa chữ cái, số và dấu gạch dưới (3-20 ký tự)">
                    <small class="form-hint">
                        Chỉ chứa chữ cái, số và dấu gạch dưới (3-20 ký tự)
                    </small>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fa-solid fa-envelope"></i>
                        Email
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="email@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                        autocomplete="email">
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
                            placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)"
                            required
                            autocomplete="new-password"
                            minlength="6">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label">
                        <i class="fa-solid fa-lock-keyhole"></i>
                        Xác nhận mật khẩu
                    </label>
                    <div class="password-input-wrapper">
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            class="form-input"
                            placeholder="Nhập lại mật khẩu"
                            required
                            autocomplete="new-password"
                            minlength="6">
                        <button type="button" class="password-toggle" id="togglePasswordConfirm">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="terms" id="termsCheckbox" required>
                        <span class="checkbox-label">
                            Tôi đồng ý với
                            <a href="../page/terms.php" target="_blank">Điều khoản sử dụng</a>
                            và
                            <a href="../page/privacy.php" target="_blank">Chính sách bảo mật</a>
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Đăng ký ngay</span>
                </button>
            </form>

            <!-- Back to Login Button (Right after form) -->
            <div class="back-to-login">
                <a href="login.php" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Quay lại đăng nhập</span>
                </a>
            </div>

            <!-- Login Link (Simple Text) -->
            <div class="auth-footer-simple">
                <p class="footer-text-center">
                    Đã có tài khoản?
                    <a href="login.php" class="link-primary">Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>

    <script src="../assets/js/auth.js?v=1.0"></script>
</body>

</html>