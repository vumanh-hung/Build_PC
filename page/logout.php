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

// Nếu đăng nhập bằng Google, redirect đến Google logout
if ($is_google_login) {
    // Xóa cookie session của PHP
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Redirect về login với parameter để force chọn account
    header("Location: login.php?logout=google");
    exit;
}

// Redirect về trang login
header("Location: login.php");
exit;
