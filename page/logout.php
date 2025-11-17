<?php

/**
 * page/logout.php - Logout (hỗ trợ cả Google logout)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra nếu đăng nhập bằng Google
$is_google_login = isset($_SESSION['user']['login_type']) &&
    $_SESSION['user']['login_type'] === 'google';

// Xóa session
session_unset();
session_destroy();

// Nếu đăng nhập bằng Google, có thể thêm logout URL
if ($is_google_login) {
    // Optional: Revoke Google token (nếu cần)
    // Hiện tại chỉ đơn giản xóa session
}

// Redirect về trang login
header("Location: login.php");
exit;
