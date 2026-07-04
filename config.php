<?php
// ===== Cấu hình Database =====
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'buildpc_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', ''); // default XAMPP

// ===== Base URL (tự động chính xác thư mục gốc dự án) =====
if (!defined('SITE_URL') || !defined('BASE_PATH')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];

    // ===== LOCAL =====
    if ($host == 'localhost' || $host == '127.0.0.1') {

        define('SITE_URL', $protocol . '://' . $host . '/Build_PC');
    } else {

        // ===== HOSTING =====
        define('SITE_URL', $protocol . '://' . $host);
    }
}

// ===== Thư mục upload (tự động trỏ đúng chỗ) =====
if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', SITE_URL . '/uploads');
}
