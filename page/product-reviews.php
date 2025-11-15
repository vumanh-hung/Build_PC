<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// Lấy tham số lọc
$search = trim($_GET['search'] ?? '');
$category = intval($_GET['category'] ?? 0);
$sort = $_GET['sort'] ?? 'newest';
$page = intval($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$per_page = 12;
$offset = ($page - 1) * $per_page;

// Xây dựng query
$where = [];
$params = [];

if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $where[] = "p.category_id = ?";
    $params[] = $category;
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Sắp xếp
$order_by = match($sort) {
    'oldest' => 'p.created_at ASC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'rating' => 'avg_rating DESC',
    default => 'p.created_at DESC'
};

// Đếm tổng
$count_query = "SELECT COUNT(*) as total FROM products p $where_clause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Lấy dữ liệu - CHỈ lấy approved reviews + ảnh sản phẩm
$query = "
    SELECT p.*, c.name as category_name,
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(r.review_id) as total_reviews,
           pi.image_path,
           pi.is_primary
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN reviews r ON p.product_id = r.product_id AND r.status = 'approved'
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
    $where_clause
    GROUP BY p.product_id
    ORDER BY $order_by
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value);
}
$stmt->bindValue(count($params) + 1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== XỬ LÝ ĐƯỜNG DẪN ẢNH CHO TỪNG PRODUCT =====
foreach ($products as &$product) {
    if (!empty($product['image_path'])) {
        $product['display_image'] = getProductImagePath($product['image_path']);
    } elseif (!empty($product['main_image'])) {
        $product['display_image'] = getProductImagePath($product['main_image']);
    } else {
        $product['display_image'] = 'uploads/img/no-image.png';
    }
}

// Lấy danh sách category
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách sản phẩm - Đánh giá từ khách hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header h1 {
            font-size: 28px;
            color: #667eea;
            flex: 1;
            min-width: 200px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .header a {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .header a:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .filters {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 600;
            font-size: 13px;
            color: #666;
        }

        .filters input,
        .filters select {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .filters input:focus,
        .filters select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        .filter-buttons button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .filter-buttons button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .filter-buttons a {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-buttons a:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            aspect-ratio: 1;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s;
            padding: 10px;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #667eea;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-content {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-category {
            font-size: 12px;
            color: #007bff;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .product-name {
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.4;
            min-height: 2.8em;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .product-rating .stars {
            font-size: 14px;
        }

        .rating-count {
            font-size: 12px;
            color: #6c757d;
        }

        .product-price {
            font-size: 18px;
            font-weight: 800;
            color: #ff6b6b;
            margin-bottom: 15px;
        }

        .price-value {
            display: block;
        }

        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }

        .btn-review {
            flex: 1;
            background: #ffc107;
            color: #333;
            border: none;
            padding: 10px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-review:hover {
            background: #ffb300;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        .add-to-cart-form {
            display: flex;
            gap: 8px;
        }

        .quantity-wrapper {
            display: flex;
            gap: 8px;
            flex: 1;
        }

        .qty-control {
            display: flex;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        .qty-btn {
            background: #f5f5f5;
            border: none;
            width: 32px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .qty-btn:hover {
            background: #667eea;
            color: white;
        }

        .qty-input {
            flex: 1;
            border: none;
            text-align: center;
            font-weight: 600;
            width: 40px;
        }

        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .add-to-cart-btn {
            flex: 1;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s;
            padding: 8px 12px;
            font-size: 13px;
        }

        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .empty {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            color: #6c757d;
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 8px 12px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }

        .pagination .current {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ===== MODAL REVIEW ===== */
        .review-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .review-modal.active {
            display: flex;
        }

        .review-modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .review-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }

        .review-modal-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .review-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }

        .review-modal-close:hover {
            color: #333;
        }

        #reviewForm {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group small {
            display: block;
            margin-top: 4px;
            color: #999;
            font-size: 12px;
        }

        .rating-input {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .star-rating {
            display: flex;
            gap: 8px;
        }

        .star-rating .star {
            font-size: 32px;
            cursor: pointer;
            color: #ddd;
            transition: all 0.2s;
            user-select: none;
        }

        .star-rating .star:hover,
        .star-rating .star.active {
            color: #ffc107;
            transform: scale(1.1);
        }

        .rating-text {
            font-weight: 600;
            color: #667eea;
            min-width: 80px;
        }

        .image-upload {
            position: relative;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f9f9f9;
        }

        .image-upload:hover {
            border-color: #667eea;
            background: #f5f8ff;
        }

        .image-upload input[type="file"] {
            display: none;
        }

        .upload-hint {
            pointer-events: none;
        }

        .upload-hint i {
            font-size: 36px;
            color: #ddd;
            display: block;
            margin-bottom: 8px;
        }

        .upload-hint p {
            margin: 0 0 4px 0;
            color: #333;
            font-weight: 600;
        }

        .upload-hint small {
            color: #999;
        }

        .image-preview {
            position: relative;
            margin-top: 12px;
            border-radius: 8px;
            overflow: hidden;
            display: none;
        }

        .image-preview.active {
            display: block;
        }

        .image-preview img {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .remove-image {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remove-image:hover {
            background: rgba(0, 0, 0, 0.9);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #eee;
        }

        .btn-cancel,
        .btn-submit {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-cancel {
            background: #e9ecef;
            color: #333;
        }

        .btn-cancel:hover {
            background: #dee2e6;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .review-alert {
            padding: 16px 24px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .review-alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .review-alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }

            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                width: 100%;
            }

            .header {
                flex-direction: column;
            }

            .filter-buttons {
                width: 100%;
            }

            .filter-buttons button,
            .filter-buttons a {
                flex: 1;
            }

            .product-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>⭐ Đánh giá từ khách hàng</h1>
        <div class="header-actions">
            <a href="../index.php">← Quay lại trang chủ</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <form method="GET" style="display: flex; gap: 15px; width: 100%; flex-wrap: wrap; align-items: flex-end;">
            <div class="filter-group">
                <label>Tìm kiếm</label>
                <input type="text" name="search" placeholder="Nhập tên sản phẩm..." value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="filter-group">
                <label>Danh mục</label>
                <select name="category">
                    <option value="">— Tất cả danh mục —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= $category == $cat['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Sắp xếp</label>
                <select name="sort">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
                    <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Giá thấp</option>
                    <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Giá cao</option>
                    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Đánh giá cao</option>
                </select>
            </div>

            <div class="filter-buttons">
                <button type="submit">🔍 Tìm kiếm</button>
                <a href="product-reviews.php">↻ Reset</a>
            </div>
        </form>
    </div>

    <!-- Products Grid -->
    <?php if (!empty($products)): ?>
        <div class="products-grid">
            <?php foreach ($products as $p): 
                $avg_rating = round($p['avg_rating'] ?? 0, 1);
            ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="../<?= htmlspecialchars($p['display_image']); ?>" 
                         alt="<?= htmlspecialchars($p['name']); ?>"
                         onerror="this.src='../uploads/img/no-image.png'"
                         loading="lazy">
                    <div class="product-badge">
                        <span>Mới</span>
                    </div>
                </div>

                <div class="product-content">
                    <div class="product-category"><?= htmlspecialchars($p['category_name'] ?? 'N/A') ?></div>
                    <h3 class="product-name" title="<?= htmlspecialchars($p['name']) ?>">
                        <?= htmlspecialchars($p['name']) ?>
                    </h3>

                    <div class="product-rating">
                        <div class="stars"><?= renderStars(floor($avg_rating)) ?></div>
                        <span class="rating-count"><?= $avg_rating ?>/5 (<?= $p['total_reviews'] ?> đánh giá)</span>
                    </div>

                    <div class="product-price">
                        <span class="price-value"><?= number_format($p['price']) ?>₫</span>
                    </div>

                    <div class="product-actions">
                        <button type="button" class="btn-review" onclick="openReviewModal(<?= $p['product_id'] ?>)">
                            <i class="fa-solid fa-star"></i> Viết đánh giá
                        </button>
                        <form class="add-to-cart-form" onsubmit="return false;">
                            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                            <div class="quantity-wrapper">
                                <div class="qty-control">
                                    <button type="button" class="qty-btn qty-minus">−</button>
                                    <input type="number" name="quantity" value="1" min="1" max="99" class="qty-input" readonly>
                                    <button type="button" class="qty-btn qty-plus">+</button>
                                </div>
                                <button type="button" class="add-to-cart-btn">
                                    <i class="fa-solid fa-cart-plus"></i> Thêm
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?= $search ? '&search=' . urlencode($search) : '' ?><?= $category ? '&category=' . $category : '' ?><?= $sort ? '&sort=' . $sort : '' ?>">« Đầu</a>
                    <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category ? '&category=' . $category : '' ?><?= $sort ? '&sort=' . $sort : '' ?>">‹ Trước</a>
                <?php else: ?>
                    <span class="disabled">« Đầu</span>
                    <span class="disabled">‹ Trước</span>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);

                if ($start > 1) {
                    echo '<a href="?page=1' . ($search ? '&search=' . urlencode($search) : '') . ($category ? '&category=' . $category : '') . ($sort ? '&sort=' . $sort : '') . '">1</a>';
                    if ($start > 2) echo '<span class="disabled">...</span>';
                }

                for ($i = $start; $i <= $end; $i++):
                    $pageUrl = '?page=' . $i;
                    if ($search) $pageUrl .= '&search=' . urlencode($search);
                    if ($category) $pageUrl .= '&category=' . $category;
                    if ($sort) $pageUrl .= '&sort=' . $sort;
                    
                    if ($i == $page):
                        echo '<span class="current">' . $i . '</span>';
                    else:
                        echo '<a href="' . $pageUrl . '">' . $i . '</a>';
                    endif;
                endfor;

                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) echo '<span class="disabled">...</span>';
                    echo '<a href="?page=' . $total_pages . ($search ? '&search=' . urlencode($search) : '') . ($category ? '&category=' . $category : '') . ($sort ? '&sort=' . $sort : '') . '">' . $total_pages . '</a>';
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category ? '&category=' . $category : '' ?><?= $sort ? '&sort=' . $sort : '' ?>">Sau ›</a>
                    <a href="?page=<?= $total_pages ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category ? '&category=' . $category : '' ?><?= $sort ? '&sort=' . $sort : '' ?>">Cuối »</a>
                <?php else: ?>
                    <span class="disabled">Sau ›</span>
                    <span class="disabled">Cuối »</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty">
            <div class="empty-icon">📭</div>
            <h3>Không tìm thấy sản phẩm</h3>
            <p>Thử tìm kiếm với từ khóa khác hoặc xem tất cả sản phẩm.</p>
        </div>
    <?php endif; ?>
</div>

<!-- ===== MODAL REVIEW FORM ===== -->
<div id="reviewModal" class="review-modal">
    <div class="review-modal-content">
        <div class="review-modal-header">
            <h2>Viết Đánh Giá Sản Phẩm</h2>
            <button class="review-modal-close" onclick="closeReviewModal()">✕</button>
        </div>

        <form id="reviewForm" enctype="multipart/form-data" onsubmit="submitReview(event)">
            <input type="hidden" id="productId" name="product_id" value="">

            <!-- Alert Messages -->
            <div id="reviewAlert" class="review-alert" style="display: none;"></div>

            <!-- Rating -->
            <div class="form-group">
                <label>Đánh giá *</label>
                <div class="rating-input">
                    <input type="hidden" id="ratingValue" name="rating" value="5">
                    <div class="star-rating" id="starRating">
                        <span class="star active" data-value="1">★</span>
                        <span class="star active" data-value="2">★</span>
                        <span class="star active" data-value="3">★</span>
                        <span class="star active" data-value="4">★</span>
                        <span class="star active" data-value="5">★</span>
                    </div>
                    <span class="rating-text" id="ratingText">Tuyệt vời</span>
                </div>
            </div>

            <!-- Title -->
            <div class="form-group">
                <label>Tiêu đề *</label>
                <input type="text" name="title" placeholder="Ví dụ: Sản phẩm rất tốt, giao hàng nhanh" maxlength="200" required>
                <small><span id="titleCount">0</span>/200</small>
            </div>

            <!-- Comment -->
            <div class="form-group">
                <label>Nội dung đánh giá *</label>
                <textarea name="comment" placeholder="Hãy kể chi tiết về sản phẩm này..." maxlength="2000" required></textarea>
                <small><span id="commentCount">0</span>/2000</small>
            </div>

            <!-- Image Upload -->
            <div class="form-group">
                <label>Thêm ảnh (tùy chọn)</label>
                <div class="image-upload" onclick="document.getElementById('imageInput').click()">
                    <input type="file" id="imageInput" name="image" accept="image/*" onchange="previewImage(event)">
                    <div class="upload-hint">
                        <i class="fa-solid fa-image"></i>
                        <p>Nhấp để chọn ảnh hoặc kéo thả</p>
                        <small>JPG, PNG, GIF, WebP (tối đa 5MB)</small>
                    </div>
                </div>
                <div id="imagePreview" class="image-preview">
                    <img id="previewImg" src="" alt="Preview">
                    <button type="button" class="remove-image" onclick="removeImage()">✕</button>
                </div>
            </div>

            <!-- Buttons -->
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeReviewModal()">Hủy</button>
                <button type="submit" class="btn-submit" id="submitBtn">Gửi Đánh Giá</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ===== QUANTITY CONTROL =====
    document.querySelectorAll('.qty-minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.qty-input');
            if (input.value > 1) input.value--;
        });
    });

    document.querySelectorAll('.qty-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.qty-input');
            if (input.value < 99) input.value++;
        });
    });

    // ===== RATING CONTROL =====
    const stars = document.querySelectorAll('.star-rating .star');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const value = this.dataset.value;
            document.getElementById('ratingValue').value = value;

            stars.forEach(s => {
                if (s.dataset.value <= value) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });

            const texts = {
                1: 'Rất tệ',
                2: 'Tệ',
                3: 'Bình thường',
                4: 'Tốt',
                5: 'Tuyệt vời'
            };
            document.getElementById('ratingText').textContent = texts[value];
        });

        star.addEventListener('mouseover', function() {
            const value = this.dataset.value;
            stars.forEach(s => {
                if (s.dataset.value <= value) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });

    document.getElementById('starRating').addEventListener('mouseleave', function() {
        const currentValue = document.getElementById('ratingValue').value;
        stars.forEach(s => {
            if (s.dataset.value <= currentValue) {
                s.style.color = '#ffc107';
            } else {
                s.style.color = '#ddd';
            }
        });
    });

    // ===== CHARACTER COUNTER =====
    document.querySelector('input[name="title"]').addEventListener('input', function() {
        document.getElementById('titleCount').textContent = this.value.length;
    });

    document.querySelector('textarea[name="comment"]').addEventListener('input', function() {
        document.getElementById('commentCount').textContent = this.value.length;
    });

    // ===== IMAGE PREVIEW =====
    function previewImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').classList.add('active');
            };
            reader.readAsDataURL(file);
        }
    }

    function removeImage() {
        document.getElementById('imageInput').value = '';
        document.getElementById('imagePreview').classList.remove('active');
    }

    // ===== MODAL CONTROL =====
    function openReviewModal(productId) {
        if (!<?= isset($_SESSION['user']) ? 'true' : 'false' ?>) {
            alert('Vui lòng đăng nhập để viết đánh giá');
            window.location.href = '../page/login.php';
            return;
        }
        
        document.getElementById('productId').value = productId;
        document.getElementById('reviewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeReviewModal() {
        document.getElementById('reviewModal').classList.remove('active');
        document.getElementById('reviewForm').reset();
        removeImage();
        document.body.style.overflow = 'auto';
    }

    // Close modal khi click outside
    document.getElementById('reviewModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeReviewModal();
        }
    });

    // ===== SUBMIT REVIEW =====
    async function submitReview(e) {
        e.preventDefault();

        const formData = new FormData(document.getElementById('reviewForm'));
        const btn = document.getElementById('submitBtn');
        const alert = document.getElementById('reviewAlert');

        btn.disabled = true;
        alert.className = 'review-alert';
        alert.innerHTML = '<span>⏳ Đang gửi...</span>';
        alert.style.display = 'flex';

        try {
            const response = await fetch('../api/submit-review.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                alert.className = 'review-alert success';
                alert.innerHTML = '<span>✓</span> <span>' + data.message + '</span>';
                alert.style.display = 'flex';
                
                setTimeout(() => {
                    closeReviewModal();
                    location.reload();
                }, 2000);
            } else {
                alert.className = 'review-alert error';
                alert.innerHTML = '<span>⚠️</span> <span>' + data.message + '</span>';
                alert.style.display = 'flex';
                btn.disabled = false;
            }
        } catch (error) {
            alert.className = 'review-alert error';
            alert.innerHTML = '<span>⚠️</span> <span>Có lỗi xảy ra, vui lòng thử lại</span>';
            alert.style.display = 'flex';
            btn.disabled = false;
        }
    }
</script>

</body>
</html>