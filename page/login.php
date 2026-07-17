<?php

/**
 * page/login.php - Login Page với Google OAuth
 */

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', 'localhost');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../google_config.php';

// Định nghĩa ID của admin chính
define('ADMIN_ID', 1);

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    $redirect = $_SESSION['user']['role'] === 'admin' ? 'admin.php' : '../index.php';
    header("Location: $redirect");
    exit;
}

// Tạo Google Login URL
$googleClient = getGoogleClient();
$googleClient->setPrompt('select_account');
$googleLoginUrl = $googleClient->createAuthUrl();

// Error message
$error = "";

// Process normal login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!";
    } else {
        $user = authenticate_user($username, $password);

        if ($user) {
            // --- BẮT ĐẦU PHÂN QUYỀN ---
            // Nếu user không phải admin chính, đảm bảo role là 'user'
            if ($user['user_id'] != ADMIN_ID) {
                if ($user['role'] === 'admin') {
                    // Cập nhật role trong database thành 'user'
                    try {
                        $pdo = require_once __DIR__ . '/../db.php';
                        $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE user_id = ?");
                        $stmt->execute([$user['user_id']]);
                    } catch (PDOException $e) {
                        // Bỏ qua lỗi nếu có, vẫn tiếp tục đăng nhập nhưng role sẽ là user
                    }
                    // Sửa role trong mảng user trước khi lưu session
                    $user['role'] = 'user';
                }
            } else {
                // Đảm bảo admin chính luôn có role admin (phòng trường hợp DB bị sai)
                if ($user['role'] !== 'admin') {
                    try {
                        $pdo = require_once __DIR__ . '/../db.php';
                        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
                        $stmt->execute([$user['user_id']]);
                    } catch (PDOException $e) {}
                    $user['role'] = 'admin';
                }
            }

            // Lưu session
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
    <style>
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
            color: #64748b;
            font-size: 14px;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        .divider span {
            padding: 0 16px;
        }
        .btn-google {
            width: 100%;
            padding: 14px 24px;
            background: white;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
        }
        .btn-google:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .btn-google img {
            width: 20px;
            height: 20px;
        }
    </style>
</head>

<body>
    <div class="auth-background">
        <div class="bg-shape shape-1"></div>
        <div class="bg-shape shape-2"></div>
        <div class="bg-shape shape-3"></div>
    </div>

    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <div class="logo">
                    <i class="fa-solid fa-desktop"></i>
                    <span>BuildPC.vn</span>
                </div>
                <h1 class="auth-title">Đăng nhập</h1>
                <p class="auth-subtitle">Chào mừng bạn quay trở lại!</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error" id="errorAlert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <a href="<?= htmlspecialchars($googleLoginUrl) ?>" class="btn-google">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
                <span>Đăng nhập bằng Google</span>
            </a>

            <div class="divider">
                <span>Hoặc đăng nhập bằng tài khoản</span>
            </div>

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
