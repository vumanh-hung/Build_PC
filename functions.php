<?php
/**
 * functions.php - All Utility Functions
 * Chứa tất cả các hàm tiện ích của hệ thống
 */

require_once __DIR__ . '/db.php';

// ================================================
// 🔐 AUTHENTICATION & AUTHORIZATION
// ================================================

/**
 * Kiểm tra user đã đăng nhập
 */
function isLoggedIn() {
    return isset($_SESSION['user']) && isset($_SESSION['user']['user_id']);
}

/**
 * Kiểm tra user có phải admin
 */
function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Lấy user ID hiện tại
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? ($_SESSION['user']['user_id'] ?? 0);
}

/**
 * Yêu cầu đăng nhập
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/page/login.php');
        exit;
    }
}

/**
 * Yêu cầu quyền admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

/**
 * Lấy thông tin user theo ID
 */
function getUserById($user_id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getUserById: " . $e->getMessage());
        return null;
    }
}

// ================================================
// 🔒 CSRF PROTECTION
// ================================================

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
 * Kiểm tra CSRF token
 */
function validateCSRFToken($token) {
    if (empty($_SESSION['csrf']) || $token !== $_SESSION['csrf']) {
        return false;
    }
    return true;
}

/**
 * Tạo token ngẫu nhiên
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// ================================================
// 📦 CATEGORIES & PRODUCTS
// ================================================

/**
 * Lấy tất cả danh mục
 */
function getCategories() {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query('SELECT * FROM categories ORDER BY name ASC');
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
 * Lấy danh mục build (dùng cho builds.php)
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
        $stmt = $pdo->prepare('SELECT * FROM products WHERE category_id = ? ORDER BY price ASC');
        $stmt->execute([$category_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getProductsByCategory: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy thông tin một sản phẩm
 */
function getProduct($id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
        $stmt->execute([$id]);
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
        $stmt = $pdo->query('SELECT * FROM products ORDER BY name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllProducts: " . $e->getMessage());
        return [];
    }
}

// ================================================
// 🏷️ BRANDS
// ================================================

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
// 🛒 CART MANAGEMENT
// ================================================

/**
 * Lấy hoặc tạo giỏ hàng
 */
function getOrCreateCart($user_id) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cart) {
            return $cart['id'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, created_at) VALUES (?, NOW())");
        $stmt->execute([$user_id]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error in getOrCreateCart: " . $e->getMessage());
        return null;
    }
}

/**
 * Lấy items trong giỏ hàng
 */
function getCartItems($user_id) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cart) {
            return [];
        }
        
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
 * Tính tổng giá giỏ hàng
 */
function calculateCartTotal($items) {
    $total = 0;
    foreach ($items as $item) {
        $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
    }
    return $total;
}

/**
 * Đếm số lượng trong giỏ hàng
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
 * Xóa item khỏi giỏ hàng
 */
function removeCartItem($item_id, $user_id) {
    try {
        $pdo = getPDO();
        
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
 * Cập nhật số lượng items
 */
function updateCartItems($items, $user_id) {
    try {
        $pdo = getPDO();
        
        foreach ($items as $item_id => $quantity) {
            $quantity = max(1, (int)$quantity);
            
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
// 🧩 BUILD MANAGEMENT
// ================================================

/**
 * Lấy builds của user
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
 * Lấy build theo ID
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
 * Lấy items trong build
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
 * Tạo build mới
 */
function createBuild($name, $user_id, $items) {
    try {
        $pdo = getPDO();
        $pdo->beginTransaction();
        
        $total_price = 0;
        foreach ($items as $item) {
            $product = getProduct($item['product_id']);
            if ($product) {
                $total_price += $product['price'] * ($item['quantity'] ?? 1);
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO builds (user_id, name, total_price, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $name, $total_price]);
        $build_id = $pdo->lastInsertId();
        
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
 * Xóa build
 */
function deleteBuild($build_id, $user_id) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("
            DELETE bi FROM build_items bi
            JOIN builds b ON bi.build_id = b.build_id
            WHERE b.build_id = ? AND b.user_id = ?
        ");
        $stmt->execute([$build_id, $user_id]);
        
        $stmt = $pdo->prepare("DELETE FROM builds WHERE build_id = ? AND user_id = ?");
        $stmt->execute([$build_id, $user_id]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error in deleteBuild: " . $e->getMessage());
        return false;
    }
}

/**
 * Thêm build vào giỏ hàng
 */
function addBuildToCart($build_id, $user_id) {
    try {
        $pdo = getPDO();
        
        $cart_id = getOrCreateCart($user_id);
        if (!$cart_id) {
            return false;
        }
        
        $build_items = getBuildItems($build_id);
        
        if (empty($build_items)) {
            return false;
        }
        
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
// 📝 ORDER MANAGEMENT
// ================================================

/**
 * Lấy order theo ID
 */
function getOrderById($order_id, $user_id = null) {
    try {
        $pdo = getPDO();
        
        $sql = "SELECT o.order_id, o.total_price, o.order_status, o.created_at, o.updated_at,
                       os.full_name, os.phone, os.address, os.city, os.payment_method, os.notes
                FROM orders o 
                LEFT JOIN order_shipping os ON o.order_id = os.order_id 
                WHERE o.order_id = ?";
        
        $params = [$order_id];
        
        if ($user_id !== null) {
            $sql .= " AND o.user_id = ?";
            $params[] = $user_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getOrderById: " . $e->getMessage());
        return null;
    }
}

/**
 * Lấy items trong order
 */
function getOrderItems($order_id) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("
            SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.price_each as price,
                   p.name as product_name, p.main_image as image_url, p.category_id
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
            ORDER BY oi.order_item_id ASC
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getOrderItems: " . $e->getMessage());
        return [];
    }
}

/**
 * Cập nhật trạng thái order
 */
function updateOrderStatus($order_id, $status, $note = '') {
    try {
        $pdo = getPDO();
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET order_status = ?, updated_at = NOW()
            WHERE order_id = ?
        ");
        $stmt->execute([$status, $order_id]);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO order_status_history (order_id, status, note, updated_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$order_id, $status, $note]);
        } catch (PDOException $e) {
            // Bỏ qua nếu bảng không tồn tại
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in updateOrderStatus: " . $e->getMessage());
        return false;
    }
}

/**
 * Lấy orders của user
 */
function getUserOrders($user_id, $limit = null, $offset = 0) {
    try {
        $pdo = getPDO();
        
        $sql = "
            SELECT o.order_id, o.total_price, o.order_status as status, o.created_at,
                   os.full_name as fullname, os.address, os.city, os.phone, os.payment_method,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
            FROM orders o
            LEFT JOIN order_shipping os ON o.order_id = os.order_id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
        ";
        
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $limit, $offset]);
        } else {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getUserOrders: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy tổng quan orders
 */
function getOrderSummary($user_id) {
    $summary = [
        'total_paid' => 0,
        'count_pending' => 0,
        'count_shipping' => 0,
        'total_orders' => 0
    ];
    
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("
            SELECT o.order_status, COUNT(*) as count, COALESCE(SUM(o.total_price), 0) as sum
            FROM orders o
            WHERE o.user_id = ?
            GROUP BY o.order_status
        ");
        $stmt->execute([$user_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $row) {
            $summary['total_orders'] += $row['count'];
            
            if (in_array($row['order_status'], ['paid', 'completed', 'shipping'])) {
                $summary['total_paid'] += $row['sum'] ?? 0;
            }
            
            if ($row['order_status'] === 'pending') {
                $summary['count_pending'] = $row['count'];
            }
            
            if ($row['order_status'] === 'shipping') {
                $summary['count_shipping'] = $row['count'];
            }
        }
        
        return $summary;
    } catch (PDOException $e) {
        error_log("Error in getOrderSummary: " . $e->getMessage());
        return $summary;
    }
}

// ================================================
// ⭐ REVIEW SYSTEM
// ================================================

/**
 * Lấy reviews của sản phẩm
 */
function getProductReviews($product_id, $sort = 'newest', $page = 1, $per_page = 10) {
    try {
        $pdo = getPDO();
        $offset = ($page - 1) * $per_page;
        
        $order_by = match($sort) {
            'helpful' => 'r.helpful_count DESC, r.created_at DESC',
            'oldest' => 'r.created_at ASC',
            'rating_high' => 'r.rating DESC, r.created_at DESC',
            'rating_low' => 'r.rating ASC, r.created_at DESC',
            default => 'r.created_at DESC'
        };
        
        $stmt = $pdo->prepare("
            SELECT r.*, u.full_name, u.user_id
            FROM reviews r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.product_id = ? AND r.status = 'approved'
            ORDER BY $order_by
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$product_id, $per_page, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getProductReviews: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy thống kê rating
 */
function getProductRatingStats($product_id) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("
            SELECT 
                ROUND(COALESCE(AVG(rating), 0), 2) as avg_rating,
                COUNT(*) as total_reviews,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
            FROM reviews
            WHERE product_id = ? AND status = 'approved'
        ");
        $stmt->execute([$product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getProductRatingStats: " . $e->getMessage());
        return null;
    }
}

/**
 * Tạo review mới
 */
function createReview($pdo, $product_id, $user_id, $title, $content, $rating, $order_id = null) {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO reviews (product_id, user_id, order_id, title, content, rating, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$product_id, $user_id, $order_id, $title, $content, $rating]);
        
        $review_id = $pdo->lastInsertId();
        $pdo->commit();
        
        return ['success' => true, 'review_id' => $review_id];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create review error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Có lỗi xảy ra'];
    }
}

/**
 * Thêm ảnh review
 */
function addReviewImage($pdo, $review_id, $image_path) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO review_images (review_id, image_path, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$review_id, $image_path]);
        return true;
    } catch (Exception $e) {
        error_log("Add review image error: " . $e->getMessage());
        return false;
    }
}

/**
 * Lấy ảnh của review
 */
function getReviewImages($pdo, $review_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM review_images
            WHERE review_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$review_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get review images error: " . $e->getMessage());
        return [];
    }
}

/**
 * Vote review (helpful/unhelpful)
 */
function voteReview($review_id, $user_id, $vote_type) {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("SELECT * FROM review_votes WHERE review_id = ? AND user_id = ?");
        $stmt->execute([$review_id, $user_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE review_votes SET vote_type = ? WHERE review_id = ? AND user_id = ?");
            $stmt->execute([$vote_type, $review_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO review_votes (review_id, user_id, vote_type)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$review_id, $user_id, $vote_type]);
        }
        
        $stmt = $pdo->prepare("
            UPDATE reviews 
            SET helpful_count = (SELECT COUNT(*) FROM review_votes WHERE review_id = ? AND vote_type = 'helpful'),
                unhelpful_count = (SELECT COUNT(*) FROM review_votes WHERE review_id = ? AND vote_type = 'unhelpful')
            WHERE review_id = ?
        ");
        $stmt->execute([$review_id, $review_id, $review_id]);
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Vote review error: " . $e->getMessage());
        return ['success' => false];
    }
}

/**
 * Kiểm tra user đã review sản phẩm chưa
 */
function hasUserReviewedProduct($pdo, $product_id, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT review_id FROM reviews
            WHERE product_id = ? AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$product_id, $user_id]);
        return $stmt->fetch() ? true : false;
    } catch (Exception $e) {
        error_log("Check user reviewed error: " . $e->getMessage());
        return false;
    }
}

/**
 * Kiểm tra user đã mua sản phẩm chưa
 */
function hasUserPurchasedProduct($pdo, $product_id, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT oi.order_item_id FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            WHERE oi.product_id = ? AND o.user_id = ? AND o.order_status IN ('paid', 'shipping', 'completed')
            LIMIT 1
        ");
        $stmt->execute([$product_id, $user_id]);
        return $stmt->fetch() ? true : false;
    } catch (Exception $e) {
        error_log("Check user purchased error: " . $e->getMessage());
        return false;
    }
}

/**
 * Render stars rating
 */
function renderStars($rating, $size = 'md') {
    $size_class = match($size) {
        'sm' => 'font-size: 12px;',
        'lg' => 'font-size: 18px;',
        default => 'font-size: 14px;'
    };
    
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fa-solid fa-star" style="' . $size_class . ' color: #ffc107;"></i>';
        } elseif ($i - $rating < 1) {
            $stars .= '<i class="fa-solid fa-star-half-stroke" style="' . $size_class . ' color: #ffc107;"></i>';
        } else {
            $stars .= '<i class="fa-solid fa-star" style="' . $size_class . ' color: #ddd;"></i>';
        }
    }
    return $stars;
}

/**
 * Format rating text
 */
function formatRating($avg_rating, $total_reviews) {
    return round($avg_rating, 1) . ' sao từ ' . $total_reviews . ' đánh giá';
}

// ================================================
// 💳 PAYMENT
// ================================================

/**
 * Tạo payment record
 */
function createPayment($order_id, $user_id, $payment_method, $transaction_id = '', $amount = 0) {
    try {
        $pdo = getPDO();
        $pdo->beginTransaction();
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payment_history (order_id, user_id, payment_method, transaction_id, amount, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'completed', NOW())
            ");
            $stmt->execute([$order_id, $user_id, $payment_method, $transaction_id, $amount]);
        } catch (PDOException $e) {
            // Bỏ qua nếu bảng không tồn tại
        }
        
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET order_status = 'paid', updated_at = NOW()
            WHERE order_id = ? AND user_id = ?
        ");
        $stmt->execute([$order_id, $user_id]);
        
        $stmt = $pdo->prepare("
            UPDATE order_shipping 
            SET payment_method = ?
            WHERE order_id = ?
        ");
        $stmt->execute([$payment_method, $order_id]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in createPayment: " . $e->getMessage());
        return false;
    }
}

/**
 * Lấy thông tin payment method
 */
function getPaymentMethod($method) {
    $methods = PAYMENT_METHODS;
    return $methods[$method] ?? ['name' => 'Chưa xác định', 'icon' => 'fa-question'];
}

/**
 * Lấy icon payment method
 */
function getPaymentMethodIcon($method) {
    $info = getPaymentMethod($method);
    return $info['icon'];
}

/**
 * Lấy trạng thái order
 */
function getOrderStatus($status) {
    $statuses = ORDER_STATUSES;
    return $statuses[$status] ?? $statuses['pending'];
}

// ================================================
// 📊 STATISTICS
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
// 🎨 FORMAT & DISPLAY
// ================================================

/**
 * Format giá tiền
 */
function formatPrice($price) {
    return number_format((float)$price, 0, ',', '.');
}

/**
 * Format giá có ký hiệu VND
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
 * Escape HTML
 */
function escape($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
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
 * Tạo slug
 */
function createSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/u', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}


// ================================================
// 🖼️ IMAGE HANDLING - UPDATED
// ================================================

/**
 * Lấy đường dẫn ảnh sản phẩm
 * Hỗ trợ: main_image, image, URL đầy đủ, tên file
 */
function getProductImagePath($image, $default = 'uploads/img/no-image.png') {
    if (empty($image)) {
        return $default;
    }
    
    // Nếu là URL đầy đủ (http/https)
    if (strpos($image, 'http') === 0) {
        return $image;
    }
    
    // Nếu đã có "uploads/" ở đầu
    if (strpos($image, 'uploads/') === 0) {
        return $image;
    }
    
    // Nếu chỉ có tên file, thêm "uploads/" vào
    return 'uploads/' . $image;
}

/**
 * Lấy đường dẫn ảnh từ product object
 * Ưu tiên: main_image > image > default
 */
function getProductImage($product, $default = 'uploads/img/no-image.png') {
    if (!is_array($product)) {
        return $default;
    }
    
    // Ưu tiên main_image
    if (!empty($product['main_image'])) {
        return getProductImagePath($product['main_image'], $default);
    }
    
    // Fallback to image column (từ script crawl)
    if (!empty($product['image'])) {
        return getProductImagePath($product['image'], $default);
    }
    
    // Default
    return $default;
}

/**
 * Kiểm tra file upload hợp lệ
 */
function isValidImageUpload($file, $maxSize = 5242880) {
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
// ✉️ VALIDATION
// ================================================

/**
 * Kiểm tra email hợp lệ
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Kiểm tra số điện thoại hợp lệ
 */
function isValidPhone($phone) {
    return preg_match('/^0\d{9}$/', $phone);
}

// ================================================
// 📝 ACTIVITY LOG
// ================================================

/**
 * Log hoạt động
 */
function logActivity($user_id, $action, $details = '') {
    try {
        $pdo = getPDO();
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $action, $details]);
        return true;
    } catch (PDOException $e) {
        error_log("Error in logActivity: " . $e->getMessage());
        return false;
    }
}

// ================================================
// 🔄 REDIRECT & MESSAGES
// ================================================

/**
 * Redirect với flash message
 */
function redirect($url, $message = '', $type = 'success') {
    if (!empty($message)) {
        $_SESSION['message'] = [
            'text' => $message,
            'type' => $type
        ];
    }
    header("Location: $url");
    exit;
}

/**
 * Lấy flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}
