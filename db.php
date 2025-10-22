<?php
require_once __DIR__ . '/config.php'; // đảm bảo chỉ gọi 1 lần

// ===== Kết nối PDO =====
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("❌ Lỗi kết nối Database: " . $e->getMessage());
}

// ===== Hàm trả về PDO =====
if (!function_exists('getPDO')) {
    function getPDO() {
        global $pdo;
        return $pdo;
    }
}
