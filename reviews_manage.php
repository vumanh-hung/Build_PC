<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__FILE__) . '/../db.php';

// ✅ Kiểm tra admin
if (!isset($_SESSION['user']['user_id']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../page/login.php');
    exit;
}

// ✅ Xử lý hành động
$action_message = '';
$action_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $review_id = intval($_POST['review_id'] ?? 0);
    
    if ($action === 'approve' && $review_id) {
        try {
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE review_id = ?");
            $stmt->execute([$review_id]);
            $action_message = 'Duyệt đánh giá thành công';
            $action_type = 'success';
        } catch (Exception $e) {
            $action_message = 'Có lỗi xảy ra: ' . $e->getMessage();
            $action_type = 'error';
        }
    } elseif ($action === 'reject' && $review_id) {
        try {
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE review_id = ?");
            $stmt->execute([$review_id]);
            $action_message = 'Từ chối đánh giá thành công';
            $action_type = 'success';
        } catch (Exception $e) {
            $action_message = 'Có lỗi xảy ra';
            $action_type = 'error';
        }
    } elseif ($action === 'delete' && $review_id) {
        try {
            $pdo->beginTransaction();
            // Xóa record
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
            $stmt->execute([$review_id]);
            $pdo->commit();
            $action_message = 'Xóa đánh giá thành công';
            $action_type = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $action_message = 'Có lỗi xảy ra';
            $action_type = 'error';
        }
    }
}

// ✅ Lấy dữ liệu lọc
$filter_status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$page = intval($_GET['page'] ?? 1);
$per_page = 20;

// ✅ Xây dựng query
$where = [];
$params = [];

if ($filter_status !== 'all') {
    $where[] = "r.status = ?";
    $params[] = $filter_status;
}

if (!empty($search)) {
    $where[] = "(r.title LIKE ? OR r.comment LIKE ? OR u.full_name LIKE ? OR p.name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$order_by = match($sort) {
    'oldest' => 'r.created_at ASC',
    'rating_high' => 'r.rating DESC',
    'rating_low' => 'r.rating ASC',
    default => 'r.created_at DESC'
};

// Đếm tổng
try {
    $count_query = "SELECT COUNT(*) as total FROM reviews r LEFT JOIN users u ON r.user_id = u.user_id LEFT JOIN products p ON r.product_id = p.product_id $where_clause";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'] ?? 0;
} catch (Exception $e) {
    $total = 0;
}

$total_pages = $total > 0 ? ceil($total / $per_page) : 1;

// Lấy dữ liệu
$reviews = [];
try {
    $offset = ($page - 1) * $per_page;
    $query = "
        SELECT r.*, u.full_name, p.name as product_name
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.user_id
        LEFT JOIN products p ON r.product_id = p.product_id
        $where_clause
        ORDER BY $order_by
        LIMIT ? OFFSET ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
} catch (Exception $e) {
    $reviews = [];
}

// Thống kê
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

try {
    $stat_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM reviews";
    $stmt = $pdo->prepare($stat_query);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?? $stats;
} catch (Exception $e) {
    // Bảng reviews không tồn tại hoặc lỗi query
}

// Hàm hiển thị sao
function renderStars($rating, $size = 'lg') {
    $rating = (int)$rating;
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '⭐' : '☆';
    }
    return $stars;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đánh giá - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }

        .back-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            transform: translateX(-4px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 4px solid #007bff;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }

        .stat-card.pending { border-top-color: #ffc107; }
        .stat-card.approved { border-top-color: #28a745; }
        .stat-card.rejected { border-top-color: #dc3545; }

        .stat-number {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .stat-number.pending { color: #ffc107; }
        .stat-number.approved { color: #28a745; }
        .stat-number.rejected { color: #dc3545; }

        .stat-label {
            color: #6c757d;
            font-size: 13px;
        }

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .filters-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 600;
            font-size: 13px;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .table-section {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            font-size: 13px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .actions {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.3s ease;
            color: white;
            font-weight: 600;
        }

        .btn-view {
            background: #17a2b8;
        }

        .btn-view:hover {
            background: #138496;
        }

        .btn-approve {
            background: #28a745;
        }

        .btn-approve:hover {
            background: #218838;
        }

        .btn-reject {
            background: #ffc107;
            color: #333;
        }

        .btn-reject:hover {
            background: #e0a800;
        }

        .btn-delete {
            background: #dc3545;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 20px;
            flex-wrap: wrap;
        }

        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
            transition: all 0.3s ease;
        }

        .pagination a.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination a:hover {
            background: #f8f9fa;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            width: 100%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 800;
            color: #333;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-body {
            line-height: 1.8;
        }

        .modal-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .modal-section:last-child {
            border-bottom: none;
        }

        .modal-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .modal-value {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 1024px) {
            .filters-row {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .filters-row {
                grid-template-columns: 1fr;
            }

            .btn-group {
                width: 100%;
            }

            .btn-group .btn {
                flex: 1;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }

            .modal-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>⭐ Quản lý Đánh giá</h1>
            <a href="../index.php" class="back-link">← Quay lại</a>
        </div>

        <!-- Alert -->
        <?php if (!empty($action_message)): ?>
            <div class="alert alert-<?= $action_type ?>">
                <?= $action_type === 'success' ? '✓' : '⚠️' ?> 
                <?= htmlspecialchars($action_message) ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= (int)($stats['total'] ?? 0) ?></div>
                <div class="stat-label">Tổng đánh giá</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number pending"><?= (int)($stats['pending'] ?? 0) ?></div>
                <div class="stat-label">Chờ duyệt</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-number approved"><?= (int)($stats['approved'] ?? 0) ?></div>
                <div class="stat-label">Đã duyệt</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-number rejected"><?= (int)($stats['rejected'] ?? 0) ?></div>
                <div class="stat-label">Từ chối</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" class="filters-row">
                <div class="filter-group">
                    <label>Tìm kiếm</label>
                    <input type="text" name="search" placeholder="Tiêu đề, sản phẩm, tên..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-group">
                    <label>Trạng thái</label>
                    <select name="status">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Tất cả</option>
                        <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                        <option value="approved" <?= $filter_status === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                        <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Từ chối</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Sắp xếp</label>
                    <select name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
                        <option value="rating_high" <?= $sort === 'rating_high' ? 'selected' : '' ?>>Đánh giá cao</option>
                        <option value="rating_low" <?= $sort === 'rating_low' ? 'selected' : '' ?>>Đánh giá thấp</option>
                    </select>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">🔍 Tìm</button>
                    <a href="reviews_manage.php" class="btn btn-secondary">↻ Xóa lọc</a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-section">
            <?php if (empty($reviews)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Không có đánh giá nào</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Khách hàng</th>
                                <th>Đánh giá</th>
                                <th>Tiêu đề</th>
                                <th>Trạng thái</th>
                                <th style="text-align: center;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($review['product_name'] ?? 'N/A', 0, 30)) ?></td>
                                    <td><?= htmlspecialchars($review['full_name'] ?? 'Khách vãng lai') ?></td>
                                    <td><?= renderStars($review['rating'] ?? 5) ?></td>
                                    <td><?= htmlspecialchars(substr($review['title'] ?? '', 0, 40)) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $review['status'] ?? 'pending' ?>">
                                            <?= match($review['status'] ?? 'pending') {
                                                'pending' => 'Chờ duyệt',
                                                'approved' => 'Đã duyệt',
                                                'rejected' => 'Từ chối',
                                                default => $review['status']
                                            } ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="btn-icon btn-view" 
                                                    onclick="viewReview(<?= htmlspecialchars(json_encode($review)) ?>)"
                                                    title="Xem">👁️</button>
                                            <?php if (($review['status'] ?? 'pending') !== 'approved'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="review_id" value="<?= (int)$review['review_id'] ?>">
                                                    <button type="submit" class="btn-icon btn-approve" title="Duyệt">✓</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (($review['status'] ?? 'pending') !== 'rejected'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="review_id" value="<?= (int)$review['review_id'] ?>">
                                                    <button type="submit" class="btn-icon btn-reject" title="Từ chối">✕</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Xác nhận xóa đánh giá này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="review_id" value="<?= (int)$review['review_id'] ?>">
                                                <button type="submit" class="btn-icon btn-delete" title="Xóa">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                            <a href="?status=<?= $filter_status ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&page=<?= $i ?>" 
                               class="<?= $page === $i ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal View -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle"></h2>
                <button class="modal-close" onclick="closeReviewModal()">✕</button>
            </div>
            <div class="modal-body" id="modalContent"></div>
        </div>
    </div>

    <script>
        function viewReview(review) {
            document.getElementById('modalTitle').textContent = review.full_name || 'Khách hàng';
            
            const stars = '⭐'.repeat(review.rating || 5) + '☆'.repeat(5 - (review.rating || 5));
            
            document.getElementById('modalContent').innerHTML = `
                <div class="modal-section">
                    <div class="modal-label">Sản phẩm</div>
                    <div class="modal-value">${escapeHtml(review.product_name || 'N/A')}</div>
                </div>
                <div class="modal-section">
                    <div class="modal-label">Đánh giá</div>
                    <div class="modal-value">${stars}</div>
                </div>
                <div class="modal-section">
                    <div class="modal-label">Tiêu đề</div>
                    <div class="modal-value">${escapeHtml(review.title || 'Không có tiêu đề')}</div>
                </div>
                <div class="modal-section">
                    <div class="modal-label">Nội dung</div>
                    <div class="modal-value">${escapeHtml(review.comment || 'Không có nội dung')}</div>
                </div>
            `;
            document.getElementById('reviewModal').style.display = 'flex';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.getElementById('reviewModal').addEventListener('click', function(e) {
            if (e.target === this) closeReviewModal();
        });
    </script>
</body>
</html>