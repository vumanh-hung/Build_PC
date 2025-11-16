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