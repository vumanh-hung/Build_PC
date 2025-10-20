<?php
session_start();
require_once __DIR__ . '/../db.php';

// Kiểm tra admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../page/login.php');
    exit;
}

$csrf = $_SESSION['csrf'] ?? bin2hex(random_bytes(16));
$_SESSION['csrf'] = $csrf;

// Xử lý xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $csrf) {
        die('CSRF token invalid');
    }
    $product_id = (int)$_POST['product_id'];
    
    // Lấy tên file ảnh để xóa
    $stmt = $pdo->prepare('SELECT image FROM products WHERE product_id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product && $product['image']) {
        $imagePath = __DIR__ . '/../uploads/' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath); // Xóa file ảnh vật lý
        }
    }
    
    // Xóa sản phẩm khỏi database
    $stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
    $stmt->execute([$product_id]);

    header('Location: products_manage.php?msg=deleted');
    exit;
}

// Lấy danh sách sản phẩm
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$query = 'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE 1=1';
$count_query = 'SELECT COUNT(*) as total FROM products p WHERE 1=1';
$params = [];

if ($search) {
    $query .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
    $count_query .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $query .= ' AND p.category_id = ?';
    $count_query .= ' AND p.category_id = ?';
    $params[] = (int)$category_filter;
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total = $count_stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

$query .= ' ORDER BY p.product_id DESC LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($query);
// Bind các tham số cho search và category
foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value);
}
// Bind các tham số cho LIMIT và OFFSET
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách category
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sản phẩm - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS provided by the user is placed here */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #2d3436;
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            font-size: 32px;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .header a, .header button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 15px;
        }

        .header a:hover, .header button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .header a.btn-back {
            background: #6c757d;
        }

        .header a.btn-back:hover {
            background: #5a6268;
            box-shadow: 0 10px 25px rgba(108, 117, 125, 0.3);
        }

        .filters {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            gap: 12px;
            flex: 1;
            min-width: 280px;
            align-items: center;
        }

        .filters input,
        .filters select {
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
            flex: 1;
        }

        .filters input:focus,
        .filters select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filters button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
            font-size: 15px;
        }

        .filters button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-reset {
            background: #6c757d !important;
            text-decoration: none;
            display: inline-flex;
        }

        .btn-reset:hover {
            background: #5a6268 !important;
            box-shadow: 0 10px 25px rgba(90, 98, 104, 0.3) !important;
        }

        .stats {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-item i {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-item span {
            font-size: 15px;
            color: #495057;
        }

        .stat-item strong {
            color: #667eea;
            font-size: 20px;
        }

        .table-wrapper {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 16px;
            text-align: left;
            font-weight: 700;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            overflow: hidden;
            background: #f1f3f5;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            flex: 1;
            min-width: 0;
        }

        .product-name {
            font-weight: 600;
            color: #667eea;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
            margin-bottom: 4px;
        }

        .product-sku {
            font-size: 12px;
            color: #6c757d;
        }

        .category-badge {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            color: #667eea;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        .price {
            color: #ff6b6b;
            font-weight: 700;
            font-size: 16px;
        }

        .stock {
            font-weight: 600;
            color: #2d3436;
        }

        .stock.low {
            color: #ffa500;
        }

        .stock.out {
            color: #ff6b6b;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-edit {
            background: linear-gradient(135deg, #4caf50, #45a049);
            color: white;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        .btn-delete {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #d32f2f, #b71c1c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
        }

        .empty {
            text-align: center;
            padding: 80px 20px;
            color: #6c757d;
        }

        .empty i {
            font-size: 80px;
            opacity: 0.2;
            margin-bottom: 20px;
            display: block;
            color: #667eea;
        }

        .empty h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #495057;
        }

        .empty p {
            margin-bottom: 20px;
            font-size: 15px;
        }

        .empty a {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .empty a:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
            border-left: 5px solid;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left-color: #ff6b6b;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
            padding: 20px;
            flex-wrap: wrap;
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
            border: 2px solid #e9ecef;
            border-radius: 8px;
            text-decoration: none;
            color: #667eea;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 14px;
        }

        .pagination a:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .pagination span.current {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }

        .pagination span.disabled {
            color: #6c757d;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-content h3 {
            margin-bottom: 12px;
            color: #2d3436;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }

        .modal-content i {
            color: #ff6b6b;
            font-size: 22px;
        }

        .modal-content p {
            margin-bottom: 24px;
            color: #6c757d;
            line-height: 1.6;
            font-size: 15px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .modal-actions button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            font-size: 15px;
        }

        .btn-cancel {
            background: #e9ecef;
            color: #495057;
        }

        .btn-cancel:hover {
            background: #dee2e6;
            transform: translateY(-2px);
        }

        .btn-confirm {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
        }

        .btn-confirm:hover {
            background: linear-gradient(135deg, #d32f2f, #b71c1c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
        }

        @media (max-width: 768px) {
            body {
                padding: 15px 0;
            }
            .container {
                padding: 0;
            }

            .header {
                padding: 20px;
                flex-direction: column;
                text-align: center;
            }

            .header h1 {
                width: 100%;
                justify-content: center;
                font-size: 24px;
            }

            .header-actions {
                width: 100%;
                flex-direction: column;
            }

            .header a {
                flex: 1;
                justify-content: center;
                width: 100%;
            }

            .filters {
                flex-direction: column;
                padding: 20px;
                gap: 12px;
            }

            .filter-group {
                width: 100%;
                flex-direction: column;
            }

            .filters input,
            .filters select,
            .filters button {
                width: 100%;
            }

            .stats {
                padding: 20px;
                gap: 20px;
                flex-direction: column;
            }

            .table-wrapper {
                margin: 0 -20px;
                border-radius: 0;
                overflow-x: auto;
            }

            th, td {
                padding: 12px 10px;
                font-size: 12px;
                white-space: nowrap;
            }

            .product-name {
                max-width: 100px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
                font-size: 12px;
                padding: 8px 12px;
            }

            .pagination {
                gap: 4px;
                padding: 15px;
            }

            .pagination a,
            .pagination span {
                min-width: 36px;
                height: 36px;
                font-size: 11px;
                padding: 4px 8px;
            }
        }
    </style>
</head>

<body>

<div class="container">
    <div class="header">
        <h1>
            <i class="fas fa-box"></i> Quản lý Sản phẩm
        </h1>
        <div class="header-actions">
            <a href="product_add.php">
                <i class="fas fa-plus"></i> Thêm sản phẩm
            </a>
            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success" id="alert-msg">
            <i class="fas fa-check-circle"></i>
            <span><?= $_GET['msg'] === 'deleted' ? 'Xóa sản phẩm thành công!' : 'Cập nhật thành công!' ?></span>
        </div>
    <?php endif; ?>

    <div class="filters">
        <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; width: 100%;">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Tìm kiếm theo tên, mô tả..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="filter-group">
                <select name="category">
                    <option value="">— Tất cả danh mục —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= $category_filter == $cat['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" style="flex: 0 1 auto;">
                <i class="fas fa-search"></i> Lọc
            </button>
            <a href="products_manage.php" class="btn-reset">
                <i class="fas fa-redo"></i> Reset
            </a>
        </form>
    </div>

    <div class="stats">
        <div class="stat-item">
            <i class="fas fa-boxes-stacked"></i>
            <span>Tổng sản phẩm: <strong><?= $total ?></strong></span>
        </div>
        <div class="stat-item">
            <i class="fas fa-list"></i>
            <span>Đang hiển thị: <strong><?= count($products) ?></strong></span>
        </div>
    </div>

    <div class="table-wrapper">
        <?php if (!empty($products)): ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Sản phẩm</th>
                        <th style="width: 130px;">Danh mục</th>
                        <th style="width: 110px;">Giá</th>
                        <th style="width: 80px;">Kho</th>
                        <th style="width: 100px;">Ngày tạo</th>
                        <th style="width: 160px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): 
                        $stock = $p['stock'] ?? 0;
                        $stock_class = $stock == 0 ? 'out' : ($stock < 10 ? 'low' : '');
                    ?>
                        <tr>
                            <td><strong>#<?= $p['product_id'] ?></strong></td>
                            <td>
                                <div class="product-cell">
                                    <?php if ($p['image']): ?>
                                        <div class="product-image">
                                            <img src="../uploads/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                                        </div>
                                    <?php else: ?>
                                        <div class="product-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image" style="color: white; font-size: 24px;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="product-info">
                                        <span class="product-name" title="<?= htmlspecialchars($p['name']) ?>">
                                            <?= htmlspecialchars($p['name']) ?>
                                        </span>
                                        <span class="product-sku">SKU: #<?= $p['product_id'] ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="category-badge"><?= htmlspecialchars($p['category_name'] ?? 'N/A') ?></span></td>
                            <td class="price"><?= number_format($p['price']) ?>₫</td>
                            <td><span class="stock <?= $stock_class ?>"><?= $stock ?></span></td>
                            <td><?= date('d/m/Y', strtotime($p['created_at'] ?? 'now')) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="product_edit.php?id=<?= $p['product_id'] ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    <button class="btn btn-delete" onclick="confirmDelete(<?= $p['product_id'] ?>, '<?= htmlspecialchars(addslashes($p['name']), ENT_QUOTES) ?>')">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?= $search ? '&search=' . urlencode($search) : '' ?><?= $category_filter ? '&category=' . $category_filter : '' ?>" title="Trang đầu">
                            <i class="fas fa-step-backward"></i>
                        </a>
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category_filter ? '&category=' . $category_filter : '' ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled"><i class="fas fa-step-backward"></i></span>
                        <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);

                    if ($start > 1) {
                        echo '<a href="?page=1' . ($search ? '&search=' . urlencode($search) : '') . ($category_filter ? '&category=' . $category_filter : '') . '">1</a>';
                        if ($start > 2) {
                            echo '<span class="disabled">...</span>';
                        }
                    }

                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category_filter ? '&category=' . $category_filter : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php
                    if ($end < $total_pages) {
                        if ($end < $total_pages - 1) {
                            echo '<span class="disabled">...</span>';
                        }
                        echo '<a href="?page=' . $total_pages . ($search ? '&search=' . urlencode($search) : '') . ($category_filter ? '&category=' . $category_filter : '') . '">' . $total_pages . '</a>';
                    }
                    ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category_filter ? '&category=' . $category_filter : '' ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="?page=<?= $total_pages ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category_filter ? '&category=' . $category_filter : '' ?>" title="Trang cuối">
                            <i class="fas fa-step-forward"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                        <span class="disabled"><i class="fas fa-step-forward"></i></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty">
                <i class="fas fa-inbox"></i>
                <h3>Không tìm thấy sản phẩm</h3>
                <p>Hãy thêm sản phẩm mới hoặc thử tìm kiếm với từ khóa khác.</p>
                 <a href="product_add.php">
                    <i class="fas fa-plus"></i> Thêm sản phẩm ngay
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal" id="deleteModal">
    <div class="modal-content">
        <h3>
            <i class="fas fa-exclamation-triangle"></i> Xác nhận xóa
        </h3>
        <p>Bạn có chắc chắn muốn xóa sản phẩm <strong id="productNameToDelete"></strong>? Hành động này không thể hoàn tác.</p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Hủy</button>
            <form id="deleteForm" method="POST" action="products_manage.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" id="productIdToDelete">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <button type="submit" class="btn-confirm">Xác nhận xóa</button>
            </form>
        </div>
    </div>
</div>


<script>
    // Tự động ẩn thông báo sau 3 giây
    const alertMsg = document.getElementById('alert-msg');
    if (alertMsg) {
        setTimeout(() => {
            alertMsg.style.transition = 'opacity 0.5s ease';
            alertMsg.style.opacity = '0';
            setTimeout(() => alertMsg.remove(), 500);
        }, 3000);
    }

    const deleteModal = document.getElementById('deleteModal');
    const deleteForm = document.getElementById('deleteForm');
    const productIdToDeleteInput = document.getElementById('productIdToDelete');
    const productNameToDeleteSpan = document.getElementById('productNameToDelete');

    function confirmDelete(productId, productName) {
        // Cập nhật thông tin vào modal
        productIdToDeleteInput.value = productId;
        productNameToDeleteSpan.textContent = `"${productName}"`;
        
        // Hiển thị modal
        deleteModal.classList.add('active');
    }

    function closeModal() {
        deleteModal.classList.remove('active');
    }

    // Đóng modal khi click ra ngoài
    window.onclick = function(event) {
        if (event.target == deleteModal) {
            closeModal();
        }
    }

    // Đóng modal khi nhấn phím Escape
    window.onkeydown = function(event) {
        if (event.key === "Escape") {
            closeModal();
        }
    }
</script>

</body>
</html>