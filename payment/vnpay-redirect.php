<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/db.php';
require_once dirname(dirname(__FILE__)) . '/functions.php';

requireLogin();

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user']['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: payment-history.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán VNPay - BuildPC.vn</title>
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
            background: linear-gradient(135deg, #0066b2 0%, #00a0e9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon {
            font-size: 80px;
            color: #00a0e9;
            margin-bottom: 20px;
            animation: bounce 0.6s ease-in-out;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            font-size: 28px;
            font-weight: 800;
            color: #333;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        p {
            color: #6c757d;
            font-size: 15px;
            margin-bottom: 8px;
        }

        .order-id {
            color: #00a0e9;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .amount {
            font-size: 32px;
            font-weight: 800;
            color: #00a0e9;
            margin: 30px 0;
            letter-spacing: -0.5px;
        }

        .status-message {
            background: linear-gradient(135deg, #f0f0f0 0%, #f8f8f8 100%);
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            border-left: 4px solid #00a0e9;
        }

        .status-message p {
            margin: 0;
        }

        .status-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 6px;
            font-size: 16px;
        }

        .status-text {
            color: #6c757d;
            font-size: 14px;
        }

        .btn-container {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            flex-direction: column;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0066b2 0%, #00a0e9 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 160, 233, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 160, 233, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #00a0e9;
        }

        .btn-secondary:hover {
            background: #e8e8e8;
            transform: translateY(-2px);
        }

        .info-box {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #0d47a1;
            font-size: 13px;
            line-height: 1.6;
        }

        .info-box strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 24px;
            }

            .icon {
                font-size: 60px;
            }

            .amount {
                font-size: 28px;
            }

            .btn-container {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 20px;
            }

            .icon {
                font-size: 50px;
            }

            .amount {
                font-size: 24px;
            }

            p {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="icon">
        <i class="fa-solid fa-wallet"></i>
    </div>
    
    <h1>Thanh toán VNPay</h1>
    
    <div class="order-id">
        <i class="fa-solid fa-receipt"></i> Đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?>
    </div>
    
    <div class="amount">
        <?= formatPriceVND($order['total_price']) ?>
    </div>

    <div class="status-message">
        <div class="status-title">
            <i class="fa-solid fa-info-circle"></i> Tính năng đang được phát triển
        </div>
        <div class="status-text">
            Vui lòng quay lại để chọn phương thức thanh toán khác
        </div>
    </div>

    <div class="info-box">
        <strong><i class="fa-solid fa-lightbulb"></i> Ghi chú:</strong>
        Phương thức thanh toán VNPay sẽ sớm được kích hoạt. Hiện tại, vui lòng sử dụng các phương thức khác như chuyển khoản ngân hàng hoặc thẻ tín dụng.
    </div>

    <div class="btn-container">
        <a href="payment-detail.php?order_id=<?= $order_id ?>" class="btn btn-primary">
            <i class="fa-solid fa-arrow-left"></i> Quay lại chọn phương thức
        </a>
        <a href="order-detail.php?order_id=<?= $order_id ?>" class="btn btn-secondary">
            <i class="fa-solid fa-receipt"></i> Xem chi tiết đơn hàng
        </a>
    </div>
</div>

</body>
</html>