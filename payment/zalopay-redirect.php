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

if ($order['order_status'] !== 'pending') {
    header('Location: order-detail.php?order_id=' . $order_id);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_zalopay_payment'])) {
    $transaction_id = 'ZLP' . date('YmdHis') . rand(1000, 9999);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE orders SET order_status = 'paid', updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        $stmt = $pdo->prepare("UPDATE order_shipping SET payment_method = 'zalopay' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO payment_history (order_id, user_id, payment_method, transaction_id, amount, status, created_at) VALUES (?, ?, 'zalopay', ?, ?, 'completed', NOW())");
            $stmt->execute([$order_id, $user_id, $transaction_id, $order['total_price']]);
        } catch (PDOException $e) {}
        
        $pdo->commit();
        $message = 'Thanh toán thành công qua ZaloPay! Đang chuyển hướng...';
        header('Refresh: 2; url=order-detail.php?order_id=' . $order_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Lỗi xử lý: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán ZaloPay - BuildPC.vn</title>
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
            background: linear-gradient(135deg, #0068ff 0%, #00a6ff 100%);
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
            color: #00a6ff;
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
            color: #00a6ff;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .amount {
            font-size: 32px;
            font-weight: 800;
            color: #00a6ff;
            margin: 30px 0;
            letter-spacing: -0.5px;
        }

        .status-message {
            background: linear-gradient(135deg, #f0f0f0 0%, #f8f8f8 100%);
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            border-left: 4px solid #00a6ff;
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
            background: linear-gradient(135deg, #0068ff 0%, #00a6ff 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 166, 255, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 166, 255, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #00a6ff;
        }

        .btn-secondary:hover {
            background: #e8e8e8;
            transform: translateY(-2px);
        }

        .info-box {
            background: #e1f5ff;
            border: 1px solid #80deea;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #006064;
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
    
    <h1>Cổng Thanh Toán ZaloPay</h1>
    
    <div class="order-id">
        <i class="fa-solid fa-receipt"></i> Đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="background: #dcfce7; color: #15803d; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><i class="fa-solid fa-circle-check"></i> <?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error" style="background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?></div>
    <?php endif; ?>
    
    <div class="amount">
        <?= formatPriceVND($order['total_price']) ?>
    </div>

    <div class="qr-section" style="margin: 20px 0;">
        <div class="qr-wrapper" style="background: white; display: inline-block; padding: 15px; border-radius: 20px; border: 2px solid #00a6ff; margin-bottom: 12px;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=ZALOPAY_PAYMENT_ORDER_<?= $order_id ?>" alt="ZaloPay QR Code" style="width: 180px; height: 180px; display: block;">
        </div>
        <div class="qr-hint" style="font-size: 13px; color: #64748b; font-weight: 600;">Mở ứng dụng ZaloPay và Quét Mã QR để thanh toán</div>
    </div>

    <div class="info-box">
        <strong><i class="fa-solid fa-lightbulb"></i> Ghi chú giao dịch:</strong>
        Hệ thống đang chạy chế độ mô phỏng thanh toán ZaloPay an toàn. Nhấn nút bên dưới để hoàn tất giao dịch.
    </div>

    <div class="btn-container">
        <form method="POST" style="width: 100%;">
            <button type="submit" name="process_zalopay_payment" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-bolt"></i> Xác Nhận Thanh Toán ZaloPay
            </button>
        </form>
        <a href="payment-methods.php?order_id=<?= $order_id ?>" class="btn btn-secondary" style="margin-top: 10px;">
            <i class="fa-solid fa-arrow-left"></i> Hủy & Chọn phương thức khác
        </a>
    </div>
</div>

</body>
</html>