<?php
/**
 * functions.php - Các hàm tiện ích chung (Tối ưu)
 */

require_once __DIR__ . '/db.php';

// ================================================
// 🔧 CÁC HÀM CƠ BẢN - QUẢN LÝ DATABASE
// ================================================

/**
 * Lấy tất cả danh mục sản phẩm
 */
function getCategories() {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query('SELECT * FROM categories ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getCategories: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy danh mục theo ID
 */
function getCategoryById($category_id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
        $stmt->execute([$category_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getCategoryById: " . $e->getMessage());
        return null;
    }
}

/**
 * Lấy danh mục build (dùng cho trang builds.php)
 */
function getBuildCategories() {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("
            SELECT category_id, name 
            FROM categories 
            WHERE category_id IN (1,2,3,4,5,21,23)
            ORDER BY category_id
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getBuildCategories: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy sản phẩm theo danh mục
 */
function getProductsByCategory($category_id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE category_id = ? ORDER BY price');
        $stmt->execute([$category_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getProductsByCategory: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy thông tin một sản phẩm theo ID
 */
function getProduct($id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ? OR id = ?');
        $stmt->execute([$id, $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getProduct: " . $e->getMessage());
        return null;
    }
}

/**
 * Lấy tất cả sản phẩm
 */
function getAllProducts() {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query('SELECT * FROM products ORDER BY category_id, name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllProducts: " . $e->getMessage());
        try {
            $pdo = getPDO();
            $stmt = $pdo->query('SELECT * FROM products ORDER BY name');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            return [];
        }
    }
}

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

// ================================================
// 🛒 QUẢN LÝ GIỎ HÀNG
// ================================================

/**
 * Lấy hoặc tạo mới giỏ hàng cho user
 */
function getOrCreateCart($user_id) {
    try {
        $pdo = getPDO();
        
        // Kiểm tra giỏ hàng đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cart) {
            return $cart['id'];
        }
        
        // Tạo mới giỏ hàng
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, created_at) VALUES (?, NOW())");
        $stmt->execute([$user_id]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error in getOrCreateCart: " . $e->getMessage());
        return null;
    }
}

/**
 * Lấy các item trong giỏ hàng
 */
function getCartItems($user_id) {
    try {
        $pdo = getPDO();
        
        // Lấy cart_id
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cart) {
            return [];
        }
        
        // Lấy các item
        $stmt = $pdo->prepare("
            SELECT 
                ci.id AS item_id,
                p.product_id AS id,
                p.name,
                p.price,
                p.main_image,
                ci.quantity
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.product_id
            WHERE ci.cart_id = ?
        ");
        $stmt->execute([$cart['id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getCartItems: " . $e->getMessage());
        return [];
    }
}

/**
 * Tính tổng giá trị giỏ hàng
 */
function calculateCartTotal($items) {
    $total = 0;
    foreach ($items as $item) {
        $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
    }
    return $total;
}

/**
 * Đếm tổng số lượng sản phẩm trong giỏ hàng
 */
function getCartCount($user_id) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(ci.quantity), 0) as total
            FROM cart c
            JOIN cart_items ci ON c.id = ci.cart_id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error in getCartCount: " . $e->getMessage());
        return 0;
    }
}

/**
 * Xóa một item khỏi giỏ hàng
 */
function removeCartItem($item_id, $user_id) {
    try {
        $pdo = getPDO();
        
        // Kiểm tra quyền sở hữu
        $stmt = $pdo->prepare("
            DELETE ci FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            WHERE ci.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$item_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error in removeCartItem: " . $e->getMessage());
        return false;
    }
}

/**
 * Xóa toàn bộ giỏ hàng
 */
function clearCart($user_id) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("
            DELETE ci FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Error in clearCart: " . $e->getMessage());
        return false;
    }
}

/**
 * Cập nhật số lượng các item trong giỏ hàng
 */
function updateCartItems($items, $user_id) {
    try {
        $pdo = getPDO();
        
        foreach ($items as $item_id => $quantity) {
            $quantity = max(1, (int)$quantity); // Đảm bảo quantity >= 1
            
            $stmt = $pdo->prepare("
                UPDATE cart_items ci
                JOIN cart c ON ci.cart_id = c.id
                SET ci.quantity = ?
                WHERE ci.id = ? AND c.user_id = ?
            ");
            $stmt->execute([$quantity, $item_id, $user_id]);
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error in updateCartItems: " . $e->getMessage());
        return false;
    }
}

// ================================================
// 🧩 QUẢN LÝ CẤU HÌNH BUILD
// ================================================

/**
 * Lấy danh sách cấu hình của user
 */
function getUserBuilds($user_id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT build_id, name, total_price, created_at 
            FROM builds 
            WHERE user_id = ?
            ORDER BY build_id DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getUserBuilds: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy thông tin một cấu hình
 */
function getBuildById($build_id, $user_id = null) {
    try {
        $pdo = getPDO();
        
        $sql = "SELECT * FROM builds WHERE build_id = ?";
        $params = [$build_id];
        
        if ($user_id !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getBuildById: " . $e->getMessage());
        return null;
    }
}

/**
 * Lấy các item trong một cấu hình
 */
function getBuildItems($build_id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT 
                bi.id,
                p.product_id,
                p.name,
                p.price,
                p.main_image,
                bi.quantity
            FROM build_items bi
            JOIN products p ON bi.product_id = p.product_id
            WHERE bi.build_id = ?
        ");
        $stmt->execute([$build_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getBuildItems: " . $e->getMessage());
        return [];
    }
}

/**
 * Tạo cấu hình mới
 */
function createBuild($name, $user_id, $items) {
    try {
        $pdo = getPDO();
        $pdo->beginTransaction();
        
        // Tính tổng giá
        $total_price = 0;
        foreach ($items as $item) {
            $product = getProduct($item['product_id']);
            if ($product) {
                $total_price += $product['price'] * ($item['quantity'] ?? 1);
            }
        }
        
        // Tạo build
        $stmt = $pdo->prepare("
            INSERT INTO builds (user_id, name, total_price, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $name, $total_price]);
        $build_id = $pdo->lastInsertId();
        
        // Thêm các item
        $stmt = $pdo->prepare("
            INSERT INTO build_items (build_id, product_id, quantity)
            VALUES (?, ?, ?)
        ");
        
        foreach ($items as $item) {
            $stmt->execute([
                $build_id,
                $item['product_id'],
                $item['quantity'] ?? 1
            ]);
        }
        
        $pdo->commit();
        return $build_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in createBuild: " . $e->getMessage());
        return false;
    }
}

/**
 * Xóa cấu hình
 */
function deleteBuild($build_id, $user_id) {
    try {
        $pdo = getPDO();
        
        // Xóa build items trước
        $stmt = $pdo->prepare("
            DELETE bi FROM build_items bi
            JOIN builds b ON bi.build_id = b.build_id
            WHERE b.build_id = ? AND b.user_id = ?
        ");
        $stmt->execute([$build_id, $user_id]);
        
        // Xóa build
        $stmt = $pdo->prepare("DELETE FROM builds WHERE build_id = ? AND user_id = ?");
        $stmt->execute([$build_id, $user_id]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error in deleteBuild: " . $e->getMessage());
        return false;
    }
}

/**
 * Thêm cấu hình vào giỏ hàng
 */
function addBuildToCart($build_id, $user_id) {
    try {
        $pdo = getPDO();
        
        // Lấy hoặc tạo giỏ hàng
        $cart_id = getOrCreateCart($user_id);
        if (!$cart_id) {
            return false;
        }
        
        // Lấy các item trong build
        $build_items = getBuildItems($build_id);
        
        if (empty($build_items)) {
            return false;
        }
        
        // Thêm từng item vào giỏ hàng
        $stmt = $pdo->prepare("
            INSERT INTO cart_items (cart_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        
        foreach ($build_items as $item) {
            $stmt->execute([
                $cart_id,
                $item['product_id'],
                $item['quantity']
            ]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error in addBuildToCart: " . $e->getMessage());
        return false;
    }
}

// ================================================
// 📊 HÀM THỐNG KÊ
// ================================================

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
 * Đếm tổng số danh mục
 */
function countCategories() {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error in countCategories: " . $e->getMessage());
        return 0;
    }
}

// ================================================
// 🔐 BẢO MẬT & XÁC THỰC
// ================================================

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

/**
 * Lấy user ID hiện tại từ session
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? ($_SESSION['user']['user_id'] ?? 0);
}

/**
 * Kiểm tra user đã đăng nhập chưa
 */
function isLoggedIn() {
    return getCurrentUserId() > 0;
}

/**
 * Kiểm tra user có phải admin không
 */
function isAdmin() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

// ================================================
// 🎨 HÀM FORMAT & HIỂN THỊ
// ================================================

/**
 * Format giá tiền theo tiêu chuẩn Việt Nam
 */
function formatPrice($price) {
    return number_format((float)$price, 0, ',', '.');
}

/**
 * Format giá tiền có ký hiệu VND
 */
function formatPriceVND($price) {
    return number_format((float)$price, 0, ',', '.') . ' ₫';
}

/**
 * Format ngày tháng
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Escape HTML output
 */
function escape($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Rút gọn văn bản
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Tạo slug từ tiêu đề
 */
function createSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/u', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ================================================
// 🖼️ HÀM XỬ LÝ HÌNH ẢNH
// ================================================

/**
 * Lấy đường dẫn hình ảnh sản phẩm
 */
function getProductImagePath($image, $default = 'uploads/img/no-image.png') {
    if (empty($image)) {
        return $default;
    }
    
    // Nếu đã có đường dẫn đầy đủ
    if (strpos($image, 'uploads/') === 0) {
        return $image;
    }
    
    return 'uploads/' . $image;
}

/**
 * Kiểm tra file upload có hợp lệ không
 */
function isValidImageUpload($file, $maxSize = 5242880) { // 5MB default
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($mimeType, $allowedTypes);
}

// ================================================
// 🔄 CÁC HÀM CŨ (Giữ lại để tương thích)
// ================================================

/**
 * @deprecated Sử dụng createBuild() thay thế
 */
function createConfiguration($name, $productIds) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $items = [];
    foreach ($productIds as $pid) {
        $items[] = ['product_id' => $pid, 'quantity' => 1];
    }
    
    return createBuild($name, getCurrentUserId(), $items);
}

/**
 * @deprecated Sử dụng getUserBuilds() thay thế
 */
function getConfigurations() {
    if (!isLoggedIn()) {
        return [];
    }
    return getUserBuilds(getCurrentUserId());
}

/**
 * @deprecated Sử dụng getBuildItems() thay thế
 */
function getConfigurationItems($configId) {
    return getBuildItems($configId);
}

?>