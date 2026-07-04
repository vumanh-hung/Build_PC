<?php
// ===== Cấu hình Database =====
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'buildpc_db');
if (!defined('DB_USER')) define('DB_USER', 'root'); // default XAMPP
if (!defined('DB_PASS')) define('DB_PASS', ''); // default XAMPP

// ===== Base URL (tự động chính xác thư mục gốc dự án) =====
if (!defined('SITE_URL') || !defined('BASE_PATH')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];

    // ✅ Lấy thư mục gốc của dự án (ví dụ: Logic-PC)
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $currentDir = str_replace('\\', '/', realpath(__DIR__));
    $projectFolder = trim(str_replace($docRoot, '', $currentDir), '/');

    if ($projectFolder === '') {
        $siteUrl = "$protocol://$host";
        $basePath = '/';
    } else {
        $siteUrl = "$protocol://$host/$projectFolder";
        $basePath = '/' . $projectFolder . '/';
    }

    define('SITE_URL', rtrim($siteUrl, '/'));
    define('BASE_PATH', $basePath);
}

// ===== Thư mục upload (tự động trỏ đúng chỗ) =====
if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', SITE_URL . '/uploads');
}

// ===== Google Gemini API =====
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', '// thêm vào đoạn này API CODE');
}
if (!defined('GEMINI_MODEL')) {
    define('GEMINI_MODEL', 'gemini-2.5-flash');
}
