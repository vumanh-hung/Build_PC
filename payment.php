<?php
session_start();
require_once __DIR__ . '/../db.php';

// Kiểm tra login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Lấy order_id từ URL
$order_id = $_GET['order_id'] ?? 0;
if (!$order_id) {
    header('Location: ../index.php');
    exit;
}

// Lấy thông tin đơn hàng
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user']['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ../index.php');
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $pdo->prepare("
    SELECT oi.*, p.name 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Thông tin thanh toán
$bank_accounts = [
    [
        'id' => 'vietcombank',
        'name' => 'Vietcombank',
        'account' => '1234567890',
        'owner' => 'CÔNG TY CỔ PHẦN BUILD PC',
        'branch' => 'CN Hồ Chí Minh',
        'icon' => '🏦'
    ],
    [
        'id' => 'techcombank',
        'name' => 'Techcombank',
        'account' => '0123456789',
        'owner' => 'CÔNG TY CỔ PHẦN BUILD PC',
        'branch' => 'CN Hồ Chí Minh',
        'icon' => '🏦'
    ],
];

$wallets = [
    [
        'id' => 'momo',
        'name' => 'Ví Momo',
        'account' => '0987654321',
        'owner' => 'BUILD PC',
        'icon' => '📱'
    ],
];

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết thanh toán - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            color: #007bff;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .status-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .status-box h2 {
            color: #2e7d32;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .status-badge {
            display: inline-block;
            background: #4caf50;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .payment-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .payment-section h2 {
            font-size: 20px;
            color: #007bff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #007bff;
        }

        .payment-method-group {
            margin-bottom: 30px;
        }

        .payment-method-group h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .payment-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-card:hover {
            border-color: #007bff;
            background: #f8faff;
        }

        .payment-card.active {
            border-color: #007bff;
            background: #f8faff;
        }

        .payment-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .payment-icon {
            font-size: 32px;
            width: 50px;
            text-align: center;
        }

        .payment-title {
            flex: 1;
        }

        .payment-title h4 {
            font-size: 16px;
            color: #333;
            margin-bottom: 3px;
            font-weight: 600;
        }

        .payment-title p {
            font-size: 13px;
            color: #999;
        }

        .payment-details {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .payment-details.show {
            display: block;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
            text-align: right;
            word-break: break-all;
        }

        .copy-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            background: #0056b3;
        }

        .copy-btn.copied {
            background: #28a745;
        }

        .instruction-box {
            background: #fffacd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            font-size: 13px;
        }

        .instruction-box h4 {
            color: #856404;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .instruction-box ol {
            margin-left: 20px;
            color: #856404;
        }

        .instruction-box li {
            margin: 5px 0;
        }

        .sidebar {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .sidebar h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }

        .order-info {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-info p {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .order-info-label {
            color: #666;
        }

        .order-info-value {
            color: #333;
            font-weight: 600;
        }

        .order-items {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            max-height: 300px;
            overflow-y: auto;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .item-name {
            color: #666;
            flex: 1;
        }

        .item-price {
            color: #007bff;
            font-weight: 600;
            text-align: right;
            margin-left: 10px;
        }

        .price-summary {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .price-summary.total {
            font-size: 18px;
            font-weight: 700;
            color: #007bff;
            border-bottom: none;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }

        .back-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 20px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                top: 0;
            }

            .payment-section,
            .sidebar {
                padding: 20px;
            }

            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="checkout.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Quay lại thanh toán
    </a>

    <div class="header">
        <h1>Chi tiết thanh toán</h1>
        <p>Mã đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
    </div>

    <div class="status-box">
        <h2><i class="fa-solid fa-circle-check"></i> Đơn hàng được tạo thành công!</h2>
        <p style="margin-bottom: 10px;">Vui lòng chọn phương thức thanh toán để hoàn tất đơn hàng</p>
        <span class="status-badge">Chờ thanh toán</span>
    </div>

    <div class="content">
        <!-- Payment Methods -->
        <div class="payment-section">
            <h2><i class="fa-solid fa-credit-card"></i> Phương thức thanh toán</h2>

            <!-- Banks -->
            <div class="payment-method-group">
                <h3>🏦 Chuyển khoản ngân hàng</h3>
                <?php foreach ($bank_accounts as $bank): ?>
                <div class="payment-card" onclick="togglePaymentDetails(this, '<?= $bank['id'] ?>')">
                    <div class="payment-header">
                        <div class="payment-icon"><?= $bank['icon'] ?></div>
                        <div class="payment-title">
                            <h4><?= $bank['name'] ?></h4>
                            <p><?= $bank['branch'] ?></p>
                        </div>
                    </div>
                    <div class="payment-details" id="details-<?= $bank['id'] ?>">
                        <div class="detail-row">
                            <span class="detail-label">Chủ tài khoản:</span>
                            <span class="detail-value"><?= $bank['owner'] ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Số tài khoản:</span>
                            <span class="detail-value account-number"><?= $bank['account'] ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Nội dung chuyển:</span>
                            <span class="detail-value">DH<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Số tiền:</span>
                            <span class="detail-value"><?= number_format($order['total_price']) ?>₫</span>
                        </div>
                        <button type="button" class="copy-btn" onclick="copyText(this, '<?= $bank['account'] ?>')">
                            <i class="fa-solid fa-copy"></i> Sao chép
                        </button>

                        <div class="instruction-box">
                            <h4><i class="fa-solid fa-lightbulb"></i> Hướng dẫn:</h4>
                            <ol>
                                <li>Mở ứng dụng ngân hàng</li>
                                <li>Chọn chuyển tiền</li>
                                <li>Nhập số tài khoản: <?= $bank['account'] ?></li>
                                <li>Nhập số tiền: <?= number_format($order['total_price']) ?>₫</li>
                                <li>Nội dung: DH<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></li>
                                <li>Xác nhận và gửi</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Wallets -->
            <div class="payment-method-group">
                <h3>📱 Ví điện tử</h3>
                <?php foreach ($wallets as $wallet): ?>
                <div class="payment-card" onclick="togglePaymentDetails(this, '<?= $wallet['id'] ?>')">
                    <div class="payment-header">
                        <div class="payment-icon"><?= $wallet['icon'] ?></div>
                        <div class="payment-title">
                            <h4><?= $wallet['name'] ?></h4>
                            <p>Ví điện tử</p>
                        </div>
                    </div>
                    <div class="payment-details" id="details-<?= $wallet['id'] ?>">
                        <div class="detail-row">
                            <span class="detail-label">Chủ tài khoản:</span>
                            <span class="detail-value"><?= $wallet['owner'] ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Số điện thoại:</span>
                            <span class="detail-value phone-number"><?= $wallet['account'] ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Số tiền:</span>
                            <span class="detail-value"><?= number_format($order['total_price']) ?>₫</span>
                        </div>
                        <button type="button" class="copy-btn" onclick="copyText(this, '<?= $wallet['account'] ?>')">
                            <i class="fa-solid fa-copy"></i> Sao chép
                        </button>

                        <div class="instruction-box">
                            <h4><i class="fa-solid fa-lightbulb"></i> Hướng dẫn:</h4>
                            <ol>
                                <li>Mở ứng dụng <?= $wallet['name'] ?></li>
                                <li>Chọn gửi tiền</li>
                                <li>Nhập số điện thoại: <?= $wallet['account'] ?></li>
                                <li>Nhập số tiền: <?= number_format($order['total_price']) ?>₫</li>
                                <li>Xác nhận giao dịch</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; border-radius: 6px; font-size: 13px; color: #1565c0;">
                <i class="fa-solid fa-info-circle"></i> <strong>Lưu ý:</strong> Sau khi chuyển khoản, vui lòng kiên nhẫn chờ từ 5-10 phút để hệ thống xác nhận tự động.
            </div>
        </div>

        <!-- Order Summary -->
        <aside class="sidebar">
            <h3>Thông tin đơn hàng</h3>

            <div class="order-info">
                <p>
                    <span class="order-info-label">Mã đơn hàng:</span>
                    <span class="order-info-value">#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></span>
                </p>
                <p>
                    <span class="order-info-label">Họ tên:</span>
                    <span class="order-info-value"><?= htmlspecialchars($order['fullname']) ?></span>
                </p>
                <p>
                    <span class="order-info-label">Số điện thoại:</span>
                    <span class="order-info-value"><?= htmlspecialchars($order['phone']) ?></span>
                </p>
                <p>
                    <span class="order-info-label">Địa chỉ:</span>
                    <span class="order-info-value"><?= htmlspecialchars(substr($order['address'], 0, 30)) ?>...</span>
                </p>
            </div>

            <h3>Sản phẩm</h3>
            <div class="order-items">
                <?php foreach ($items as $item): ?>
                <div class="order-item">
                    <span class="item-name">
                        <?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)
                    </span>
                    <span class="item-price"><?= number_format($item['price'] * $item['quantity']) ?>₫</span>
                </div>
                <?php endforeach; ?>
            </div>

            <h3 style="border-bottom: none; padding-bottom: 0; margin-bottom: 15px;">Giá</h3>
            <div class="price-summary">
                <span>Tạm tính:</span>
                <span><?= number_format($order['total_price']) ?>₫</span>
            </div>
            <div class="price-summary">
                <span>Vận chuyển:</span>
                <span>Miễn phí</span>
            </div>
            <div class="price-summary total">
                <span>Tổng cộng:</span>
                <span><?= number_format($order['total_price']) ?>₫</span>
            </div>
        </aside>
    </div>
</div>

<script>
    function togglePaymentDetails(element, methodId) {
        const details = document.getElementById('details-' + methodId);
        const allCards = document.querySelectorAll('.payment-card');
        const allDetails = document.querySelectorAll('.payment-details');

        // Xóa active class từ tất cả cards
        allCards.forEach(card => card.classList.remove('active'));
        allDetails.forEach(detail => detail.classList.remove('show'));

        // Thêm active class cho card được click
        element.classList.add('active');
        details.classList.add('show');
    }

    function copyText(button, text) {
        navigator.clipboard.writeText(text).then(() => {
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fa-solid fa-check"></i> Đã sao chép';
            button.classList.add('copied');

            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('copied');
            }, 2000);
        });
    }

    // Tự động mở card đầu tiên
    window.addEventListener('load', () => {
        const firstCard = document.querySelector('.payment-card');
        if (firstCard) firstCard.click();
    });
</script>

</body>
</html>