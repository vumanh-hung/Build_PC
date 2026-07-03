<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/db.php';
require_once dirname(dirname(__FILE__)) . '/functions.php';

requireLogin();

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$user_id = $_SESSION['user']['user_id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: payment-history.php');
    exit;
}

if ($order['order_status'] !== 'pending') {
    header('Location: order-detail.php?order_id=' . $order_id);
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_momo_payment'])) {
    $transaction_id = 'MOMO' . date('YmdHis') . rand(1000, 9999);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE orders SET order_status = 'paid', updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        $stmt = $pdo->prepare("UPDATE order_shipping SET payment_method = 'momo' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO payment_history (order_id, user_id, payment_method, transaction_id, amount, status, created_at) VALUES (?, ?, 'momo', ?, ?, 'completed', NOW())");
            $stmt->execute([$order_id, $user_id, $transaction_id, $order['total_price']]);
        } catch (PDOException $e) {}
        
        $pdo->commit();
        $message = 'Thanh toán thành công qua MoMo! Đang chuyển hướng...';
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
    <title>Cổng Thanh Toán MoMo - BuildPC.vn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --momo-color: #a50064;
            --momo-gradient: linear-gradient(135deg, #a50064 0%, #d60070 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #2b0320 0%, #5c053a 50%, #8c004c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .momo-card {
            width: 100%;
            max-width: 480px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .momo-header {
            background: var(--momo-gradient);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }

        .momo-logo {
            width: 64px;
            height: 64px;
            background: white;
            color: var(--momo-color);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 900;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            margin-bottom: 12px;
        }

        .momo-header h2 {
            font-size: 22px;
            font-weight: 800;
        }

        .momo-body {
            padding: 30px 25px;
        }

        .order-info-box {
            background: #fff0f6;
            border: 1px solid #ffadd2;
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 25px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #475569;
            margin-bottom: 10px;
        }

        .info-row:last-child {
            margin-bottom: 0;
            padding-top: 10px;
            border-top: 1px dashed #ffadd2;
        }

        .total-amount {
            font-size: 22px;
            font-weight: 800;
            color: var(--momo-color);
        }

        .qr-section {
            text-align: center;
            margin-bottom: 25px;
        }

        .qr-wrapper {
            background: white;
            display: inline-block;
            padding: 15px;
            border-radius: 20px;
            border: 2px solid #f0f0f0;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 12px;
        }

        .qr-wrapper img {
            width: 180px;
            height: 180px;
            display: block;
        }

        .qr-hint {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
        }

        .btn-momo {
            width: 100%;
            padding: 16px;
            background: var(--momo-gradient);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(165, 0, 100, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-momo:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 25px rgba(165, 0, 100, 0.4);
        }

        .btn-cancel {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success { background: #dcfce7; color: #15803d; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

<div class="momo-card">
    <div class="momo-header">
        <div class="momo-logo">Mo</div>
        <h2>Cổng Thanh Toán MoMo</h2>
    </div>

    <div class="momo-body">
        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $message ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?></div>
        <?php endif; ?>

        <div class="order-info-box">
            <div class="info-row">
                <span>Mã đơn hàng:</span>
                <strong>#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div class="info-row">
                <span>Đơn vị chấp nhận:</span>
                <strong>BuildPC Vietnam</strong>
            </div>
            <div class="info-row">
                <span>Số tiền thanh toán:</span>
                <span class="total-amount"><?= formatPrice($order['total_price']) ?></span>
            </div>
        </div>

        <div class="qr-section">
            <div class="qr-wrapper">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=MOMO_PAYMENT_ORDER_<?= $order_id ?>" alt="MoMo QR Code">
            </div>
            <div class="qr-hint">Mở ứng dụng MoMo và Quét Mã QR để thanh toán</div>
        </div>

        <form method="POST">
            <button type="submit" name="process_momo_payment" class="btn-momo">
                <i class="fa-solid fa-bolt"></i> Xác Nhận Thanh Toán MoMo
            </button>
        </form>

        <a href="payment-methods.php?order_id=<?= $order_id ?>" class="btn-cancel">
            <i class="fa-solid fa-arrow-left"></i> Hủy & Chọn phương thức khác
        </a>
    </div>
</div>

</body>
</html>