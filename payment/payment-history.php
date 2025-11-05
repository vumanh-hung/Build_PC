<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/db.php';
require_once dirname(dirname(__FILE__)) . '/functions.php';

// ✅ Kiểm tra đăng nhập
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: ../page/login.php');
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// ✅ Lấy danh sách tất cả đơn hàng của user
$stmt = $pdo->prepare("
    SELECT o.order_id, o.total_price, o.order_status as status, o.created_at,
           os.full_name as fullname, os.phone, os.payment_method, os.address, os.city,
           COUNT(oi.order_item_id) as item_count,
           SUM(oi.quantity) as total_qty
    FROM orders o
    LEFT JOIN order_shipping os ON o.order_id = os.order_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Tính toán thống kê
$total_paid = 0;
$total_pending = 0;
$total_shipped = 0;
$count_paid = 0;
$count_pending = 0;
$count_shipped = 0;

foreach ($payments as $payment) {
    if (in_array($payment['status'], ['paid', 'completed'])) {
        $total_paid += $payment['total_price'];
        $count_paid++;
    } elseif ($payment['status'] === 'shipping') {
        $total_shipped += $payment['total_price'];
        $count_shipped++;
    } elseif ($payment['status'] === 'pending') {
        $total_pending += $payment['total_price'];
        $count_pending++;
    }
}

// ✅ Xử lý filter và search
$filter_status = $_GET['status'] ?? 'all';
$search_keyword = trim($_GET['search'] ?? '');
$sort_by = $_GET['sort'] ?? 'newest';

$filtered_payments = $payments;

// Filter theo status
if ($filter_status !== 'all') {
    $filtered_payments = array_filter($filtered_payments, function ($order) use ($filter_status) {
        return $order['status'] === $filter_status;
    });
}

// Search theo mã đơn, họ tên, số điện thoại
if (!empty($search_keyword)) {
    $filtered_payments = array_filter($filtered_payments, function ($order) use ($search_keyword) {
        $order_id_str = str_pad($order['order_id'], 6, '0', STR_PAD_LEFT);
        return stripos($order_id_str, $search_keyword) !== false ||
               stripos($order['fullname'] ?? '', $search_keyword) !== false ||
               stripos($order['phone'] ?? '', $search_keyword) !== false;
    });
}

// Sort
switch ($sort_by) {
    case 'oldest':
        usort($filtered_payments, function ($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        break;
    case 'price_high':
        usort($filtered_payments, function ($a, $b) {
            return $b['total_price'] - $a['total_price'];
        });
        break;
    case 'price_low':
        usort($filtered_payments, function ($a, $b) {
            return $a['total_price'] - $b['total_price'];
        });
        break;
    case 'newest':
    default:
        // Mặc định đã sắp xếp newest trong query
        break;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đơn hàng - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .back-link {
            color: #007bff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            padding: 8px 16px;
            background: white;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .back-link:hover {
            background: #f0f8ff;
            transform: translateX(-4px);
        }

        .header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .header p {
            opacity: 0.95;
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
            border-top: 4px solid #6c757d;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-card h3 {
            color: #6c757d;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .stat-card .value {
            color: #007bff;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .stat-card .subtext {
            color: #6c757d;
            font-size: 13px;
        }

        .stat-card.pending {
            border-top-color: #ffc107;
        }

        .stat-card.pending .value {
            color: #ffc107;
        }

        .stat-card.shipping {
            border-top-color: #17a2b8;
        }

        .stat-card.shipping .value {
            color: #17a2b8;
        }

        .stat-card.paid {
            border-top-color: #28a745;
        }

        .stat-card.paid .value {
            color: #28a745;
        }

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 13px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .btn-filter {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .orders-section h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .orders-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1.2fr 1fr 1fr 0.8fr;
            gap: 15px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
            border-bottom: 2px solid #e9ecef;
            font-weight: 700;
            color: #333;
            font-size: 13px;
            text-transform: uppercase;
        }

        .table-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1.2fr 1fr 1fr 0.8fr;
            gap: 15px;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .table-row:hover {
            background: #f8f9fa;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .order-id {
            font-weight: 700;
            color: #007bff;
            font-size: 14px;
        }

        .order-date {
            color: #6c757d;
            font-size: 13px;
        }

        .order-customer {
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .order-amount {
            color: #333;
            font-weight: 700;
            font-size: 14px;
        }

        .order-qty {
            color: #6c757d;
            font-size: 13px;
            background: #f0f2f5;
            padding: 6px 12px;
            border-radius: 20px;
            display: inline-block;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            width: 100%;
            justify-content: center;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-shipping {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 6px;
            justify-content: center;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-view {
            background: #007bff;
            color: white;
        }

        .btn-view:hover {
            background: #0056b3;
        }

        .btn-pay {
            background: #28a745;
            color: white;
        }

        .btn-pay:hover {
            background: #218838;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 48px;
            color: #dee2e6;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .btn-shop {
            display: inline-block;
            padding: 10px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .btn-shop:hover {
            background: #0056b3;
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .no-results i {
            font-size: 36px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        @media (max-width: 1024px) {
            .table-header,
            .table-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 24px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="../index.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Quay lại trang chủ
    </a>

    <div class="header">
        <h1><i class="fa-solid fa-receipt"></i> Lịch sử đơn hàng</h1>
        <p><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Khách hàng') ?></p>
    </div>

    <!-- ✅ Thống kê -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Tổng đơn hàng</h3>
            <div class="value"><?= count($payments) ?></div>
            <div class="subtext">Tất cả đơn hàng</div>
        </div>

        <div class="stat-card pending">
            <h3>Chờ thanh toán</h3>
            <div class="value"><?= $count_pending ?></div>
            <div class="subtext"><?= formatPriceVND($total_pending) ?></div>
        </div>

        <div class="stat-card shipping">
            <h3>Đang giao</h3>
            <div class="value"><?= $count_shipped ?></div>
            <div class="subtext"><?= formatPriceVND($total_shipped) ?></div>
        </div>

        <div class="stat-card paid">
            <h3>Đã hoàn thành</h3>
            <div class="value"><?= $count_paid ?></div>
            <div class="subtext"><?= formatPriceVND($total_paid) ?></div>
        </div>
    </div>

    <!-- ✅ Bộ lọc và tìm kiếm -->
    <div class="filters-section">
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="search"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</label>
                    <input type="text" id="search" name="search" 
                           placeholder="Mã đơn, họ tên, số điện thoại..."
                           value="<?= htmlspecialchars($search_keyword) ?>">
                </div>

                <div class="filter-group">
                    <label for="status"><i class="fa-solid fa-filter"></i> Trạng thái</label>
                    <select id="status" name="status">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Tất cả</option>
                        <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                        <option value="shipping" <?= $filter_status === 'shipping' ? 'selected' : '' ?>>Đang giao</option>
                        <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                        <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort"><i class="fa-solid fa-arrow-down-up"></i> Sắp xếp</label>
                    <select id="sort" name="sort">
                        <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
                        <option value="price_high" <?= $sort_by === 'price_high' ? 'selected' : '' ?>>Giá cao → thấp</option>
                        <option value="price_low" <?= $sort_by === 'price_low' ? 'selected' : '' ?>>Giá thấp → cao</option>
                    </select>
                </div>

                <div class="btn-filter">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-search"></i> Tìm kiếm
                    </button>
                    <a href="payment-history.php" class="btn btn-secondary">
                        <i class="fa-solid fa-redo"></i> Xóa lọc
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- ✅ Danh sách đơn hàng -->
    <div class="orders-section">
        <h2><i class="fa-solid fa-list"></i> Danh sách đơn hàng</h2>

        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <h3>Chưa có đơn hàng</h3>
                <p>Bạn chưa tạo đơn hàng nào. Hãy bắt đầu mua sắm ngay!</p>
                <a href="../index.php" class="btn-shop">
                    <i class="fa-solid fa-shopping-bag"></i> Mua sắm ngay
                </a>
            </div>
        <?php elseif (empty($filtered_payments)): ?>
            <div class="no-results">
                <i class="fa-solid fa-search"></i>
                <p>Không tìm thấy đơn hàng phù hợp với bộ lọc của bạn</p>
            </div>
        <?php else: ?>
            <div class="orders-table">
                <div class="table-header">
                    <div>Mã đơn hàng</div>
                    <div>Ngày đặt</div>
                    <div>Khách hàng</div>
                    <div>Tổng tiền</div>
                    <div>Số lượng</div>
                    <div>Trạng thái</div>
                </div>

                <?php foreach ($filtered_payments as $order): ?>
                    <div class="table-row" onclick="viewOrderDetail(<?= $order['order_id'] ?>)">
                        <div>
                            <div class="order-id">#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></div>
                        </div>

                        <div class="order-date">
                            <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                        </div>

                        <div>
                            <div class="order-customer"><?= htmlspecialchars($order['fullname'] ?? 'N/A') ?></div>
                            <div style="font-size: 12px; color: #6c757d; margin-top: 4px;">
                                <?= htmlspecialchars($order['phone'] ?? 'N/A') ?>
                            </div>
                        </div>

                        <div class="order-amount">
                            <?= formatPriceVND($order['total_price']) ?>
                        </div>

                        <div>
                            <span class="order-qty">
                                <i class="fa-solid fa-box"></i>
                                <?= $order['item_count'] ?> sản phẩm
                            </span>
                        </div>

                        <div>
                            <div class="status-badge status-<?= $order['status'] ?>">
                                <i class="fa-solid fa-badge-check"></i>
                                <?= ucfirst($order['status']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function viewOrderDetail(orderId) {
    // Redirect sang trang chi tiết đơn hàng
    window.location.href = 'order-detail.php?order_id=' + orderId;
}
</script>

</body>
</html>