<?php
// ===== Cấu hình Database =====
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'buildpc_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', ''); // default XAMPP

// ===== Base URL (tự động xác định) =====
if (!defined('SITE_URL') || !defined('BASE_PATH')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $folder = trim($scriptDir, '/');

    if ($folder !== '') {
        $siteUrl = "$protocol://$host/$folder";
        $basePath = "/$folder/";
    } else {
        $siteUrl = "$protocol://$host";
        $basePath = "/";
    }

    define('SITE_URL', rtrim($siteUrl, '/'));
    define('BASE_PATH', $basePath);
}
