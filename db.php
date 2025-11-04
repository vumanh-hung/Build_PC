<?php
/**
 * db.php - Kết nối database + định nghĩa constants + utility functions
 */

$db_host = 'localhost';
$db_name = 'buildpc_db';
$db_user = 'root';
$db_pass = '';
$db_port = 3306;

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

// ============================================
// ✅ ĐỊNH NGHĨA CONSTANTS
// ============================================

if (!defined('ADMIN_USER')) define('ADMIN_USER', 'admin');
if (!defined('ADMIN_PASS')) define('ADMIN_PASS', 'admin123');
if (!defined('ADMIN_ROLE')) define('ADMIN_ROLE', 'admin');

if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/uploads');
if (!defined('UPLOAD_BRANDS_DIR')) define('UPLOAD_BRANDS_DIR', __DIR__ . '/uploads/brands');
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 2 * 1024 * 1024);
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
if (!defined('SITE_NAME')) define('SITE_NAME', 'BuildPC.vn');
if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost:9000/qlmt');

// ===== PAYMENT CONSTANTS =====
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

if (!defined('ORDER_STATUSES')) define('ORDER_STATUSES', [
    'pending' => ['label' => 'Chờ thanh toán', 'color' => '#ffc107', 'icon' => 'fa-hourglass-end', 'text_color' => '#856404'],
    'paid' => ['label' => 'Đã thanh toán', 'color' => '#28a745', 'icon' => 'fa-check-circle', 'text_color' => '#0f5132'],
    'shipping' => ['label' => 'Đang giao', 'color' => '#ff9800', 'icon' => 'fa-truck', 'text_color' => '#664d03'],
    'completed' => ['label' => 'Hoàn thành', 'color' => '#17a2b8', 'icon' => 'fa-check-double', 'text_color' => '#055160'],
    'cancelled' => ['label' => 'Đã hủy', 'color' => '#dc3545', 'icon' => 'fa-times-circle', 'text_color' => '#842029']
]);

// ============================================
// ✅ HÀM TIỆN ÍCH - AUTHENTICATION
// ============================================

if (!function_exists('getPDO')) {
    function getPDO() {
        global $pdo;
        return $pdo;
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: ' . SITE_URL . '/page/login.php');
            exit;
        }
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        if (!isAdmin()) {
            header('Location: ' . SITE_URL . '/index.php');
            exit;
        }
    }
}

// ============================================
// ✅ HÀM TIỆN ÍCH - STRING & FORMAT
// ============================================

if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return number_format($price, 0, ',', '.') . ' ₫';
    }
}

if (!function_exists('escape')) {
    function escape($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

if (!function_exists('generateToken')) {
    function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}

// ============================================
// ✅ HÀM TIỆN ÍCH - VALIDATION
// ============================================

if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('isValidPhone')) {
    function isValidPhone($phone) {
        return preg_match('/^0\d{9}$/', $phone);
    }
}

// ============================================
// ✅ HÀM TIỆN ÍCH - STATUS & PAYMENT
// ============================================

if (!function_exists('getOrderStatus')) {
    function getOrderStatus($status) {
        $statuses = ORDER_STATUSES;
        return $statuses[$status] ?? $statuses['pending'];
    }
}

if (!function_exists('getPaymentMethod')) {
    function getPaymentMethod($method) {
        $methods = PAYMENT_METHODS;
        return $methods[$method] ?? ['name' => 'Chưa xác định', 'icon' => 'fa-question'];
    }
}

if (!function_exists('getPaymentMethodIcon')) {
    function getPaymentMethodIcon($method) {
        $icons = [
            'cash' => 'fa-money-bill',
            'bank_transfer' => 'fa-building',
            'credit_card' => 'fa-credit-card',
            'ewallet' => 'fa-mobile',
            'paypal' => 'fa-paypal',
            'momo' => 'fa-circle',
            'zalopay' => 'fa-wallet'
        ];
        return $icons[$method] ?? 'fa-info-circle';
    }
}

// ============================================
// ✅ HÀM TIỆN ÍCH - USER
// ============================================

if (!function_exists('getUserById')) {
    function getUserById($user_id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getUserById: " . $e->getMessage());
            return null;
        }
    }
}

// ============================================
// ✅ HÀM TIỆN ÍCH - ORDER
// ============================================

if (!function_exists('getOrderById')) {
    function getOrderById($order_id, $user_id = null) {
        global $pdo;
        
        try {
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
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error in getOrderById: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('getOrderItems')) {
    function getOrderItems($order_id) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.price_each as price,
                       p.name as product_name, p.main_image as image_url, p.category_id
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = ?
                ORDER BY oi.order_item_id ASC
            ");
            $stmt->execute([$order_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getOrderItems: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('updateOrderStatus')) {
    function updateOrderStatus($order_id, $status, $note = '') {
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            // Cập nhật trạng thái đơn hàng
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET order_status = ?, updated_at = NOW()
                WHERE order_id = ?
            ");
            $stmt->execute([$status, $order_id]);
            
            // Lưu lịch sử (nếu bảng tồn tại)
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
}

if (!function_exists('getUserOrders')) {
    function getUserOrders($user_id, $limit = null, $offset = 0) {
        global $pdo;
        
        try {
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
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getUserOrders: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getOrderSummary')) {
    function getOrderSummary($user_id) {
        global $pdo;
        
        $summary = [
            'total_paid' => 0,
            'count_pending' => 0,
            'count_shipping' => 0,
            'total_orders' => 0
        ];
        
        try {
            $stmt = $pdo->prepare("
                SELECT o.order_status, COUNT(*) as count, COALESCE(SUM(o.total_price), 0) as sum
                FROM orders o
                WHERE o.user_id = ?
                GROUP BY o.order_status
            ");
            $stmt->execute([$user_id]);
            $results = $stmt->fetchAll();
            
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
}

// ============================================
// ✅ HÀM TIỆN ÍCH - ACTIVITY LOG
// ============================================

if (!function_exists('logActivity')) {
    function logActivity($user_id, $action, $details = '') {
        global $pdo;
        
        try {
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
}

// ============================================
// ✅ HÀM TIỆN ÍCH - PAYMENT
// ============================================

if (!function_exists('createPayment')) {
    function createPayment($order_id, $user_id, $payment_method, $transaction_id = '', $amount = 0) {
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            // Lưu lịch sử thanh toán
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO payment_history (order_id, user_id, payment_method, transaction_id, amount, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'completed', NOW())
                ");
                $stmt->execute([$order_id, $user_id, $payment_method, $transaction_id, $amount]);
            } catch (PDOException $e) {
                // Bỏ qua nếu bảng không tồn tại
            }
            
            // Cập nhật trạng thái đơn hàng
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET order_status = 'paid', updated_at = NOW()
                WHERE order_id = ? AND user_id = ?
            ");
            $stmt->execute([$order_id, $user_id]);
            
            // Cập nhật phương thức thanh toán
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
}

// ============================================
// ✅ HÀM TIỆN ÍCH - REDIRECT & MESSAGE
// ============================================

if (!function_exists('redirect')) {
    function redirect($url, $message = '', $type = 'success') {
        if (!empty($message)) {
            $_SESSION['message'] = [
                'text' => $message,
                'type' => $type // success, error, warning, info
            ];
        }
        header("Location: $url");
        exit;
    }
}

if (!function_exists('getFlashMessage')) {
    function getFlashMessage() {
        if (isset($_SESSION['message'])) {
            $message = $_SESSION['message'];
            unset($_SESSION['message']);
            return $message;
        }
        return null;
    }
}
?>
<?php
// ✅ COPY & PASTE vào CUỐI file db.php của bạn

// ============================================
// REVIEW SYSTEM HELPER FUNCTIONS
// ============================================

/**
 * Lấy đánh giá của một sản phẩm (có sort, paging)
 */
if (!function_exists('getProductReviews')) {
    function getProductReviews($pdo, $product_id, $sort = 'newest', $page = 1, $per_page = 10) {
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
    }
}

/**
 * Lấy thống kê rating của sản phẩm
 */
if (!function_exists('getProductRatingStats')) {
    function getProductRatingStats($pdo, $product_id) {
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
    }
}

/**
 * Tạo đánh giá mới
 */
if (!function_exists('createReview')) {
    function createReview($pdo, $product_id, $user_id, $title, $content, $rating, $order_id = null) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO reviews (product_id, user_id, order_id, title, content, rating, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
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
}

/**
 * Thêm ảnh vào đánh giá
 */
if (!function_exists('addReviewImage')) {
    function addReviewImage($pdo, $review_id, $image_path) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO review_images (review_id, image_path)
                VALUES (?, ?)
            ");
            $stmt->execute([$review_id, $image_path]);
            return true;
        } catch (Exception $e) {
            error_log("Add review image error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Lấy ảnh của một đánh giá
 */
if (!function_exists('getReviewImages')) {
    function getReviewImages($pdo, $review_id) {
        $stmt = $pdo->prepare("
            SELECT * FROM review_images
            WHERE review_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$review_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/**
 * Thêm vote helpful/unhelpful
 */
if (!function_exists('voteReview')) {
    function voteReview($pdo, $review_id, $user_id, $vote_type) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM review_votes WHERE review_id = ? AND user_id = ?");
            $stmt->execute([$review_id, $user_id]);
            $existing = $stmt->fetch();
            
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
}

/**
 * Kiểm tra user đã review sản phẩm chưa
 */
if (!function_exists('hasUserReviewedProduct')) {
    function hasUserReviewedProduct($pdo, $product_id, $user_id) {
        $stmt = $pdo->prepare("
            SELECT review_id FROM reviews
            WHERE product_id = ? AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$product_id, $user_id]);
        return $stmt->fetch() ? true : false;
    }
}

/**
 * Kiểm tra user đã mua sản phẩm chưa
 */
if (!function_exists('hasUserPurchasedProduct')) {
    function hasUserPurchasedProduct($pdo, $product_id, $user_id) {
        $stmt = $pdo->prepare("
            SELECT oi.order_item_id FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            WHERE oi.product_id = ? AND o.user_id = ? AND o.order_status IN ('paid', 'shipping', 'completed')
            LIMIT 1
        ");
        $stmt->execute([$product_id, $user_id]);
        return $stmt->fetch() ? true : false;
    }
}

/**
 * Hiển thị sao rating
 */
if (!function_exists('renderStars')) {
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
}

/**
 * Format rating text
 */
if (!function_exists('formatRating')) {
    function formatRating($avg_rating, $total_reviews) {
        return round($avg_rating, 1) . ' sao từ ' . $total_reviews . ' đánh giá';
    }
}

?>
// ===== Hàm trả về PDO =====
if (!function_exists('getPDO')) {
    function getPDO() {
        global $pdo;
        return $pdo;
    }
}
