<?php
session_start();
require_once __DIR__ . '/../db.php';

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

// Lấy dữ liệu
$query = "
    SELECT p.*, c.name as category_name,
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(r.review_id) as total_reviews
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN reviews r ON p.product_id = r.product_id AND r.status = 'approved'
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

// Lấy danh sách category
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '⭐' : '☆';
    }
    return $stars;
}

function renderProducts($products) {
    foreach ($products as $p): ?>
      <div class="product-card" data-aos="fade-up">
        <div class="product-image">
          <img src="uploads/<?php echo htmlspecialchars($p['image'] ?? 'default.png'); ?>" 
     alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy">
          <div class="product-overlay">
            <div class="quick-view">
              <i class="fa-solid fa-eye"></i> Xem nhanh
            </div>
          </div>
          <div class="product-badge">
            <span>Mới</span>
          </div>
        </div>
        <div class="product-content">
          <div class="product-category"><?php echo htmlspecialchars($p['category_name'] ?? 'Khác'); ?></div>
          <h3 class="product-name" title="<?php echo htmlspecialchars($p['name']); ?>"><?php echo htmlspecialchars($p['name']); ?></h3>
          <div class="product-rating">
            <div class="stars">
              <i class="fa-solid fa-star"></i>
              <i class="fa-solid fa-star"></i>
              <i class="fa-solid fa-star"></i>
              <i class="fa-solid fa-star"></i>
              <i class="fa-solid fa-star-half-stroke"></i>
            </div>
            <span class="rating-count">(<?php echo round($p['avg_rating'], 1); ?>)</span>
          </div>
          <p class="product-price">
            <span class="price-value"><?php echo number_format($p['price']); ?>₫</span>
          </p>
          <form class="add-to-cart-form" data-product-id="<?php echo $p['product_id']; ?>" onsubmit="return false;">
            <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
            <div class="quantity-wrapper">
              <div class="qty-control">
                <button type="button" class="qty-btn qty-minus">−</button>
                <input type="number" name="quantity" value="1" min="1" max="99" class="qty-input" readonly>
                <button type="button" class="qty-btn qty-plus">+</button>
              </div>
              <button type="button" class="add-to-cart-btn">
                <i class="fa-solid fa-cart-plus"></i>
                <span>Thêm</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    <?php endforeach;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách sản phẩm - Đánh giá từ khách hàng</title>
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
            align-items: center;
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
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-info {
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

        .product-stars {
            font-size: 16px;
            color: #ffc107;
        }

        .product-review-count {
            font-size: 12px;
            color: #6c757d;
        }

        .product-price {
            font-size: 18px;
            font-weight: 800;
            color: #ff6b6b;
            margin-bottom: 10px;
        }

        .product-stock {
            font-size: 13px;
            margin-bottom: 12px;
        }

        .product-stock.available {
            color: #28a745;
        }

        .product-stock.limited {
            color: #ffc107;
        }

        .product-stock.out {
            color: #dc3545;
        }

        .product-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-detail {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-cart {
            background: #28a745;
            color: white;
        }

        .btn-cart:hover {
            background: #20c997;
            transform: translateY(-2px);
        }

        .btn-cart:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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

        .pagination span.current {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }

        .pagination span.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }

            .filters {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            .header {
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
                $stock_class = $p['stock'] > 10 ? 'available' : ($p['stock'] > 0 ? 'limited' : 'out');
                $avg_rating = round($p['avg_rating'] ?? 0, 1);
            ?>
            <div class="product-card">
                <div class="product-image">
                    <?php if ($p['image']): ?>
                        <img src="uploads/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <span style="font-size: 60px; color: #ddd;">📦</span>
                    <?php endif; ?>
                    <div class="product-overlay">
                        <div class="quick-view">
                            <i class="fa-solid fa-eye"></i> Xem nhanh
                        </div>
                    </div>
                    <div class="product-badge">
                        <span>Mới</span>
                    </div>
                </div>

                <div class="product-info">
                    <div class="product-category"><?= htmlspecialchars($p['category_name'] ?? 'N/A') ?></div>
                    <div class="product-name" title="<?= htmlspecialchars($p['name']) ?>">
                        <?= htmlspecialchars($p['name']) ?>
                    </div>

                    <div class="product-rating">
                        <div class="product-stars"><?= renderStars(floor($avg_rating)) ?></div>
                        <div class="product-review-count"><?= $avg_rating ?>/5 (<?= $p['total_reviews'] ?> đánh giá)</div>
                    </div>

                    <div class="product-price">
                        <?= number_format($p['price']) ?>₫
                    </div>

                    <div class="product-stock <?= $stock_class ?>">
                        <?php if ($p['stock'] > 0): ?>
                            ✓ Còn hàng (<?= $p['stock'] ?> sp)
                        <?php else: ?>
                            ✗ Hết hàng
                        <?php endif; ?>
                    </div>

                    <div class="product-actions">
                        <a href="../admin/product_detail.php?id=<?= $p['product_id'] ?>" class="btn btn-detail">
                            👁️ Xem chi tiết
                        </a>
                        <button class="btn btn-cart" <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>
                            🛒
                        </button>
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
                    if ($i == $page):
                        echo '<span class="current">' . $i . '</span>';
                    else:
                        echo '<a href="?page=' . $i . ($search ? '&search=' . urlencode($search) : '') . ($category ? '&category=' . $category : '') . ($sort ? '&sort=' . $sort : '') . '">' . $i . '</a>';
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

</body>
</html>