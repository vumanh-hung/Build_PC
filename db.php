<?php
/**
 * db.php - Kết nối database + định nghĩa constants
 */

$db_host = 'localhost';
$db_name = 'buildpc_db';
$db_user = 'root';
$db_pass = '';
$db_port = 3306;

try {
    $pdo = new PDO(
        "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("❌ Lỗi kết nối database: " . $e->getMessage());
}

// ===== ĐỊNH NGHĨA CONSTANTS =====
if (!defined('ADMIN_USER')) define('ADMIN_USER', 'admin');
if (!defined('ADMIN_PASS')) define('ADMIN_PASS', 'admin123');
if (!defined('ADMIN_ROLE')) define('ADMIN_ROLE', 'admin');

if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/uploads');
if (!defined('UPLOAD_BRANDS_DIR')) define('UPLOAD_BRANDS_DIR', __DIR__ . '/uploads/brands');
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 2 * 1024 * 1024);
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
if (!defined('SITE_NAME')) define('SITE_NAME', 'BuildPC.vn');
if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost:9000');

// ===== HÀM TRẢ VỀ PDO =====
function getPDO() {
    global $pdo;
    return $pdo;
}
