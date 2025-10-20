<?php
/**
 * functions.php - Các hàm tiện ích chung
 */

require_once __DIR__ . '/db.php';

// ===== HÀM LẤY PDO =====
/**
 * Lấy đối tượng PDO
 */
function getPDO() {
    global $pdo;
    if (!isset($pdo)) {
        require_once __DIR__ . '/db.php';
    }
    return $pdo;
}

// ===== CÁC HÀM CŨ CỦA BẠN =====

function getCategories() {
    try {
        $pdo = getPDO();
        // Lấy danh mục từ bảng categories thay vì cột category
        $stmt = $pdo->query('SELECT * FROM categories ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getCategories: " . $e->getMessage());
        return [];
    }
}

function getProductsByCategory($category_id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE category_id = ? ORDER BY price');
        $stmt->execute([$category_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getProductsByCategory: " . $e->getMessage());
        return [];
    }
}

function getProduct($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllProducts() {
    try {
        $pdo = getPDO();
        // Sắp xếp theo category_id và name
        $stmt = $pdo->query('SELECT * FROM products ORDER BY category_id, name');
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in getAllProducts: " . $e->getMessage());
        // Fallback: chỉ ORDER theo name
        try {
            $pdo = getPDO();
            $stmt = $pdo->query('SELECT * FROM products ORDER BY name');
            return $stmt->fetchAll();
        } catch (PDOException $e2) {
            return [];
        }
    }
}

function createConfiguration($name, $productIds) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO configurations (name) VALUES (?)');
    $stmt->execute([$name]);
    $configId = $pdo->lastInsertId();
    $stmt2 = $pdo->prepare('INSERT INTO configuration_items (configuration_id, product_id) VALUES (?, ?)');
    foreach ($productIds as $pid) {
        $stmt2->execute([$configId, $pid]);
    }
    return $configId;
}

function getConfigurations() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM configurations ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function getConfigurationItems($configId) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT p.* FROM configuration_items ci JOIN products p ON ci.product_id = p.id WHERE ci.configuration_id = ?');
    $stmt->execute([$configId]);
    return $stmt->fetchAll();
}

// ===== HÀM THÊM MỚI CHO ADMIN =====

/**
 * Lấy tất cả thương hiệu
 */
function getAllBrands() {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT * FROM brands ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllBrands: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy thương hiệu theo ID
 */
function getBrandById($brand_id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM brands WHERE brand_id = ?");
        $stmt->execute([$brand_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getBrandById: " . $e->getMessage());
        return null;
    }
}

/**
 * Đếm tổng số sản phẩm
 */
function countProducts() {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error in countProducts: " . $e->getMessage());
        return 0;
    }
}

/**
 * Đếm tổng số thương hiệu
 */
function countBrands() {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM brands");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error in countBrands: " . $e->getMessage());
        return 0;
    }
}

/**
 * Format giá tiền (VND)
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.');
}

/**
 * Format ngày tháng
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Escape HTML output
 */
function escape($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Kiểm tra CSRF token
 */
function validateCSRFToken($token) {
    if (empty($_SESSION['csrf']) || $token !== $_SESSION['csrf']) {
        return false;
    }
    return true;
}

/**
 * Tạo CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

?>