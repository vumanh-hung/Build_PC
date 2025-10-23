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

    // ✅ Lấy thư mục gốc của dự án (ví dụ: Logic-PC)
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $currentDir = str_replace('\\', '/', realpath(__DIR__));
    $projectFolder = trim(str_replace($docRoot, '', $currentDir), '/');

    $siteUrl = "$protocol://$host/$projectFolder";
    $basePath = '/' . trim($projectFolder, '/') . '/';

    define('SITE_URL', rtrim($siteUrl, '/'));
    define('BASE_PATH', $basePath);
}

// ===== Thư mục upload (tự động trỏ đúng chỗ) =====
if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', SITE_URL . '/uploads');
}
