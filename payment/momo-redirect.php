<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/db.php';

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
    <title>Thanh toán Momo - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #a50064 0%, #d60070 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container { max-width: 600px; background: white; border-radius: 16px; padding: 40px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); text-align: center; }
        .icon { font-size: 80px; color: #d60070; margin-bottom: 20px; }
        h1 { font-size: 28px; font-weight: 800; color: #333; margin-bottom: 12px; }
        p { color: #6c757d; font-size: 15px; margin-bottom: 8px; }
        .amount { font-size: 32px; font-weight: 800; color: #d60070; margin: 30px 0; }
        .btn { display: inline-block; padding: 12px 40px; margin-top: 20px; border-radius: 10px; background: linear-gradient(135deg, #a50064 0%, #d60070 100%); color: white; text-decoration: none; font-weight: 700; transition: all 0.3s ease; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(214, 0, 112, 0.4); }
    </style>
</head>
<body>
<div class="container">
    <div class="icon"><i class="fa-solid fa-mobile-screen"></i></div>
    <h1>Thanh toán Momo</h1>
    <p>Đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
    <div class="amount"><?= formatPrice($order['total_price']) ?></div>
    <p style="color: #333; font-weight: 600;">Tính năng đang được phát triển</p>
    <p>Vui lòng quay lại để chọn phương thức khác</p>
    <a href="payment-methods.php?order_id=<?= $order_id ?>" class="btn">← Quay lại</a>
</div>
</body>
</html>
