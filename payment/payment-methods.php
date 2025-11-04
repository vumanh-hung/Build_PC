<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/db.php';

requireLogin();

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    header('Location: payment-history.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*, os.full_name, os.phone, os.payment_method
    FROM orders o
    LEFT JOIN order_shipping os ON o.order_id = os.order_id
    WHERE o.order_id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user']['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: payment-history.php');
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) as item_count, SUM(quantity) as total_qty FROM order_items WHERE order_id = ?");
$stmt->execute([$order_id]);
$items_info = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn phương thức thanh toán - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .back-link {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 14px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateX(-4px);
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        .header h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .header p { opacity: 0.95; font-size: 16px; }
        .payment-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .order-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 40px;
            border-left: 4px solid #667eea;
        }
        .order-summary h3 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
            color: #555;
            font-size: 15px;
            font-weight: 500;
        }
        .summary-row:last-child {
            border-bottom: none;
            font-weight: 800;
            font-size: 18px;
            color: #667eea;
            margin-top: 15px;
            padding-top: 18px;
            border-top: 2px solid #dee2e6;
        }
        .methods-section h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
        }
        .methods-grid {
            display: grid;
            gap: 16px;
            margin-bottom: 30px;
        }
        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 14px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .payment-method:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
        }
        .payment-method a {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 18px;
            width: 100%;
        }
        .method-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            flex-shrink: 0;
        }
        .method-info { flex: 1; }
        .method-info h4 { color: #333; font-size: 16px; margin-bottom: 6px; font-weight: 700; }
        .method-info p { color: #666; font-size: 13px; }
        .method-arrow { color: #667eea; font-size: 22px; flex-shrink: 0; }
        .note-box {
            background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
            border-left: 4px solid #ffc107;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .note-box p {
            color: #856404;
            font-size: 14px;
            margin: 0;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .security-badge {
            background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);
            color: white;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        footer { text-align: center; color: white; margin-top: 40px; font-size: 13px; opacity: 0.85; }
        @media (max-width: 768px) {
            .payment-card { padding: 20px; }
            .header h1 { font-size: 24px; }
            .method-icon { width: 60px; height: 60px; font-size: 28px; }
        }
    </style>
</head>
<body>
<div class="container">
    <a href="payment-history.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Quay lại
    </a>

    <div class="header">
        <h1><i class="fa-solid fa-wallet"></i> Chọn phương thức thanh toán</h1>
        <p>Đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?> | <?= $items_info['item_count'] ?> sản phẩm</p>
    </div>

    <div class="payment-card">
        <div class="order-summary">
            <h3><i class="fa-solid fa-receipt"></i> Tóm tắt đơn hàng</h3>
            <div class="summary-row">
                <span><i class="fa-solid fa-box" style="color: #667eea; margin-right: 8px;"></i> Tổng tiền hàng (<?= $items_info['total_qty'] ?> sản phẩm):</span>
                <span><?= formatPrice($order['total_price']) ?></span>
            </div>
            <div class="summary-row">
                <span><i class="fa-solid fa-truck" style="color: #667eea; margin-right: 8px;"></i> Phí vận chuyển:</span>
                <span style="color: #51cf66; font-weight: 700;">Miễn phí</span>
            </div>
            <div class="summary-row">
                <span><i class="fa-solid fa-coins" style="color: #667eea; margin-right: 8px;"></i> Tổng thanh toán:</span>
                <span><?= formatPrice($order['total_price']) ?></span>
            </div>
        </div>

        <div class="methods-section">
            <h2><i class="fa-solid fa-credit-card"></i> Chọn phương thức thanh toán</h2>
            <div class="methods-grid">
                <div class="payment-method">
                    <a href="transfer-verify.php?order_id=<?= $order_id ?>">
                        <div class="method-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                        <div class="method-info">
                            <h4>Chuyển khoản ngân hàng</h4>
                            <p>Vietcombank • Techcombank • Các ngân hàng khác</p>
                        </div>
                        <div class="method-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                    </a>
                </div>

                <div class="payment-method">
                    <a href="momo-redirect.php?order_id=<?= $order_id ?>">
                        <div class="method-icon" style="background: linear-gradient(135deg, #a50064 0%, #d60070 100%);">
                            <i class="fa-solid fa-mobile-screen"></i>
                        </div>
                        <div class="method-info">
                            <h4>Ví Momo</h4>
                            <p>Thanh toán nhanh qua ví điện tử Momo</p>
                        </div>
                        <div class="method-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                    </a>
                </div>

                <div class="payment-method">
                    <a href="vnpay-redirect.php?order_id=<?= $order_id ?>">
                        <div class="method-icon" style="background: linear-gradient(135deg, #0066b2 0%, #00a0e9 100%);">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <div class="method-info">
                            <h4>VNPay</h4>
                            <p>Thanh toán qua cổng VNPay toàn quốc</p>
                        </div>
                        <div class="method-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                    </a>
                </div>

                <div class="payment-method">
                    <a href="zalopay-redirect.php?order_id=<?= $order_id ?>">
                        <div class="method-icon" style="background: linear-gradient(135deg, #0068ff 0%, #00a6ff 100%);">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <div class="method-info">
                            <h4>ZaloPay</h4>
                            <p>Thanh toán qua ví điện tử ZaloPay</p>
                        </div>
                        <div class="method-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                    </a>
                </div>

                <div class="payment-method">
                    <a href="javascript:void(0);" onclick="confirmCOD(<?= $order_id ?>)">
                        <div class="method-icon" style="background: linear-gradient(135deg, #fd7e14 0%, #ff922b 100%);">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                        <div class="method-info">
                            <h4>Thanh toán khi nhận hàng (COD)</h4>
                            <p>Thanh toán trực tiếp khi nhận đơn hàng</p>
                        </div>
                        <div class="method-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                    </a>
                </div>
            </div>
        </div>

        <div class="note-box">
            <p>
                <i class="fa-solid fa-exclamation-circle"></i>
                <strong>Lưu ý:</strong> Hoàn tất thanh toán trong 24 giờ để giữ đơn hàng. Sau đó sẽ bị hủy tự động.
            </p>
        </div>

        <div class="security-badge">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Thanh toán an toàn • Dữ liệu được mã hoá SSL</span>
        </div>
    </div>

    <footer>
        <p>© <?= date('Y') ?> BuildPC.vn - Nền tảng mua bán máy tính & linh kiện chính hãng</p>
    </footer>
</div>

<script>
function confirmCOD(orderId) {
    if (confirm('Xác nhận thanh toán khi nhận hàng (COD)?')) {
        alert('Đơn hàng của bạn sẽ được xử lý. Chúng tôi sẽ liên hệ với bạn để xác nhận.');
        window.location.href = 'payment-history.php';
    }
}
</script>
</body>
</html>