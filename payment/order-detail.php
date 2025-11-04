<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/db.php';

// ✅ Kiểm tra đăng nhập
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: ../page/login.php');
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$order_id = $_GET['order_id'] ?? null;

// ✅ Validate order_id
if (!$order_id || !is_numeric($order_id)) {
    header('Location: payment-history.php');
    exit;
}

// ✅ Lấy thông tin đơn hàng
$stmt = $pdo->prepare("
    SELECT o.order_id, o.total_price, o.order_status as status, o.created_at, o.user_id,
           os.full_name, os.phone, os.payment_method, os.address, os.city, os.notes
    FROM orders o
    LEFT JOIN order_shipping os ON o.order_id = os.order_id
    WHERE o.order_id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Nếu không tìm thấy đơn hàng, redirect về lịch sử
if (!$order) {
    header('Location: payment-history.php');
    exit;
}

// ✅ Lấy chi tiết sản phẩm trong đơn hàng
$stmt = $pdo->prepare("
    SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.price_each as price,
           p.name as product_name, p.main_image as image_url, p.category_id
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Lấy lịch sử trạng thái đơn hàng (nếu bảng tồn tại)
$status_history = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM order_status_history
        WHERE order_id = ?
        ORDER BY updated_at DESC
    ");
    $stmt->execute([$order_id]);
    $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Bảng order_status_history không tồn tại, bỏ qua
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?> - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
            color: #2d3436;
            min-height: 100vh;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-link {
            color: #007bff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            font-weight: 600;
            padding: 12px 20px;
            background: white;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .back-link:hover {
            background: #f0f8ff;
            transform: translateX(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 40px 30px;
            border-radius: 16px;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 24px rgba(0, 107, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        .header-left {
            position: relative;
            z-index: 1;
        }

        .header-left h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }

        .header-left p {
            opacity: 0.9;
            font-size: 15px;
            font-weight: 500;
        }

        .status-large {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
            background: #cfe2ff;
            color: #084298;
        }

        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
            padding: 24px;
            border-bottom: 2px solid #e9ecef;
            font-weight: 700;
            font-size: 18px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header i {
            color: #007bff;
            font-size: 20px;
        }

        .card-body {
            padding: 24px;
        }

        .product-item {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 20px;
            align-items: center;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 18px;
            transition: all 0.3s ease;
            background: white;
        }

        .product-item:last-child {
            margin-bottom: 0;
        }

        .product-item:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
            transform: translateY(-2px);
        }

        .product-image {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-item:hover .product-image img {
            transform: scale(1.05);
        }

        .product-image i {
            font-size: 40px;
            color: #dee2e6;
        }

        .product-info h4 {
            color: #2d3436;
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 16px;
            line-height: 1.3;
        }

        .product-info p {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .product-info p i {
            color: #007bff;
            font-size: 12px;
        }

        .product-price {
            text-align: right;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .product-price .quantity {
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
            background: #f0f2f5;
            padding: 8px 12px;
            border-radius: 8px;
        }

        .product-price .subtotal {
            color: #007bff;
            font-weight: 800;
            font-size: 18px;
            letter-spacing: -0.5px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 20px;
            padding: 18px 0;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row:hover {
            background: #f8f9fa;
            margin: 0 -6px;
            padding: 18px 6px;
            border-radius: 6px;
        }

        .info-label {
            font-weight: 700;
            color: #495057;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-label i {
            color: #007bff;
            font-size: 14px;
        }

        .info-value {
            color: #2d3436;
            font-size: 15px;
            font-weight: 500;
        }

        .summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
            border-radius: 12px;
            padding: 24px;
            margin-top: 20px;
            border: 2px solid #e9ecef;
        }

        .summary-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            padding: 12px 0;
            font-size: 15px;
            font-weight: 600;
            color: #495057;
        }

        .summary-row.total {
            padding: 18px 0;
            border-top: 2px solid #dee2e6;
            border-bottom: 2px solid #dee2e6;
            font-size: 20px;
            font-weight: 800;
            color: #007bff;
            margin: 12px 0;
            letter-spacing: -0.5px;
        }

        .timeline {
            position: relative;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 24px;
            position: relative;
            animation: slideInLeft 0.5s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 50px;
            width: 2px;
            height: calc(100% + 24px);
            background: linear-gradient(180deg, #007bff 0%, #e9ecef 100%);
        }

        .timeline-dot {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
            position: relative;
            z-index: 2;
            box-shadow: 0 4px 12px rgba(0, 107, 255, 0.3);
            animation: popIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes popIn {
            0% {
                transform: scale(0.5);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .timeline-content {
            margin-left: 20px;
            flex: 1;
            background: #f8f9fa;
            padding: 16px;
            border-radius: 10px;
            border-left: 3px solid #007bff;
            transition: all 0.3s ease;
        }

        .timeline-item:hover .timeline-content {
            background: white;
            border-left-color: #0056b3;
            box-shadow: 0 4px 12px rgba(0, 107, 255, 0.1);
        }

        .timeline-time {
            color: #6c757d;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .timeline-status {
            font-weight: 700;
            color: #007bff;
            font-size: 16px;
            margin-bottom: 6px;
            text-transform: capitalize;
        }

        .timeline-note {
            color: #495057;
            font-size: 14px;
            line-height: 1.4;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            padding: 14px 28px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
        }

        .empty-message {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-message i {
            font-size: 48px;
            color: #dee2e6;
            display: block;
            margin-bottom: 16px;
        }

        .empty-message p {
            font-size: 16px;
            font-weight: 500;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .product-item {
                grid-template-columns: 100px 1fr auto;
                gap: 15px;
            }

            .product-image {
                width: 100px;
                height: 100px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }

            .header {
                padding: 24px 16px;
            }

            .header-left h1 {
                font-size: 24px;
            }

            .card-header {
                padding: 16px;
                font-size: 16px;
            }

            .card-body {
                padding: 16px;
            }

            .product-item {
                grid-template-columns: 80px 1fr;
                gap: 12px;
                padding: 14px;
            }

            .product-image {
                width: 80px;
                height: 80px;
            }

            .product-price {
                grid-column: 1 / -1;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .info-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .info-label {
                font-size: 13px;
            }

            .info-value {
                font-size: 14px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .summary-row {
                font-size: 14px;
            }

            .summary-row.total {
                font-size: 18px;
            }
        }

        @media (max-width: 480px) {
            .header-left h1 {
                font-size: 20px;
            }

            .header-left h1 i {
                font-size: 20px;
            }

            .product-image {
                width: 70px;
                height: 70px;
            }

            .product-info h4 {
                font-size: 14px;
            }

            .product-info p {
                font-size: 12px;
            }

            .product-price .subtotal {
                font-size: 16px;
            }

            .btn {
                padding: 12px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="payment-history.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Quay lại lịch sử đơn hàng
    </a>

    <div class="header">
        <div class="header-left">
            <h1><i class="fa-solid fa-receipt"></i> Đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></h1>
            <p><i class="fa-solid fa-calendar-days"></i> Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
        </div>
        <div class="status-large status-<?= $order['status'] ?>">
            <i class="fa-solid fa-badge-check"></i> <?= strtoupper($order['status']) ?>
        </div>
    </div>

    <div class="content-grid">
        <!-- ✅ Chi tiết sản phẩm -->
        <div>
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-box"></i> Sản phẩm trong đơn hàng
                </div>
                <div class="card-body">
                    <?php if (empty($items)): ?>
                        <div class="empty-message">
                            <i class="fa-solid fa-inbox"></i>
                            <p>Không có sản phẩm</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <div class="product-item">
                                <div class="product-image">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($item['image_url']) ?>" 
                                             alt="<?= htmlspecialchars($item['product_name']) ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-image"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h4><?= htmlspecialchars($item['product_name'] ?? 'Sản phẩm') ?></h4>
                                    <p><i class="fa-solid fa-barcode"></i> Mã: #<?= $item['product_id'] ?></p>
                                    <p><i class="fa-solid fa-tag"></i> Đơn giá: <strong><?= formatPrice($item['price']) ?></strong></p>
                                </div>
                                <div class="product-price">
                                    <div class="quantity"><i class="fa-solid fa-box"></i> SL: <?= $item['quantity'] ?></div>
                                    <div class="subtotal"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="summary">
                        <div class="summary-row">
                            <span><i class="fa-solid fa-calculator"></i> Tổng sản phẩm:</span>
                            <span><?= array_sum(array_column($items, 'quantity')) ?> cái</span>
                        </div>
                        <div class="summary-row total">
                            <span><i class="fa-solid fa-coins"></i> Tổng tiền:</span>
                            <span><?= formatPrice($order['total_price']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ Thông tin đơn hàng và giao hàng -->
        <div>
            <!-- Thông tin giao hàng -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <i class="fa-solid fa-truck"></i> Thông tin giao hàng
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <div class="info-label"><i class="fa-solid fa-user"></i> Họ tên:</div>
                        <div class="info-value"><?= htmlspecialchars($order['full_name'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fa-solid fa-phone"></i> Điện thoại:</div>
                        <div class="info-value"><?= htmlspecialchars($order['phone'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fa-solid fa-map-marker"></i> Địa chỉ:</div>
                        <div class="info-value"><?= htmlspecialchars($order['address'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fa-solid fa-city"></i> Thành phố:</div>
                        <div class="info-value"><?= htmlspecialchars($order['city'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fa-solid fa-credit-card"></i> Phương thức:</div>
                        <div class="info-value"><?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?></div>
                    </div>
                    <?php if (!empty($order['notes'])): ?>
                        <div class="info-row">
                            <div class="info-label"><i class="fa-solid fa-note-sticky"></i> Ghi chú:</div>
                            <div class="info-value"><?= htmlspecialchars($order['notes']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lịch sử trạng thái -->
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử trạng thái
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php if (empty($status_history)): ?>
                            <div class="empty-message">
                                <i class="fa-solid fa-hourglass-end"></i>
                                <p>Chưa có cập nhật</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($status_history as $history): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-time">
                                            <?= date('d/m/Y H:i', strtotime($history['updated_at'])) ?>
                                        </div>
                                        <div class="timeline-status"><?= ucfirst($history['status']) ?></div>
                                        <?php if (!empty($history['note'])): ?>
                                            <div class="timeline-note"><?= htmlspecialchars($history['note']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="action-buttons">
                <a href="payment-history.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại
                </a>
                <?php if ($order['status'] === 'pending'): ?>
                    <a href="payment-detail.php?order_id=<?= $order_id ?>" class="btn btn-primary">
                        <i class="fa-solid fa-credit-card"></i> Thanh toán ngay
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>