<?php
/**
 * db.php - Database Connection & Core Configuration
 * Chỉ chứa: Kết nối PDO + Constants + getPDO()
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================
require_once __DIR__ . '/config.php';

// ============================================
// PDO CONNECTION
// ============================================
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

// ============================================
// GLOBAL PDO GETTER
// ============================================
if (!function_exists('getPDO')) {
    function getPDO() {
        global $pdo;
        return $pdo;
    }
}

// ============================================
// APPLICATION CONSTANTS
// ============================================

// Admin
if (!defined('ADMIN_USER')) define('ADMIN_USER', 'admin');
if (!defined('ADMIN_PASS')) define('ADMIN_PASS', 'admin123');
if (!defined('ADMIN_ROLE')) define('ADMIN_ROLE', 'admin');

// Upload
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/uploads');
if (!defined('UPLOAD_BRANDS_DIR')) define('UPLOAD_BRANDS_DIR', __DIR__ . '/uploads/brands');
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 2 * 1024 * 1024);
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// Site
if (!defined('SITE_NAME')) define('SITE_NAME', 'BuildPC.vn');
if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost:9000/qlmt');

// Payment Methods
if (!defined('PAYMENT_METHODS')) define('PAYMENT_METHODS', [
    'cash' => ['name' => 'Tiền mặt', 'icon' => 'fa-money-bill'],
    'bank_transfer' => ['name' => 'Chuyển khoản ngân hàng', 'icon' => 'fa-building'],
    'credit_card' => ['name' => 'Thẻ tín dụng', 'icon' => 'fa-credit-card'],
    'ewallet' => ['name' => 'Ví điện tử', 'icon' => 'fa-mobile'],
    'cod' => ['name' => 'COD (Thanh toán khi nhận hàng)', 'icon' => 'fa-hand-holding-dollar'],
    'bank' => ['name' => 'Chuyển khoản ngân hàng', 'icon' => 'fa-building-columns'],
    'momo' => ['name' => 'Ví Momo', 'icon' => 'fa-mobile-screen'],
    'vnpay' => ['name' => 'VNPay', 'icon' => 'fa-wallet'],
    'zalopay' => ['name' => 'ZaloPay', 'icon' => 'fa-qrcode']
]);

// Order Statuses
if (!defined('ORDER_STATUSES')) define('ORDER_STATUSES', [
    'pending' => ['label' => 'Chờ thanh toán', 'color' => '#ffc107', 'icon' => 'fa-hourglass-end', 'text_color' => '#856404'],
    'paid' => ['label' => 'Đã thanh toán', 'color' => '#28a745', 'icon' => 'fa-check-circle', 'text_color' => '#0f5132'],
    'shipping' => ['label' => 'Đang giao', 'color' => '#ff9800', 'icon' => 'fa-truck', 'text_color' => '#664d03'],
    'completed' => ['label' => 'Hoàn thành', 'color' => '#17a2b8', 'icon' => 'fa-check-double', 'text_color' => '#055160'],
    'cancelled' => ['label' => 'Đã hủy', 'color' => '#dc3545', 'icon' => 'fa-times-circle', 'text_color' => '#842029']
]);
