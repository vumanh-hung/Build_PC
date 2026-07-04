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
function isLoggedIn()
{
    // ✅ Đảm bảo session đã được start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user']) && isset($_SESSION['user']['user_id']);
}

/**
 * Kiểm tra user có phải admin
 */
function isAdmin()
{
    // ✅ Đảm bảo session đã được start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Lấy user ID hiện tại
 */
function getCurrentUserId()
{
    // ✅ Đảm bảo session đã được start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // ✅ Ưu tiên lấy từ $_SESSION['user']['user_id']
    if (isset($_SESSION['user']['user_id'])) {
        return (int)$_SESSION['user']['user_id'];
    }

    // ✅ Fallback: Kiểm tra $_SESSION['user_id'] (trường hợp cũ)
    if (isset($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }

    return 0;
}

/**
 * Yêu cầu đăng nhập
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/page/login.php');
        exit;
    }
}

/**
 * Yêu cầu quyền admin
 */
function requireAdmin()
{
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

/**
 * Lấy thông tin user theo ID
 */
function getUserById($user_id)
{
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
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Kiểm tra CSRF token
 */
function validateCSRFToken($token)
{
    if (empty($_SESSION['csrf']) || $token !== $_SESSION['csrf']) {
        return false;
    }
    return true;
}

/**
 * Tạo token ngẫu nhiên
 */
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

// ================================================
// 📦 CATEGORIES & PRODUCTS
// ================================================

/**
 * Lấy tất cả danh mục
 */
function getCategories()
{
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
function getCategoryById($category_id)
{
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
function getBuildCategories()
{
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
function getProductsByCategory($category_id)
{
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
function getProduct($id)
{
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
function getAllProducts()
{
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
function getAllBrands()
{
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
function getBrandById($brand_id)
{
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
function getOrCreateCart($user_id)
{
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
function getCartItems($user_id)
{
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
function calculateCartTotal($items)
{
    $total = 0;
    foreach ($items as $item) {
        $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
    }
    return $total;
}

/**
 * Đếm số lượng trong giỏ hàng
 */
function getCartCount($user_id)
{
    try {
        // ✅ Kiểm tra user_id hợp lệ
        if (!$user_id || $user_id <= 0) {
            return 0;
        }

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
function removeCartItem($item_id, $user_id)
{
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
function clearCart($user_id)
{
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
function updateCartItems($items, $user_id)
{
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
function getUserBuilds($user_id)
{
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
function getBuildById($build_id, $user_id = null)
{
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
function getBuildItems($build_id)
{
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
function createBuild($name, $user_id, $items)
{
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
function deleteBuild($build_id, $user_id)
{
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
function addBuildToCart($build_id, $user_id)
{
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
function getOrderById($order_id, $user_id = null)
{
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
function getOrderItems($order_id)
{
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
function updateOrderStatus($order_id, $status, $note = '')
{
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
function getUserOrders($user_id, $limit = null, $offset = 0)
{
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
function getOrderSummary($user_id)
{
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
function getProductReviews($product_id, $sort = 'newest', $page = 1, $per_page = 10)
{
    try {
        $pdo = getPDO();
        $offset = ($page - 1) * $per_page;

        $order_by = match ($sort) {
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
function getProductRatingStats($product_id)
{
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
function createReview($pdo, $product_id, $user_id, $title, $content, $rating, $order_id = null)
{
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
function addReviewImage($pdo, $review_id, $image_path)
{
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
function getReviewImages($pdo, $review_id)
{
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
function voteReview($review_id, $user_id, $vote_type)
{
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
function hasUserReviewedProduct($pdo, $product_id, $user_id)
{
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
function hasUserPurchasedProduct($pdo, $product_id, $user_id)
{
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
function renderStars($rating, $size = 'md')
{
    $size_class = match ($size) {
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
function formatRating($avg_rating, $total_reviews)
{
    return round($avg_rating, 1) . ' sao từ ' . $total_reviews . ' đánh giá';
}

// ================================================
// 💳 PAYMENT
// ================================================

/**
 * Tạo payment record
 */
function createPayment($order_id, $user_id, $payment_method, $transaction_id = '', $amount = 0)
{
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
function getPaymentMethod($method)
{
    $methods = PAYMENT_METHODS;
    return $methods[$method] ?? ['name' => 'Chưa xác định', 'icon' => 'fa-question'];
}

/**
 * Lấy icon payment method
 */
function getPaymentMethodIcon($method)
{
    $info = getPaymentMethod($method);
    return $info['icon'];
}

/**
 * Lấy trạng thái order
 */
function getOrderStatus($status)
{
    $statuses = ORDER_STATUSES;
    return $statuses[$status] ?? $statuses['pending'];
}

// ================================================
// 📊 STATISTICS
// ================================================

/**
 * Đếm tổng số sản phẩm
 */
function countProducts()
{
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
function countBrands()
{
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
function countCategories()
{
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
function formatPrice($price)
{
    return number_format((float)$price, 0, ',', '.');
}

/**
 * Format giá có ký hiệu VND
 */
function formatPriceVND($price)
{
    return number_format((float)$price, 0, ',', '.') . ' ₫';
}

/**
 * Format ngày tháng
 */
function formatDate($date, $format = 'd/m/Y H:i')
{
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Escape HTML
 */
function escape($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input
 */
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Rút gọn văn bản
 */
function truncateText($text, $length = 100, $suffix = '...')
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Tạo slug
 */
function createSlug($text)
{
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
function getProductImagePath($image, $default = 'uploads/img/no-image.png')
{
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
function getProductImage($product, $default = 'uploads/img/no-image.png')
{
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
function isValidImageUpload($file, $maxSize = 5242880)
{
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
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Kiểm tra số điện thoại hợp lệ
 */
function isValidPhone($phone)
{
    return preg_match('/^0\d{9}$/', $phone);
}

// ================================================
// 📝 ACTIVITY LOG
// ================================================

/**
 * Log hoạt động
 */
function logActivity($user_id, $action, $details = '')
{
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
function redirect($url, $message = '', $type = 'success')
{
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
function getFlashMessage()
{
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

// ================================================
// 🛍️ PRODUCT FILTERING & DISPLAY
// ================================================

/**
 * Lấy sản phẩm theo bộ lọc
 */
function getFilteredProducts($filters)
{
    try {
        $pdo = getPDO();

        $where = [];
        $params = [];

        if (!empty($filters['keyword'])) {
            $where[] = "p.name LIKE :keyword";
            $params[':keyword'] = "%" . $filters['keyword'] . "%";
        }

        if ($filters['category_id'] > 0) {
            $where[] = "p.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        if ($filters['brand_id'] > 0) {
            $where[] = "p.brand_id = :brand_id";
            $params[':brand_id'] = $filters['brand_id'];
        }

        if ($filters['min_price'] > 0) {
            $where[] = "p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }

        if ($filters['max_price'] > 0) {
            $where[] = "p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }

        $sql = "
            SELECT p.*, c.name AS category_name, b.name AS brand_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
        ";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY p.product_id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getFilteredProducts: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy thông tin khuyến mãi đang có hiệu lực của sản phẩm
 * Điều kiện: đang bật, nằm trong khoảng thời gian, chưa hết số lượng
 * (max_quantity = 0 nghĩa là không giới hạn số lượng)
 */
function getProductPromotion($product_id)
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT * FROM promotions 
            WHERE product_id = :product_id 
            AND is_active = 1 
            AND start_date <= NOW() 
            AND end_date >= NOW()
            AND (max_quantity = 0 OR used_quantity < max_quantity)
            ORDER BY discount_percent DESC, discount_amount DESC
            LIMIT 1
        ");
        $stmt->execute([':product_id' => $product_id]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);
        return $promo ?: null;
    } catch (PDOException $e) {
        // Bỏ qua nếu bảng không tồn tại
        return null;
    }
}

/**
 * Tính giá sau khuyến mãi.
 * Hỗ trợ cả giảm theo phần trăm (percent) và giảm theo số tiền (amount).
 * Nếu truyền vào mảng promotion sẽ tự nhận diện loại giảm giá.
 */
function calculateSalePrice($original_price, $discount_percent, $promotion = null)
{
    // Nếu có mảng promotion đầy đủ, ưu tiên tính theo discount_type
    if (is_array($promotion)) {
        $type = $promotion['discount_type'] ?? 'percent';
        if ($type === 'fixed') {
            $sale = $original_price - (float)($promotion['discount_amount'] ?? 0);
            return $sale < 0 ? 0 : $sale;
        }
        $percent = (float)($promotion['discount_percent'] ?? 0);
        return $original_price * (1 - $percent / 100);
    }

    // Fallback: tính theo phần trăm
    return $original_price * (1 - $discount_percent / 100);
}

/**
 * Tính phần trăm giảm giá hiệu dụng của một khuyến mãi so với giá gốc.
 * Dùng để hiển thị badge "-X%" thống nhất cho cả 2 loại giảm giá.
 */
function getPromotionDiscountPercent($original_price, $promotion)
{
    if (empty($promotion) || $original_price <= 0) {
        return 0;
    }

    $type = $promotion['discount_type'] ?? 'percent';
    if ($type === 'fixed') {
        $amount = (float)($promotion['discount_amount'] ?? 0);
        return (int)round(($amount / $original_price) * 100);
    }

    return (int)round((float)($promotion['discount_percent'] ?? 0));
}

// ================================================
// 🏷️ PROMOTION MANAGEMENT (ADMIN)
// ================================================

/**
 * Lấy toàn bộ khuyến mãi kèm tên sản phẩm (cho dashboard admin)
 */
function getAllPromotions()
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("
            SELECT pr.*, p.name AS product_name, p.price AS product_price
            FROM promotions pr
            LEFT JOIN products p ON pr.product_id = p.product_id
            ORDER BY pr.is_active DESC, pr.end_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllPromotions: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy 1 khuyến mãi theo ID
 */
function getPromotionById($promotion_id)
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM promotions WHERE promotion_id = ?");
        $stmt->execute([$promotion_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log("Error in getPromotionById: " . $e->getMessage());
        return null;
    }
}

/**
 * Kiểm tra xem sản phẩm đã có khuyến mãi nào bị trùng khoảng thời gian chưa.
 * Trả về true nếu bị trùng (không cho tạo/sửa).
 */
function hasOverlappingPromotion($product_id, $start_date, $end_date, $exclude_id = 0)
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM promotions
            WHERE product_id = :product_id
            AND is_active = 1
            AND promotion_id != :exclude_id
            AND start_date <= :end_date
            AND end_date >= :start_date
        ");
        $stmt->execute([
            ':product_id' => $product_id,
            ':exclude_id' => $exclude_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
        ]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error in hasOverlappingPromotion: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate dữ liệu khuyến mãi. Trả về mảng lỗi (rỗng nếu hợp lệ).
 */
function validatePromotionData($data)
{
    $errors = [];

    if (empty($data['product_id'])) {
        $errors[] = 'Vui lòng chọn sản phẩm áp dụng.';
    }
    if (empty(trim($data['promotion_name'] ?? ''))) {
        $errors[] = 'Tên chương trình khuyến mãi không được để trống.';
    }

    $discount_type = $data['discount_type'] ?? 'percent';
    if ($discount_type === 'percent') {
        $percent = (float)($data['discount_percent'] ?? 0);
        if ($percent <= 0 || $percent > 100) {
            $errors[] = 'Phần trăm giảm phải nằm trong khoảng 1 - 100.';
        }
    } else {
        $amount = (float)($data['discount_amount'] ?? 0);
        if ($amount <= 0) {
            $errors[] = 'Số tiền giảm phải lớn hơn 0.';
        }
    }

    $start = strtotime($data['start_date'] ?? '');
    $end = strtotime($data['end_date'] ?? '');
    if (!$start || !$end) {
        $errors[] = 'Thời gian bắt đầu và kết thúc không hợp lệ.';
    } elseif ($end <= $start) {
        $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
    }

    if (isset($data['max_quantity']) && (int)$data['max_quantity'] < 0) {
        $errors[] = 'Số lượng tối đa không được âm.';
    }

    return $errors;
}

/**
 * Tạo mới khuyến mãi. Trả về ['success' => bool, 'error' => string]
 */
function createPromotion($data)
{
    $errors = validatePromotionData($data);
    if (!empty($errors)) {
        return ['success' => false, 'error' => implode(' ', $errors)];
    }

    if (hasOverlappingPromotion($data['product_id'], $data['start_date'], $data['end_date'])) {
        return ['success' => false, 'error' => 'Sản phẩm này đã có khuyến mãi đang chạy trong khoảng thời gian trùng lặp.'];
    }

    try {
        $pdo = getPDO();
        $discount_type = $data['discount_type'] ?? 'percent';
        $stmt = $pdo->prepare("
            INSERT INTO promotions
                (product_id, promotion_name, promotion_type, discount_type,
                 discount_percent, discount_amount, start_date, end_date,
                 max_quantity, used_quantity, is_active, created_at)
            VALUES(:product_id, :promotion_name, :promotion_type, :discount_type,
                 :discount_percent, :discount_amount, :start_date, :end_date,
                 :max_quantity, 0, :is_active, NOW())
        ");
        $stmt->execute([
            ':product_id' => (int)$data['product_id'],
            ':promotion_name' => trim($data['promotion_name']),
            ':promotion_type' => $data['promotion_type'] ?? 'flash_sale',
            ':discount_type' => $discount_type,
            ':discount_percent' => $discount_type === 'percent' ? (float)$data['discount_percent'] : 0,
            ':discount_amount' => $discount_type === 'fixed' ? (float)$data['discount_amount'] : 0,
            ':start_date' => date('Y-m-d H:i:s', strtotime($data['start_date'])),
            ':end_date' => date('Y-m-d H:i:s', strtotime($data['end_date'])),
            ':max_quantity' => (int)($data['max_quantity'] ?? 0),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        error_log("Error in createPromotion: " . $e->getMessage());
        return ['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()];
    }
}

/**
 * Cập nhật khuyến mãi
 */
function updatePromotion($promotion_id, $data)
{
    $errors = validatePromotionData($data);
    if (!empty($errors)) {
        return ['success' => false, 'error' => implode(' ', $errors)];
    }

    if (hasOverlappingPromotion($data['product_id'], $data['start_date'], $data['end_date'], $promotion_id)) {
        return ['success' => false, 'error' => 'Sản phẩm này đã có khuyến mãi khác trùng khoảng thời gian.'];
    }

    try {
        $pdo = getPDO();
        $discount_type = $data['discount_type'] ?? 'percent';
        $stmt = $pdo->prepare("
            UPDATE promotions SET
                product_id = :product_id,
                promotion_name = :promotion_name,
                promotion_type = :promotion_type,
                discount_type = :discount_type,
                discount_percent = :discount_percent,
                discount_amount = :discount_amount,
                start_date = :start_date,
                end_date = :end_date,
                max_quantity = :max_quantity,
                is_active = :is_active,
                updated_at = NOW()
            WHERE promotion_id = :promotion_id
        ");
        $stmt->execute([
            ':product_id' => (int)$data['product_id'],
            ':promotion_name' => trim($data['promotion_name']),
            ':promotion_type' => $data['promotion_type'] ?? 'flash_sale',
            ':discount_type' => $discount_type,
            ':discount_percent' => $discount_type === 'percent' ? (float)$data['discount_percent'] : 0,
            ':discount_amount' => $discount_type === 'fixed' ? (float)$data['discount_amount'] : 0,
            ':start_date' => date('Y-m-d H:i:s', strtotime($data['start_date'])),
            ':end_date' => date('Y-m-d H:i:s', strtotime($data['end_date'])),
            ':max_quantity' => (int)($data['max_quantity'] ?? 0),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
            ':promotion_id' => (int)$promotion_id,
        ]);
        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        error_log("Error in updatePromotion: " . $e->getMessage());
        return ['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()];
    }
}

/**
 * Xóa khuyến mãi
 */
function deletePromotion($promotion_id)
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("DELETE FROM promotions WHERE promotion_id = ?");
        $stmt->execute([(int)$promotion_id]);
        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        error_log("Error in deletePromotion: " . $e->getMessage());
        return ['success' => false, 'error' => 'Lỗi kh xóa: ' . $e->getMessage()];
    }
}

/**
 * Bật/tắt trạng thái khuyến mãi
 */
function togglePromotionStatus($promotion_id)
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("UPDATE promotions SET is_active = 1 - is_active, updated_at = NOW() WHERE promotion_id = ?");
        $stmt->execute([(int)$promotion_id]);
        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        error_log("Error in togglePromotionStatus: " . $e->getMessage());
        return ['success' => false, 'error' => 'Lỗi khi cập nhật: ' . $e->getMessage()];
    }
}

// ================================================
// ⭐ REVIEW DISPLAY FUNCTIONS
// ================================================

/**
 * Lấy thống kê review tổng quan
 */
function getOverallReviewStats()
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT 
                AVG(r.rating) as avg_rating,
                COUNT(*) as total_reviews,
                SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) as rating_5,
                SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END) as rating_4,
                SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END) as rating_3,
                SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END) as rating_2,
                SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END) as rating_1
            FROM reviews r
            WHERE r.status = 'approved'
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getOverallReviewStats: " . $e->getMessage());
        return null;
    }
}

/**
 * Lấy reviews gần đây
 */
function getRecentReviews($limit = 6)
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT r.*, u.full_name, p.name as product_name
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.user_id
            LEFT JOIN products p ON r.product_id = p.product_id
            WHERE r.status = 'approved'
            ORDER BY r.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getRecentReviews: " . $e->getMessage());
        return [];
    }
}

/**
 * Xử lý submit review
 */
function handleReviewSubmission($pdo, $user_id)
{
    $result = ['success' => false, 'error' => ''];

    if (!$user_id) {
        $result['error'] = 'Vui lòng đăng nhập để viết đánh giá';
        return $result;
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Validation
    if (!$product_id) {
        $result['error'] = 'Sản phẩm không tồn tại';
        return $result;
    }

    if (!hasUserPurchasedProduct($pdo, $product_id, $user_id)) {
        $result['error'] = 'Bạn cần mua sản phẩm này trước khi viết đánh giá';
        return $result;
    }

    if (hasUserReviewedProduct($pdo, $product_id, $user_id)) {
        $result['error'] = 'Bạn đã viết đánh giá cho sản phẩm này';
        return $result;
    }

    if ($rating < 1 || $rating > 5) {
        $result['error'] = 'Rating không hợp lệ';
        return $result;
    }

    if (strlen($title) < 5) {
        $result['error'] = 'Tiêu đề phải có ít nhất 5 ký tự';
        return $result;
    }

    if (strlen($content) < 20) {
        $result['error'] = 'Nội dung phải có ít nhất 20 ký tự';
        return $result;
    }

    // Create review
    $review_result = createReview($pdo, $product_id, $user_id, $title, $content, $rating);

    if (!$review_result['success']) {
        $result['error'] = $review_result['message'] ?? 'Có lỗi xảy ra';
        return $result;
    }

    // Handle image uploads
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = dirname(__FILE__) . '/uploads/reviews/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $review_id = $review_result['review_id'];
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if (!empty($tmp_name) && $_FILES['images']['error'][$key] === 0) {
                $file_ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));

                if (in_array($file_ext, $allowed) && $_FILES['images']['size'][$key] <= 5000000) {
                    $filename = 'review_' . $review_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                    $filepath = $upload_dir . $filename;

                    if (move_uploaded_file($tmp_name, $filepath)) {
                        addReviewImage($pdo, $review_id, 'uploads/reviews/' . $filename);
                    }
                }
            }
        }
    }

    $result['success'] = true;
    return $result;
}

// ================================================
// 🎨 RENDERING FUNCTIONS
// ================================================

/**
 * Render search form
 */
function renderSearchForm($filters, $categories, $brands, $is_build_mode, $build_mode, $build_id, $item_id)
{
?>
    <form method="GET" class="search-bar">
        <?php if ($is_build_mode): ?>
            <input type="hidden" name="mode" value="<?= escape($build_mode) ?>">
            <input type="hidden" name="build_id" value="<?= $build_id ?>">
            <?php if ($item_id): ?>
                <input type="hidden" name="item_id" value="<?= $item_id ?>">
            <?php endif; ?>
        <?php endif; ?>

        <input type="text" name="keyword" placeholder="Tìm sản phẩm..."
            value="<?= escape($filters['keyword']) ?>">

        <select name="category_id">
            <option value="">-- Danh mục --</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['category_id'] ?>"
                    <?= $filters['category_id'] == $c['category_id'] ? 'selected' : '' ?>>
                    <?= escape($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="brand_id">
            <option value="">-- Thương hiệu --</option>
            <?php foreach ($brands as $b): ?>
                <option value="<?= $b['brand_id'] ?>"
                    <?= $filters['brand_id'] == $b['brand_id'] ? 'selected' : '' ?>>
                    <?= escape($b['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="number" name="min_price" placeholder="Giá từ..."
            value="<?= $filters['min_price'] > 0 ? $filters['min_price'] : '' ?>">
        <input type="number" name="max_price" placeholder="Giá đến..."
            value="<?= $filters['max_price'] > 0 ? $filters['max_price'] : '' ?>">

        <button type="submit" class="btn-search">
            <i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm
        </button>
    </form>
    <?php
}

/**
 * Render product cards
 */
function renderProducts($products, $is_build_mode, $build_mode, $build_id, $item_id)
{
    $pdo = getPDO();

    foreach ($products as $p):
        $image_path = getProductImage($p);
        $promotion = getProductPromotion($p['product_id']);
        $has_promotion = !empty($promotion);

        $original_price = $p['price'];
        $discount_percent = $has_promotion ? $promotion['discount_percent'] : 0;
        $sale_price = $has_promotion ? calculateSalePrice($original_price, $discount_percent) : $original_price;
        $sold_count = $p['sold_count'] ?? 0;
    ?>
        <div class="product-card" data-product-id="<?= $p['product_id'] ?>">
            <!-- Image -->
            <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="image-link" target="_blank">
                <div class="image-wrapper">
                    <?php if ($has_promotion): ?>
                        <div class="discount-badge">-<?= $discount_percent ?>%</div>
                    <?php endif; ?>

                    <?php if ($p['is_hot'] ?? false): ?>
                        <div class="hot-badge">HOT</div>
                    <?php endif; ?>

                    <img src="../<?= escape($image_path) ?>"
                        alt="<?= escape($p['name']) ?>"
                        onerror="this.src='../uploads/img/no-image.png'">

                    <?php if ($is_build_mode): ?>
                        <div class="image-overlay">
                            <i class="fa fa-eye"></i>
                            <span>Xem chi tiết</span>
                        </div>
                    <?php endif; ?>
                </div>
            </a>

            <!-- Product Info -->
            <div class="product-info-section">
                <h3 class="product-name"><?= escape($p['name']) ?></h3>

                <p class="brand-cat">
                    <?= escape($p['brand_name'] ?? 'Thương hiệu') ?> •
                    <?= escape($p['category_name'] ?? 'Danh mục') ?>
                </p>

                <?php if ($has_promotion): ?>
                    <div class="price-section">
                        <div class="price-row">
                            <span class="original-price"><?= formatPriceVND($original_price) ?></span>
                            <span class="discount-percent">-<?= $discount_percent ?>%</span>
                        </div>
                        <div class="sale-price"><?= formatPriceVND($sale_price) ?></div>
                    </div>
                <?php else: ?>
                    <div class="price-section">
                        <div class="current-price"><?= formatPriceVND($original_price) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($sold_count > 0): ?>
                    <div class="sold-count">
                        <i class="fa-solid fa-box"></i> Đã bán: <?= number_format($sold_count) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <?php if ($is_build_mode): ?>
                <div class="build-mode-actions">
                    <button type="button"
                        class="select-product-btn"
                        data-product-id="<?= $p['product_id'] ?>"
                        data-build-id="<?= $build_id ?>"
                        data-item-id="<?= $item_id ?>"
                        data-mode="<?= $build_mode ?>"
                        data-product-name="<?= escape($p['name']) ?>">
                        <?php if ($build_mode === 'replace'): ?>
                            <i class="fa fa-exchange-alt"></i> <span>Thay thế</span>
                        <?php else: ?>
                            <i class="fa fa-plus-circle"></i> <span>Thêm vào Build</span>
                        <?php endif; ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="normal-mode-actions">
                    <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="btn-view-detail">
                        <i class="fa fa-eye"></i> Xem chi tiết
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php
    endforeach;
}

/**
 * Render reviews section
 */
function renderReviewsSection($review_stats, $recent_reviews)
{
    $pdo = getPDO();
    ?>
    <div class="reviews-section">
        <div class="reviews-header">
            <h2>⭐ Đánh Giá Từ Khách Hàng</h2>
            <div style="display: flex; gap: 10px;">
                <button class="btn-write-review" onclick="openReviewModal()" title="Viết đánh giá">
                    <i class="fa-solid fa-pen"></i> Viết đánh giá
                </button>
                <a href="product-reviews.php" class="btn-view-all-reviews">Xem Tất Cả →</a>
            </div>
        </div>

        <?php if ($review_stats && $review_stats['total_reviews'] > 0): ?>
            <div class="reviews-stats">
                <div class="rating-summary">
                    <div class="rating-value-large"><?= number_format($review_stats['avg_rating'], 1) ?></div>
                    <div class="rating-stars-large"><?= renderStarsBadge($review_stats['avg_rating']) ?></div>
                    <div class="rating-count-text"><?= $review_stats['total_reviews'] ?> đánh giá</div>
                </div>

                <div class="rating-distribution">
                    <?php for ($i = 5; $i >= 1; $i--):
                        $count = $review_stats["rating_$i"] ?? 0;
                        $percentage = $review_stats['total_reviews'] > 0 ? ($count / $review_stats['total_reviews']) * 100 : 0;
                    ?>
                        <div class="rating-bar-row">
                            <span class="rating-bar-label"><?= $i ?>★</span>
                            <div class="rating-bar-track">
                                <div class="rating-bar-fill" style="width: <?= $percentage ?>%;"></div>
                            </div>
                            <span class="rating-bar-count"><?= $count ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="reviews-list">
                <?php foreach ($recent_reviews as $review):
                    $images = getReviewImages($pdo, $review['review_id']);
                ?>
                    <div class="review-item">
                        <div class="review-item-header">
                            <div>
                                <div class="review-item-author"><?= escape($review['full_name']) ?></div>
                                <div class="review-item-date"><?= formatDate($review['created_at'], 'd/m/Y') ?></div>
                            </div>
                            <span class="review-badge">✓ Đã mua</span>
                        </div>

                        <div class="review-item-rating"><?= renderStarsBadge($review['rating']) ?></div>
                        <div class="review-item-title"><?= escape($review['title']) ?></div>
                        <div class="review-item-content"><?= escape($review['content']) ?></div>

                        <?php if (!empty($images)): ?>
                            <div class="review-item-images">
                                <?php foreach (array_slice($images, 0, 3) as $img): ?>
                                    <div class="review-item-img">
                                        <img src="../<?= escape($img['image_path']) ?>"
                                            alt="Review"
                                            onerror="this.src='../assets/images/placeholder.jpg'">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="review-item-footer">
                            <span>📦 <?= truncateText($review['product_name'], 20) ?></span>
                            <span>👍 <?= $review['helpful_count'] ?? 0 ?> hữu ích</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-reviews">
                <i class="fa-solid fa-star"></i>
                <p>Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
            </div>
        <?php endif; ?>
    </div>
<?php
}

/**
 * Render review modal
 */
function renderReviewModal($review_success, $review_error)
{
?>
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Viết Đánh Giá</h2>
                <button class="modal-close" onclick="closeReviewModal()">×</button>
            </div>

            <?php if ($review_success): ?>
                <div class="success-msg">
                    <span>✓</span>
                    <span>Đánh giá của bạn đã được gửi thành công! Sẽ được kiểm duyệt trong 24 giờ.</span>
                </div>
            <?php elseif (!empty($review_error)): ?>
                <div class="error-msg">
                    <span>⚠️</span>
                    <span><?= escape($review_error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="write_review">
                <input type="hidden" name="product_id" id="modalProductId" value="">

                <!-- Rating -->
                <div class="form-group">
                    <label>Đánh giá <span class="required">*</span></label>
                    <div class="rating-input" id="ratingInput">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="rating-btn <?= $i <= 5 ? 'active' : '' ?>"
                                data-rating="<?= $i ?>" onclick="setRating(<?= $i ?>, event)">★</button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" value="5" id="ratingValue">
                </div>

                <!-- Title -->
                <div class="form-group">
                    <label>Tiêu đề <span class="required">*</span></label>
                    <input type="text" name="title"
                        placeholder="Ví dụ: Sản phẩm rất tốt, giao hàng nhanh"
                        maxlength="200" required
                        oninput="updateCount(this, 'titleCount')">
                    <div class="char-count"><span id="titleCount">0</span>/200</div>
                </div>

                <!-- Content -->
                <div class="form-group">
                    <label>Nội dung <span class="required">*</span></label>
                    <textarea name="content"
                        placeholder="Hãy kể chi tiết về sản phẩm này..."
                        maxlength="2000" required
                        oninput="updateCount(this, 'contentCount')"></textarea>
                    <div class="char-count"><span id="contentCount">0</span>/2000</div>
                </div>

                <!-- Images -->
                <div class="form-group">
                    <label>Thêm ảnh (tùy chọn)</label>
                    <div class="upload-area"
                        onclick="document.getElementById('reviewImageInput').click()"
                        ondragover="this.style.background='#f0f7ff'"
                        ondragleave="this.style.background='white'"
                        ondrop="handleImageDrop(event)">
                        <div><i class="fa-solid fa-image"></i></div>
                        <div>Kéo và thả ảnh hoặc click để chọn</div>
                        <small>Tối đa 5 ảnh, mỗi ảnh dưới 5MB (JPG, PNG, WebP)</small>
                    </div>
                    <input type="file" id="reviewImageInput" name="images[]"
                        multiple accept="image/*" style="display: none;"
                        onchange="previewReviewImages(this.files)">
                    <div id="previewImages" class="preview-images"></div>
                </div>

                <!-- Actions -->
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeReviewModal()">Hủy</button>
                    <button type="submit" class="btn-submit">✓ Gửi Đánh Giá</button>
                </div>
            </form>
        </div>
    </div>
<?php
}

/**
 * Render stars badge
 */
function renderStarsBadge($rating)
{
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fa-solid fa-star"></i>';
        } elseif ($i - $rating < 1) {
            $stars .= '<i class="fa-solid fa-star-half-stroke"></i>';
        } else {
            $stars .= '<i class="fa-regular fa-star"></i>';
        }
    }
    return $stars;
}
