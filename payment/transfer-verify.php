<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/db.php';

// ✅ Kiểm tra đăng nhập
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: ../page/login.php');
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$verify_message = '';
$verify_error = '';

// ✅ Validate order_id
if (!$order_id) {
    header('Location: payment-history.php');
    exit;
}

// ✅ Lấy thông tin đơn hàng trực tiếp từ database
$stmt = $pdo->prepare("
    SELECT o.order_id, o.total_price, o.order_status, o.created_at, o.user_id,
           os.full_name, os.phone, os.address, os.city, os.payment_method
    FROM orders o
    LEFT JOIN order_shipping os ON o.order_id = os.order_id
    WHERE o.order_id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: payment-history.php');
    exit;
}

// ✅ Nếu đơn hàng đã thanh toán
if ($order['order_status'] !== 'pending') {
    header('Location: order-detail.php?order_id=' . $order_id);
    exit;
}

// ✅ Lấy chi tiết sản phẩm
$stmt = $pdo->prepare("
    SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.price_each as price,
           p.name as product_name, p.main_image as image_url
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Thông tin ngân hàng
$banks = [
    [
        'name' => 'Vietcombank',
        'account' => '1234567890',
        'holder' => 'BUILDPC.VN',
        'branch' => 'TP HỒ CHÍ MINH',
        'icon' => 'fa-building'
    ],
    [
        'name' => 'Techcombank',
        'account' => '0987654321',
        'holder' => 'BUILDPC.VN',
        'branch' => 'TP HỒ CHÍ MINH',
        'icon' => 'fa-building'
    ]
];

// ✅ Xử lý xác nhận thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_payment'])) {
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    
    // Validate dữ liệu
    if (empty($transaction_id)) {
        $verify_error = 'Vui lòng nhập mã giao dịch';
    } elseif (empty($bank_name)) {
        $verify_error = 'Vui lòng chọn ngân hàng';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Cập nhật trạng thái đơn hàng thành "paid"
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET order_status = 'paid', updated_at = NOW()
                WHERE order_id = ? AND user_id = ?
            ");
            $stmt->execute([$order_id, $user_id]);
            
            // Cập nhật phương thức thanh toán
            $stmt = $pdo->prepare("
                UPDATE order_shipping 
                SET payment_method = 'bank_transfer'
                WHERE order_id = ?
            ");
            $stmt->execute([$order_id]);
            
            // Lưu lịch sử thanh toán
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO payment_history (order_id, user_id, payment_method, transaction_id, amount, status, created_at)
                    VALUES (?, ?, 'bank_transfer', ?, ?, 'completed', NOW())
                ");
                $stmt->execute([$order_id, $user_id, $transaction_id, $order['total_price']]);
            } catch (PDOException $e) {
                // Bảng không tồn tại, bỏ qua
            }
            
            // Lưu lịch sử trạng thái
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO order_status_history (order_id, status, note, updated_at)
                    VALUES (?, 'paid', ?, NOW())
                ");
                $stmt->execute([$order_id, 'Chuyển khoản qua ' . $bank_name . ' - Mã: ' . $transaction_id]);
            } catch (PDOException $e) {
                // Bảng không tồn tại, bỏ qua
            }
            
            $pdo->commit();
            $verify_message = 'Xác nhận thanh toán thành công! Đơn hàng của bạn đang được xử lý.';
            
            // Redirect sau 2 giây
            header('Refresh: 2; url=order-detail.php?order_id=' . $order_id);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $verify_error = 'Lỗi xử lý thanh toán: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận chuyển khoản #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?> - BuildPC.vn</title>
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
            box-shadow: 0 8px 24px rgba(0, 123, 255, 0.2);
        }

        .header h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }

        .header p {
            opacity: 0.9;
            font-size: 15px;
            font-weight: 500;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #e9ecef;
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

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .alert i {
            font-size: 18px;
        }

        .product-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 16px;
            align-items: center;
            padding: 16px;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 12px;
            background: #f8f9fa;
        }

        .product-item:last-child {
            margin-bottom: 0;
        }

        .product-image {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-image i {
            font-size: 32px;
            color: #dee2e6;
        }

        .product-info h4 {
            color: #2d3436;
            font-weight: 700;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .product-info p {
            color: #6c757d;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .product-price {
            text-align: right;
            white-space: nowrap;
        }

        .product-price .quantity {
            color: #6c757d;
            font-size: 12px;
            display: block;
            margin-bottom: 4px;
        }

        .product-price .subtotal {
            color: #007bff;
            font-weight: 800;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 10px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: #007bff;
            font-size: 16px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
            color: #2d3436;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            background: #f8f9ff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .form-group input::placeholder {
            color: #adb5bd;
        }

        .bank-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .bank-option {
            position: relative;
        }

        .bank-option input[type="radio"] {
            display: none;
        }

        .bank-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            margin: 0;
            gap: 8px;
            font-size: 13px;
            text-align: center;
            font-weight: 600;
            color: #495057;
        }

        .bank-option label i {
            font-size: 24px;
            color: #6c757d;
        }

        .bank-option input[type="radio"]:checked + label {
            border-color: #007bff;
            background: #f8f9ff;
            color: #0056b3;
        }

        .bank-option input[type="radio"]:checked + label i {
            color: #007bff;
        }

        .bank-info-box {
            background: #cfe2ff;
            padding: 16px;
            border-radius: 10px;
            border-left: 3px solid #007bff;
            margin-bottom: 16px;
        }

        .bank-info-row {
            margin-bottom: 12px;
            font-size: 14px;
        }

        .bank-info-row:last-child {
            margin-bottom: 0;
        }

        .bank-info-row strong {
            color: #084298;
            display: block;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .bank-info-row small {
            color: #084298;
            display: block;
        }

        .bank-info-row .amount {
            color: #0056b3;
            font-size: 18px;
            font-weight: 800;
        }

        .bank-details {
            background: #f0f8ff;
            padding: 12px;
            border-radius: 8px;
            margin-top: 8px;
            font-size: 12px;
        }

        .bank-details div {
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
        }

        .bank-details div:last-child {
            margin-bottom: 0;
        }

        .bank-details strong {
            color: #007bff;
        }

        .order-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
            border-radius: 12px;
            padding: 24px;
            border: 2px solid #e9ecef;
            margin-top: 20px;
        }

        .summary-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            padding: 12px 0;
            font-size: 14px;
            font-weight: 600;
            color: #495057;
        }

        .summary-row.total {
            padding: 16px 0;
            border-top: 2px solid #dee2e6;
            border-bottom: 2px solid #dee2e6;
            font-size: 20px;
            font-weight: 800;
            color: #007bff;
            margin: 12px 0;
            letter-spacing: -0.5px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            padding: 16px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            flex: 1;
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

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #007bff;
        }

        .info-item .label {
            font-size: 12px;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .info-item .value {
            font-size: 15px;
            font-weight: 700;
            color: #2d3436;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }

            .header {
                padding: 24px 16px;
            }

            .header h1 {
                font-size: 24px;
            }

            .card-body {
                padding: 16px;
            }

            .product-item {
                grid-template-columns: 80px 1fr;
                gap: 12px;
            }

            .product-image {
                width: 80px;
                height: 80px;
            }

            .bank-selector {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 20px;
            }

            .bank-selector {
                grid-template-columns: 1fr;
            }

            .btn {
                padding: 14px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="order-detail.php?order_id=<?= $order_id ?>" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Quay lại chi tiết đơn hàng
    </a>

    <div class="header">
        <h1><i class="fa-solid fa-building"></i> Xác nhận chuyển khoản</h1>
        <p><i class="fa-solid fa-receipt"></i> Mã đơn: #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
    </div>

    <?php if (!empty($verify_message)): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-check-circle"></i>
            <span><?= htmlspecialchars($verify_message) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($verify_error)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($verify_error) ?></span>
        </div>
    <?php endif; ?>

    <div class="content-grid">
        <!-- ✅ Form xác nhận -->
        <div>
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-check-double"></i> Xác nhận thanh toán
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="verifyForm">
                        <!-- Chọn ngân hàng -->
                        <div class="form-group">
                            <label><i class="fa-solid fa-building"></i> Chọn ngân hàng chuyển khoản</label>
                            <div class="bank-selector">
                                <?php foreach ($banks as $index => $bank): ?>
                                <div class="bank-option">
                                    <input type="radio" id="bank_<?= $index ?>" name="bank_name" value="<?= htmlspecialchars($bank['name']) ?>" required>
                                    <label for="bank_<?= $index ?>">
                                        <i class="fa-solid <?= $bank['icon'] ?>"></i>
                                        <?= htmlspecialchars($bank['name']) ?>
                                    </label>
                                    <div class="bank-details">
                                        <div><strong>TK:</strong> <?= htmlspecialchars($bank['account']) ?></div>
                                        <div><strong>CTK:</strong> <?= htmlspecialchars($bank['holder']) ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Mã giao dịch -->
                        <div class="form-group">
                            <label><i class="fa-solid fa-hashtag"></i> Mã giao dịch (Ref ID)</label>
                            <input type="text" name="transaction_id" placeholder="VD: VCB123456789 hoặc TCB987654321" required>
                            <small style="color: #6c757d; margin-top: 6px; display: block;">
                                <i class="fa-solid fa-info-circle"></i> 
                                Mã này được hiển thị trên biên lai ATM hoặc trong tin nhắn xác nhận ngân hàng
                            </small>
                        </div>

                        <!-- Thông tin hướng dẫn -->
                        <div class="bank-info-box">
                            <div class="bank-info-row">
                                <strong><i class="fa-solid fa-lightbulb"></i> Lưu ý quan trọng:</strong>
                                <small>• Số tiền: <strong><?= formatPrice($order['total_price']) ?></strong></small>
                            </div>
                            <div class="bank-info-row">
                                <small>• Nội dung chuyển: <strong>BUILDPC<?= $order_id ?></strong></small>
                            </div>
                            <div class="bank-info-row">
                                <small>• Hệ thống tự động xác nhận trong 2-5 phút</small>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">
                                <i class="fa-solid fa-times"></i> Hủy
                            </button>
                            <button type="submit" name="verify_payment" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Xác nhận thanh toán
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ✅ Tóm tắt đơn hàng -->
        <div>
            <!-- Thông tin đơn hàng -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <i class="fa-solid fa-info-circle"></i> Thông tin đơn hàng
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="label">Mã đơn hàng</div>
                            <div class="value">#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="label">Trạng thái</div>
                            <div class="value" style="color: #0056b3;">Chờ xác nhận</div>
                        </div>
                        <div class="info-item">
                            <div class="label">Ngày đặt</div>
                            <div class="value"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="label">Người nhận</div>
                            <div class="value"><?= htmlspecialchars($order['full_name'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danh sách sản phẩm -->
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-box"></i> Sản phẩm
                </div>
                <div class="card-body">
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
                                <p><strong><?= formatPrice($item['price']) ?></strong> x <?= $item['quantity'] ?></p>
                            </div>
                            <div class="product-price">
                                <div class="quantity">SL: <?= $item['quantity'] ?></div>
                                <div class="subtotal"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="order-summary">
                        <div class="summary-row">
                            <span><i class="fa-solid fa-box"></i> Tổng sản phẩm:</span>
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
    </div>
</div>

<script>
// ✅ Form validation
document.getElementById('verifyForm').addEventListener('submit', function(e) {
    const bankName = document.querySelector('input[name="bank_name"]:checked');
    const transactionId = document.querySelector('input[name="transaction_id"]').value.trim();
    
    if (!bankName) {
        e.preventDefault();
        alert('Vui lòng chọn ngân hàng');
        return;
    }

    if (!transactionId) {
        e.preventDefault();
        alert('Vui lòng nhập mã giao dịch');
        return;
    }
});
</script>

</body>
</html>