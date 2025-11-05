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
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// ✅ Validate order_id
if (!$order_id) {
    header('Location: payment-history.php');
    exit;
}

// ✅ Lấy thông tin đơn hàng
$order = getOrderById($order_id, $user_id);

if (!$order) {
    header('Location: payment-history.php');
    exit;
}

// ✅ Lấy chi tiết sản phẩm
$items = getOrderItems($order_id);
if (!is_array($items)) {
    $items = [];
}

// ✅ Lấy thông tin trạng thái
$status = getOrderStatus($order['order_status'] ?? 'pending');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận thanh toán - BuildPC.vn</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
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
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .success-icon {
            font-size: 80px;
            color: white;
            margin-bottom: 20px;
            animation: bounce 0.8s infinite;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        .header h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 16px;
            font-weight: 500;
            opacity: 0.95;
            margin-bottom: 0;
        }

        .content {
            padding: 40px;
        }

        .order-info {
            background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
            border: 2px solid #28a745;
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 28px;
        }

        .order-number {
            font-size: 28px;
            font-weight: 800;
            color: #1e7e34;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.5px;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .detail-item {
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(40, 167, 69, 0.2);
        }

        .detail-label {
            font-size: 12px;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .detail-label i {
            color: #28a745;
        }

        .detail-value {
            font-size: 16px;
            font-weight: 700;
            color: #2d3436;
        }

        .message-box {
            background: white;
            border-left: 4px solid #28a745;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 28px;
        }

        .message-box h3 {
            color: #1e7e34;
            font-size: 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message-box p {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .message-box p:last-child {
            margin-bottom: 0;
        }

        .products-preview {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
        }

        .products-preview h3 {
            font-size: 14px;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-name {
            font-weight: 600;
            color: #2d3436;
            flex: 1;
        }

        .product-qty {
            color: #6c757d;
            margin: 0 16px;
            min-width: 50px;
            text-align: right;
        }

        .product-price {
            font-weight: 700;
            color: #28a745;
            min-width: 100px;
            text-align: right;
        }

        .summary-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            font-size: 15px;
            font-weight: 600;
            color: #495057;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 800;
            color: #28a745;
            padding: 16px 0;
            border-top: 2px solid #dee2e6;
            border-bottom: 2px solid #dee2e6;
            margin: 12px 0;
            letter-spacing: -0.5px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .btn {
            padding: 16px 28px;
            border-radius: 12px;
            border: none;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
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
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            box-shadow: 0 6px 16px rgba(40, 167, 69, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(40, 167, 69, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            box-shadow: 0 6px 16px rgba(108, 117, 125, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(108, 117, 125, 0.4);
        }

        .timeline {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
        }

        .timeline h3 {
            font-size: 14px;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timeline-step {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
            position: relative;
        }

        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 15px;
            top: 50px;
            width: 2px;
            height: 32px;
            background: #e9ecef;
        }

        .timeline-dot {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            flex-shrink: 0;
            position: relative;
            z-index: 2;
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.2);
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: 700;
            color: #2d3436;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .timeline-text {
            color: #6c757d;
            font-size: 13px;
        }

        footer {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            padding: 20px 40px;
            text-align: center;
            color: #6c757d;
            font-size: 13px;
            font-weight: 500;
        }

        footer a {
            color: #6c757d;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        footer a:hover {
            color: #28a745;
        }

        @media (max-width: 768px) {
            .container {
                border-radius: 16px;
            }

            .header {
                padding: 40px 24px;
            }

            .header h1 {
                font-size: 28px;
            }

            .success-icon {
                font-size: 64px;
            }

            .content {
                padding: 24px;
            }

            .order-info {
                padding: 20px;
            }

            .order-number {
                font-size: 24px;
            }

            .order-details {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }

            .btn {
                padding: 14px 20px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 30px 16px;
            }

            .content {
                padding: 16px;
            }

            .header h1 {
                font-size: 24px;
            }

            .success-icon {
                font-size: 56px;
            }

            .order-info {
                padding: 16px;
            }

            .order-number {
                font-size: 20px;
            }

            .summary-row.total {
                font-size: 18px;
            }

            footer {
                padding: 16px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- ✅ Header -->
    <div class="header">
        <div class="header-content">
            <div class="success-icon">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            <h1>Thanh toán thành công!</h1>
            <p>Cảm ơn bạn đã mua hàng tại BuildPC.vn</p>
        </div>
    </div>

    <!-- ✅ Content -->
    <div class="content">
        <!-- Thông tin đơn hàng -->
        <div class="order-info">
            <div class="order-number">
                <i class="fa-solid fa-receipt"></i>
                Đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?>
            </div>
            <div class="order-details">
                <div class="detail-item">
                    <div class="detail-label"><i class="fa-solid fa-calendar"></i> Ngày đặt</div>
                    <div class="detail-value"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fa-solid fa-user"></i> Người nhận</div>
                    <div class="detail-value"><?= escape($order['full_name'] ?? 'N/A') ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fa-solid fa-phone"></i> Điện thoại</div>
                    <div class="detail-value"><?= escape($order['phone'] ?? 'N/A') ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="fa-solid fa-map-marker"></i> Thành phố</div>
                    <div class="detail-value"><?= escape($order['city'] ?? 'N/A') ?></div>
                </div>
            </div>
        </div>

        <!-- Thông báo -->
        <div class="message-box">
            <h3>
                <i class="fa-solid fa-info-circle"></i>
                Chúng tôi sẽ sớm xác nhận và gửi hàng cho bạn
            </h3>
            <p>✓ Kiểm tra email để nhận thông tin chi tiết đơn hàng</p>
            <p>✓ Bạn có thể theo dõi trạng thái đơn hàng tại trang lịch sử thanh toán</p>
            <p>✓ Nếu có vấn đề, vui lòng liên hệ hotline: <strong>1800.xxxx</strong></p>
        </div>

        <!-- Danh sách sản phẩm -->
        <?php if (!empty($items)): ?>
        <div class="products-preview">
            <h3>
                <i class="fa-solid fa-box"></i>
                Danh sách sản phẩm (<?= count($items) ?> mục)
            </h3>
            <?php foreach ($items as $item): ?>
            <div class="product-item">
                <div class="product-name">
                    <?= escape($item['product_name'] ?? 'Sản phẩm') ?>
                </div>
                <div class="product-qty">
                    x<?= $item['quantity'] ?>
                </div>
                <div class="product-price">
                    <?= formatPriceVND($item['price'] * $item['quantity']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Tóm tắt -->
        <div class="summary-box">
            <div class="summary-row">
                <span><i class="fa-solid fa-box"></i> Tổng sản phẩm:</span>
                <span><?= array_sum(array_column($items, 'quantity')) ?> cái</span>
            </div>
            <div class="summary-row total">
                <span><i class="fa-solid fa-coins"></i> Tổng tiền:</span>
                <span><?= formatPriceVND($order['total_price']) ?></span>
            </div>
        </div>

        <!-- Quy trình xử lý -->
        <div class="timeline">
            <h3>
                <i class="fa-solid fa-clock"></i>
                Quy trình xử lý đơn hàng
            </h3>
            <div class="timeline-step">
                <div class="timeline-dot">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-title">Thanh toán thành công</div>
                    <div class="timeline-text">Đơn hàng của bạn đã được xác nhận</div>
                </div>
            </div>
            <div class="timeline-step">
                <div class="timeline-dot">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-title">Đang chuẩn bị hàng</div>
                    <div class="timeline-text">Chúng tôi sẽ chuẩn bị và kiểm tra hàng</div>
                </div>
            </div>
            <div class="timeline-step">
                <div class="timeline-dot">
                    <i class="fa-solid fa-truck"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-title">Đang giao hàng</div>
                    <div class="timeline-text">Hàng của bạn đang trên đường đến</div>
                </div>
            </div>
            <div class="timeline-step">
                <div class="timeline-dot">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-title">Đã giao thành công</div>
                    <div class="timeline-text">Cảm ơn bạn đã mua hàng</div>
                </div>
            </div>
        </div>

        <!-- Nút hành động -->
        <div class="action-buttons">
            <a href="payment-history.php" class="btn btn-primary">
                <i class="fa-solid fa-history"></i> Lịch sử đơn hàng
            </a>
            <a href="../index.php" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Tiếp tục mua hàng
            </a>
        </div>
    </div>

    <!-- ✅ Footer -->
    <footer>
        <p>
            <i class="fa-solid fa-copyright"></i> <?= date('Y') ?> BuildPC.vn — 
            Máy tính & Linh kiện chính hãng | 
            <a href="javascript:void(0)">
                Liên hệ hỗ trợ
            </a>
        </p>
    </footer>
</div>

</body>
</html>