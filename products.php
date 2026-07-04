<?php
/**
 * products.php - Product Listing Page
 * Xử lý logic hiển thị danh sách sản phẩm, tìm kiếm, lọc và đánh giá
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ================================================
// INITIALIZATION
// ================================================

$pdo = getPDO();
$csrf = generateCSRFToken();
$user_id = getCurrentUserId();

// ================================================
// PAGE CONFIGURATION
// ================================================

$pageTitle = 'Sản phẩm - BuildPC.vn';
$additionalCSS = [
    'assets/css/products.css',
    'assets/css/footer.css'
];
$additionalJS = [
    'assets/js/products.js'
];
$basePath = '../';

// ================================================
// BUILD MODE DETECTION
// ================================================

$build_mode = $_GET['mode'] ?? '';
$build_id = intval($_GET['build_id'] ?? 0);
$item_id = intval($_GET['item_id'] ?? 0);
$is_build_mode = !empty($build_mode) && $build_id > 0;

if ($is_build_mode) {
    error_log("🔧 Build Mode Active: mode={$build_mode}, build_id={$build_id}, item_id={$item_id}");
}

// ================================================
// DATA LOADING
// ================================================

// Categories & Brands
$categories = getCategories();
$brands = getAllBrands();

// Search & Filter Parameters
$filters = [
    'keyword' => trim($_GET['keyword'] ?? ''),
    'category_id' => intval($_GET['category_id'] ?? 0),
    'brand_id' => intval($_GET['brand_id'] ?? 0),
    'min_price' => !empty($_GET['min_price']) ? floatval($_GET['min_price']) : 0,
    'max_price' => !empty($_GET['max_price']) ? floatval($_GET['max_price']) : 0,
];

// Get Products with Filters
$products = getFilteredProducts($filters);

// Cart Count
$cart_count = $user_id ? getCartCount($user_id) : 0;

// ================================================
// REVIEWS DATA (only if not in build mode)
// ================================================

$review_stats = null;
$recent_reviews = [];

if (!$is_build_mode) {
    $review_stats = getOverallReviewStats();
    $recent_reviews = getRecentReviews(6);
}

// ================================================
// HANDLE REVIEW SUBMISSION
// ================================================

$review_error = '';
$review_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'write_review') {
    $result = handleReviewSubmission($pdo, $user_id);
    $review_success = $result['success'];
    $review_error = $result['error'];
}

// ================================================
// RENDER TEMPLATE
// ================================================

include 'products_template.php';
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

header('Content-Type: application/json; charset=utf-8');

try {
    $category_id = $_GET['category_id'] ?? null;
    $keyword = $_GET['search'] ?? '';

    $sql = "
        SELECT 
            p.product_id,
            p.name,
            p.slug,
            p.category_id,
            c.name AS category_name,
            p.brand_id,
            b.name AS brand_name,
            p.price,
            p.stock,
            p.description,
            p.main_image,
            p.created_at
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE 1=1
    ";

    $params = [];
    if ($category_id) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
    }
    if (!empty($keyword)) {
        $sql .= " AND (p.name LIKE ? OR b.name LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
    }

    $sql .= " ORDER BY p.product_id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi truy vấn: ' . $e->getMessage()]);
}
