<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

// ✅ Kiểm tra đăng nhập
$user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['user_id'] ?? 0);
if (!$user_id) {
    include __DIR__ . '/../includes/header.php';
    echo "<p class='empty'>Vui lòng <a href='../page/login.php'>đăng nhập</a> để thanh toán.</p>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// ✅ Lấy giỏ hàng của user
$stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch(PDO::FETCH_ASSOC);

// KIỂM TRA VÀ REDIRECT TRƯỚC KHI INCLUDE HEADER
if (!$cart) {
    header("Location: cart.php");
    exit;
}

// ✅ Lấy các sản phẩm trong giỏ hàng
$stmt = $pdo->prepare("
    SELECT 
        p.product_id AS id, 
        p.name, 
        p.price, 
        p.main_image, 
        ci.quantity 
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    WHERE ci.cart_id = ?
");
$stmt->execute([$cart['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// KIỂM TRA VÀ REDIRECT TRƯỚC KHI INCLUDE HEADER
if (empty($items)) {
    header("Location: cart.php");
    exit;
}

// ✅ Tính tổng tiền
$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// ✅ Lấy thông tin người dùng
$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Xử lý đặt hàng
$message = '';
$order_success = false;
$order_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cod';
    
    // ✅ Validation
    if (empty($full_name) || strlen($full_name) < 3) {
        $message = "Tên không hợp lệ (ít nhất 3 ký tự)!";
    } elseif (empty($phone)) {
        $message = "Số điện thoại không được để trống!";
    } elseif (!preg_match('/^0[0-9]{9}$/', preg_replace('/[^0-9]/', '', $phone))) {
        $message = "Số điện thoại không hợp lệ! (Định dạng: 0xxxxxxxxx)";
    } elseif (empty($address)) {
        $message = "Địa chỉ không được để trống!";
    } elseif (empty($city)) {
        $message = "Thành phố không được để trống!";
    } elseif (!in_array($payment_method, ['cod', 'bank', 'momo', 'vnpay', 'zalopay'])) {
        $message = "Phương thức thanh toán không hợp lệ!";
    } else {
        // ✅ Tạo đơn hàng
        try {
            $pdo->beginTransaction();
            
            // Thêm đơn hàng vào bảng orders
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, order_status, total_price, created_at)
                VALUES (?, 'pending', ?, NOW())
            ");
            $stmt->execute([$user_id, $total]);
            
            $order_id = $pdo->lastInsertId();
            
            // ✅ Lưu thông tin giao hàng
            $stmt = $pdo->prepare("
                INSERT INTO order_shipping (order_id, full_name, phone, address, city, notes, payment_method, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$order_id, $full_name, $phone, $address, $city, $notes, $payment_method]);
            
            // ✅ Thêm chi tiết đơn hàng vào bảng order_items
            foreach ($items as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price_each)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id,
                    $item['id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }
            
            // ✅ Xóa giỏ hàng
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cart['id']]);
            
            $pdo->commit();
            
            // ✅ Lưu thông tin vào session cho hiển thị
            $_SESSION['last_order'] = [
                'order_id' => $order_id,
                'full_name' => $full_name,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'payment_method' => $payment_method,
                'total' => $total
            ];
            
            $order_success = true;
            
            // ✅ REDIRECT sang trang thanh toán tương ứng trực tiếp
            if ($payment_method === 'cod') {
                $message = "Đặt hàng thành công! Mã đơn hàng: #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
            } elseif ($payment_method === 'momo') {
                header('Location: ../payment/momo-redirect.php?order_id=' . $order_id);
                exit;
            } elseif ($payment_method === 'vnpay') {
                header('Location: ../payment/vnpay-redirect.php?order_id=' . $order_id);
                exit;
            } elseif ($payment_method === 'zalopay') {
                header('Location: ../payment/zalopay-redirect.php?order_id=' . $order_id);
                exit;
            } else {
                header('Location: ../payment/transfer-verify.php?order_id=' . $order_id);
                exit;
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Checkout error: " . $e->getMessage());
            $message = "Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}

// SAU KHI XỬ LÝ HẾT LOGIC, MỚI INCLUDE HEADER
include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .checkout-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .checkout-header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .checkout-wrapper {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .checkout-form, .order-summary {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-section h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-row.full {
            grid-template-columns: 1fr;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="tel"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="tel"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .payment-option {
            border: 2px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-option input[type="radio"] {
            margin-right: 8px;
            cursor: pointer;
        }
        
        .payment-option label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
            cursor: pointer;
            font-weight: 500;
        }
        
        .payment-option:has(input[type="radio"]:checked) {
            border-color: #007bff;
            background: #f0f8ff;
        }
        
        .order-summary h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-of-type {
            border-bottom: none;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            color: #333;
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .item-qty {
            color: #999;
            font-size: 13px;
        }
        
        .item-price {
            color: #007bff;
            font-weight: 600;
            text-align: right;
            white-space: nowrap;
            margin-left: 20px;
        }
        
        .summary-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            color: #555;
            font-size: 14px;
        }
        
        .summary-row.total {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            padding-top: 15px;
            border-top: 2px solid #007bff;
        }
        
        .btn-checkout {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .btn-checkout:hover {
            background: #218838;
        }
        
        /* ========== COD SUCCESS STYLES ========== */
        .success-wrapper {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .success-content {
            text-align: center;
            padding: 20px;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 50px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .success-content h2 {
            color: #28a745;
            font-size: 26px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .success-content > p {
            color: #666;
            margin-bottom: 30px;
            font-size: 15px;
        }
        
        .order-code-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3f7ff 100%);
            border: 2px solid #2196f3;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .order-code-label {
            color: #1976d2;
            font-size: 12px;
            margin-bottom: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .order-code-value {
            color: #0d47a1;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .info-box {
            background: #f5f5f5;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            text-align: left;
        }
        
        .info-box h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box h3 i {
            color: #2196f3;
            font-size: 18px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
        }
        
        .info-grid > div:nth-child(odd) {
            color: #666;
            font-weight: 600;
        }
        
        .info-grid > div:nth-child(even) {
            color: #333;
            font-weight: 600;
        }
        
        .info-grid > div:nth-child(8) {
            grid-column: 1;
            color: #666;
            font-weight: 600;
        }
        
        .info-grid > div:nth-child(9) {
            color: #28a745;
            font-weight: 800;
            font-size: 18px;
        }
        
        .notice-box {
            background: linear-gradient(135deg, #c8e6c9 0%, #b9dbb5 100%);
            border-left: 4px solid #4caf50;
            padding: 18px;
            border-radius: 6px;
            margin-bottom: 25px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .notice-box-content {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        
        .notice-box-icon {
            color: #2e7d32;
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .notice-box-text {
            color: #1b5e20;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .notice-box-text strong {
            font-weight: 700;
        }
        
        .info-notice {
            background: linear-gradient(135deg, #e1f5fe 0%, #e0f2f1 100%);
            border-left: 4px solid #03a9f4;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            font-size: 13px;
            color: #01579b;
            line-height: 1.7;
        }
        
        .info-notice strong {
            font-weight: 700;
            display: block;
            margin-bottom: 6px;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn-group a {
            padding: 14px 35px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }
        
        .btn-group .btn-primary {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }
        
        .btn-group .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(33, 150, 243, 0.4);
        }
        
        .btn-group .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }
        
        .btn-group .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(108, 117, 125, 0.4);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 12px;
            }
            .checkout-header h1 {
                font-size: 24px;
            }
            .checkout-wrapper {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .checkout-form, .order-summary {
                padding: 16px;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .payment-methods {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .info-grid {
                grid-template-columns: 1fr;
                gap: 6px;
            }
            .order-code-value {
                font-size: 24px;
            }
            .btn-group {
                flex-direction: column;
                gap: 10px;
            }
            .btn-group a {
                width: 100%;
                justify-content: center;
            }
            .success-wrapper {
                padding: 20px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="cart.php" class="btn-back">← Quay lại giỏ hàng</a>
        
        <?php if ($order_success): ?>
            <div class="alert success">✓ <?php echo htmlspecialchars($message); ?></div>
            
            <div class="success-wrapper">
                <div class="success-content">
                    <!-- Success Icon -->
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    
                    <h2>Đơn hàng của bạn đã được tạo!</h2>
                    <p>Cảm ơn bạn đã mua sắm tại BuildPC</p>
                    
                    <!-- Order Code -->
                    <div class="order-code-box">
                        <div class="order-code-label">Mã đơn hàng</div>
                        <div class="order-code-value">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    
                    <!-- Delivery Information -->
                    <div class="info-box">
                        <h3>
                            <i class="fas fa-box"></i> Thông tin giao hàng:
                        </h3>
                        <div class="info-grid">
                            <div>Họ tên</div>
                            <div><?php echo htmlspecialchars($_SESSION['last_order']['full_name']); ?></div>
                            
                            <div>Số điện thoại</div>
                            <div><?php echo htmlspecialchars($_SESSION['last_order']['phone']); ?></div>
                            
                            <div>Địa chỉ</div>
                            <div>
                                <?php echo htmlspecialchars($_SESSION['last_order']['address']); ?>, 
                                <?php echo htmlspecialchars($_SESSION['last_order']['city']); ?>
                            </div>
                            
                            <div>Phương thức</div>
                            <div style="color: #28a745;"><i class="fas fa-money-bill-wave" style="margin-right: 5px;"></i>Thanh toán khi nhận hàng (COD)</div>
                            
                            <div>Tổng cộng</div>
                            <div><?php echo number_format($_SESSION['last_order']['total'], 0, ',', '.'); ?>₫</div>
                        </div>
                    </div>
                    
                    <!-- Notice -->
                    <div class="notice-box">
                        <div class="notice-box-content">
                            <i class="fas fa-phone-alt notice-box-icon"></i>
                            <div class="notice-box-text">
                                <strong>📞 Liên hệ:</strong> Chúng tôi sẽ gọi điện xác nhận đơn hàng trong 1-2 giờ. Vui lòng chú ý điện thoại!
                            </div>
                        </div>
                    </div>
                    
                    <!-- Info -->
                    <div class="info-notice">
                        <strong>✓ Đơn hàng đã được xác nhận</strong>
                        ✓ Chúng tôi sẽ liên hệ với bạn để xác nhận địa chỉ giao hàng<br>
                        ✓ Thời gian giao hàng: 1-3 ngày làm việc<br>
                        ✓ Bạn có thể theo dõi trạng thái đơn hàng tại lịch sử đơn hàng
                    </div>
                    
                    <!-- Buttons -->
                    <div class="btn-group">
                        <a href="../index.php" class="btn-secondary">
                            <i class="fas fa-shopping-cart"></i> Tiếp tục mua sắm
                        </a>
                        <a href="../payment/payment-history.php" class="btn-primary">
                            <i class="fas fa-list"></i> Xem lịch sử đơn hàng
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="checkout-header">
                <h1>💳 Thanh Toán Đơn Hàng</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert error">✗ <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="checkout-wrapper">
                <form method="POST" class="checkout-form">
                    <div class="form-section">
                        <h2>📦 Thông Tin Giao Hàng</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Họ và tên *</label>
                                <input type="text" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                       minlength="3" maxlength="100" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Số điện thoại *</label>
                                <input type="tel" id="phone" name="phone" 
                                       placeholder="0912345678" pattern="0[0-9]{9}" required>
                            </div>
                        </div>
                        
                        <div class="form-row full">
                            <div class="form-group">
                                <label for="address">Địa chỉ *</label>
                                <input type="text" id="address" name="address" 
                                       placeholder="Số nhà, tên đường" required>
                            </div>
                        </div>
                        
                        <div class="form-row full">
                            <div class="form-group">
                                <label for="city">Thành phố/Tỉnh *</label>
                                <input type="text" id="city" name="city" 
                                       placeholder="TP. Hồ Chí Minh" required>
                            </div>
                        </div>
                        
                        <div class="form-row full">
                            <div class="form-group">
                                <label for="notes">Ghi chú đơn hàng</label>
                                <textarea id="notes" name="notes" placeholder="Ghi chú thêm (tùy chọn)"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section" style="margin-top: 30px;">
                        <h2>💰 Phương Thức Thanh Toán</h2>
                        
                        <div class="payment-methods" style="display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 20px;">
                            <div class="payment-option" style="border: 2px solid #ddd; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 10px;">
                                <input type="radio" id="cod" name="payment_method" value="cod" checked style="margin-right: 8px;">
                                <label for="cod" style="display: flex; align-items: center; gap: 10px; margin-bottom: 0; cursor: pointer; font-weight: 600; width: 100%;">
                                    <i class="fas fa-money-bill-wave" style="color: #28a745; font-size: 20px;"></i>
                                    💵 Thanh toán khi nhận hàng (COD)
                                </label>
                            </div>
                            
                            <div class="payment-option" style="border: 2px solid #ddd; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 10px;">
                                <input type="radio" id="bank" name="payment_method" value="bank" style="margin-right: 8px;">
                                <label for="bank" style="display: flex; align-items: center; gap: 10px; margin-bottom: 0; cursor: pointer; font-weight: 600; width: 100%;">
                                    <i class="fas fa-qrcode" style="color: #2196f3; font-size: 20px;"></i>
                                    🏦 Chuyển khoản VietQR (Ngân hàng)
                                </label>
                            </div>

                            <div class="payment-option" style="border: 2px solid #ddd; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 10px;">
                                <input type="radio" id="momo" name="payment_method" value="momo" style="margin-right: 8px;">
                                <label for="momo" style="display: flex; align-items: center; gap: 10px; margin-bottom: 0; cursor: pointer; font-weight: 600; width: 100%;">
                                    <i class="fas fa-mobile-alt" style="color: #a50064; font-size: 20px;"></i>
                                    📱 Thanh toán Ví MoMo
                                </label>
                            </div>

                            <div class="payment-option" style="border: 2px solid #ddd; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 10px;">
                                <input type="radio" id="vnpay" name="payment_method" value="vnpay" style="margin-right: 8px;">
                                <label for="vnpay" style="display: flex; align-items: center; gap: 10px; margin-bottom: 0; cursor: pointer; font-weight: 600; width: 100%;">
                                    <i class="fas fa-credit-card" style="color: #005baa; font-size: 20px;"></i>
                                    💳 Cổng thanh toán VNPay
                                </label>
                            </div>

                            <div class="payment-option" style="border: 2px solid #ddd; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 10px;">
                                <input type="radio" id="zalopay" name="payment_method" value="zalopay" style="margin-right: 8px;">
                                <label for="zalopay" style="display: flex; align-items: center; gap: 10px; margin-bottom: 0; cursor: pointer; font-weight: 600; width: 100%;">
                                    <i class="fas fa-wallet" style="color: #0284c7; font-size: 20px;"></i>
                                    🧩 Ví điện tử ZaloPay
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-checkout">✓ Đặt Hàng Ngay</button>
                </form>
                
                <div class="order-summary">
                    <h2>🛒 Tóm Tắt Đơn Hàng</h2>
                    
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($items as $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                        ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-qty">x<?php echo $item['quantity']; ?> × <?php echo number_format($item['price'], 0, ',', '.'); ?>₫</div>
                                </div>
                                <div class="item-price"><?php echo number_format($subtotal, 0, ',', '.'); ?>₫</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-section">
                        <div class="summary-row">
                            <span>Tạm tính:</span>
                            <span><?php echo number_format($total, 0, ',', '.'); ?>₫</span>
                        </div>
                        <div class="summary-row">
                            <span>Phí vận chuyển:</span>
                            <span style="color: #28a745; font-weight: 600;">Miễn phí</span>
                        </div>
                        <div class="summary-row">
                            <span>Giảm giá:</span>
                            <span>0₫</span>
                        </div>
                        <div class="summary-row total">
                            <span>Tổng cộng:</span>
                            <span><?php echo number_format($total, 0, ',', '.'); ?>₫</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php include '../includes/footer.php'; ?>