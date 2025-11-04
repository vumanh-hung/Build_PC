<?php
// ===== Cấu hình Database =====
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'buildpc_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // default XAMPP

// ===== Kết nối Database =====
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Kết nối Database thất bại: " . $e->getMessage());
}

// ===== Base path =====
define('BASE_PATH', '/qlmt/');
?>
