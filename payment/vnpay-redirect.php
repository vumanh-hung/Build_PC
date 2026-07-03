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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_vnpay_payment'])) {
    $bank_code = $_POST['bank_code'] ?? 'VNPAYQR';
    $transaction_id = 'VNP' . date('YmdHis') . rand(1000, 9999);
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE orders SET order_status = 'paid', updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        $stmt = $pdo->prepare("UPDATE order_shipping SET payment_method = 'vnpay' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO payment_history (order_id, user_id, payment_method, transaction_id, amount, status, created_at) VALUES (?, ?, 'vnpay', ?, ?, 'completed', NOW())");
            $stmt->execute([$order_id, $user_id, $transaction_id, $order['total_price']]);
        } catch (PDOException $e) {}
        
        $pdo->commit();
        $message = 'Thanh toán thành công qua VNPay (' . $bank_code . ')! Đang chuyển hướng...';
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
    <title>Cổng Thanh Toán VNPay - BuildPC.vn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --vnpay-blue: #005baa;
            --vnpay-dark: #003b73;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #001e3d 0%, #003b73 50%, #005baa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .vnpay-card {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .vnpay-header {
            background: linear-gradient(135deg, #005baa 0%, #0088ff 100%);
            color: white;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .vnpay-logo-text {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .vnpay-body {
            padding: 30px;
        }

        .order-summary-bar {
            background: #f0f7ff;
            border-left: 4px solid #005baa;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-summary-bar .total {
            font-size: 20px;
            font-weight: 800;
            color: var(--vnpay-blue);
        }

        .bank-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }

        @media (max-width: 480px) {
            .bank-grid { grid-template-columns: repeat(2, 1fr); }
        }

        .bank-item {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 10px;
            text-align: center;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
            color: #334155;
            transition: all 0.2s ease;
        }

        .bank-item:hover, .bank-item.active {
            border-color: var(--vnpay-blue);
            background: #e0f2fe;
            color: var(--vnpay-blue);
        }

        .btn-vnpay {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #005baa 0%, #0088ff 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(0, 91, 170, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-vnpay:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 25px rgba(0, 91, 170, 0.4);
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

<div class="vnpay-card">
    <div class="vnpay-header">
        <div class="vnpay-logo-text"><i class="fa-solid fa-wallet"></i> VNPAY</div>
        <div style="font-size: 13px; font-weight: 600; opacity: 0.9;">Cổng thanh toán bảo mật</div>
    </div>

    <div class="vnpay-body">
        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $message ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?></div>
        <?php endif; ?>

        <div class="order-summary-bar">
            <div>
                <div style="font-size: 13px; color: #64748b;">Đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></div>
                <div style="font-size: 15px; font-weight: 700; color: #1e293b;">BuildPC Vietnam</div>
            </div>
            <div class="total"><?= formatPrice($order['total_price']) ?></div>
        </div>

        <div style="font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">
            Chọn ngân hàng hoặc ứng dụng thanh toán:
        </div>

        <form method="POST">
            <input type="hidden" name="bank_code" id="bankCodeInput" value="VNPAYQR">
            
            <div class="bank-grid">
                <div class="bank-item active" onclick="selectBank(this, 'VNPAYQR')">
                    <i class="fa-solid fa-qrcode" style="font-size: 20px; display: block; margin-bottom: 6px;"></i> VNPay QR
                </div>
                <div class="bank-item" onclick="selectBank(this, 'NCB')">NCB</div>
                <div class="bank-item" onclick="selectBank(this, 'VCB')">Vietcombank</div>
                <div class="bank-item" onclick="selectBank(this, 'TCB')">Techcombank</div>
                <div class="bank-item" onclick="selectBank(this, 'MB')">MBBank</div>
                <div class="bank-item" onclick="selectBank(this, 'ACB')">ACB</div>
            </div>

            <button type="submit" name="process_vnpay_payment" class="btn-vnpay">
                <i class="fa-solid fa-lock"></i> Thanh Toán Ngay Qua VNPay
            </button>
        </form>

        <a href="payment-methods.php?order_id=<?= $order_id ?>" class="btn-cancel">
            <i class="fa-solid fa-arrow-left"></i> Hủy & Chọn phương thức khác
        </a>
    </div>
</div>

<script>
function selectBank(el, code) {
    document.querySelectorAll('.bank-item').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('bankCodeInput').value = code;
}
</script>
</body>
</html>