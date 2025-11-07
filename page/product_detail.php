<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ===== CSRF TOKEN =====
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// ===== GET PRODUCT ID =====
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: products.php');
    exit;
}

// ===== DATABASE =====
$pdo = getPDO();

// ===== GET PRODUCT DETAILS =====
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, b.name AS brand_name, b.logo AS brand_logo
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.product_id = :product_id
");
$stmt->execute([':product_id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

// ===== GET PRODUCT IMAGES =====
$stmt = $pdo->prepare("
    SELECT * FROM product_images 
    WHERE product_id = :product_id 
    ORDER BY is_primary DESC, image_id ASC
");
$stmt->execute([':product_id' => $product_id]);
$product_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no images, use main_image
if (empty($product_images) && $product['main_image']) {
    $product_images = [['image_path' => $product['main_image'], 'is_primary' => 1]];
}

// ===== GET PRODUCT SPECIFICATIONS =====
$stmt = $pdo->prepare("
    SELECT * FROM product_specifications 
    WHERE product_id = :product_id
    ORDER BY spec_order ASC, spec_id ASC
");
$stmt->execute([':product_id' => $product_id]);
$specifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== GET REVIEWS =====
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.avatar
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.product_id = :product_id AND r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([':product_id' => $product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== GET REVIEW STATISTICS =====
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
    FROM reviews
    WHERE product_id = :product_id AND status = 'approved'
");
$stmt->execute([':product_id' => $product_id]);
$review_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// ===== GET RELATED PRODUCTS =====
$stmt = $pdo->prepare("
    SELECT p.*, b.name AS brand_name
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.category_id = :category_id 
    AND p.product_id != :product_id
    AND p.stock > 0
    ORDER BY RAND()
    LIMIT 6
");
$stmt->execute([
    ':category_id' => $product['category_id'],
    ':product_id' => $product_id
]);
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== GET CART COUNT =====
$user_id = getCurrentUserId();
$cart_count = $user_id ? getCartCount($user_id) : 0;

// ===== CHECK IF FLASH SALE =====
$is_flash_sale = false;
$flash_sale_end = null;
$original_price = $product['price'];
$discount_percent = 0;

// Check for active promotions
$stmt = $pdo->prepare("
    SELECT * FROM promotions 
    WHERE product_id = :product_id 
    AND start_date <= NOW() 
    AND end_date >= NOW()
    AND is_active = 1
    ORDER BY discount_percent DESC
    LIMIT 1
");
$stmt->execute([':product_id' => $product_id]);
$promotion = $stmt->fetch(PDO::FETCH_ASSOC);

if ($promotion) {
    $is_flash_sale = true;
    $flash_sale_end = $promotion['end_date'];
    $discount_percent = $promotion['discount_percent'];
    $product['sale_price'] = $product['price'] * (1 - $discount_percent / 100);
}

// Include template
include 'product_detail_template.php';
?>