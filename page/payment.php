<?php
session_start();
require_once __DIR__ . '/../db.php';

// Kiểm tra login
if (!isset($_SESSION['user'])) {
    header('Location: page/login.php');
    exit;
}

// Kiểm tra giỏ hàng có trống không
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$user = $_SESSION['user'];
$cart = $_SESSION['cart'];

// Tính tổng tiền
$total_price = 0;
$cart_items = [];

foreach ($cart as $product_id => $item) {
    $quantity = is_array($item) ? $item['quantity'] : $item;
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        $subtotal = $product['price'] * $quantity;
        $total_price += $subtotal;
        $cart_items[] = [
            'product_id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}

// Xử lý đặt hàng
$order_message = '';
$order_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $note = trim($_POST['note'] ?? '');
    
    if ($fullname && $phone && $address && $payment_method) {
        try {
            // Tạo đơn hàng
            $insert_order = $pdo->prepare("
                INSERT INTO orders (user_id, fullname, phone, address, note, payment_method, total_price, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $insert_order->execute([
                $user['user_id'],
                $fullname,
                $phone,
                $address,
                $note,
                $payment_method,
                $total_price
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Thêm chi tiết đơn hàng
            foreach ($cart_items as $item) {
                $insert_detail = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                $insert_detail->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }
            
            // Xóa giỏ hàng
            unset($_SESSION['cart']);
            
            $_SESSION['order_success'] = true;
            $_SESSION['order_id'] = $order_id;
            $_SESSION['order_method'] = $payment_method;
            
            header('Location: order_success.php?order_id=' . $order_id);
            exit;
        } catch (Exception $e) {
            $order_message = "Lỗi: " . $e->getMessage();
            $order_type = 'error';
        }
    } else {
        $order_message = "Vui lòng điền đầy đủ thông tin!";
        $order_type = 'error';
    }
}

$shipping_fee = 0;
$total_with_shipping = $total_price + $shipping_fee;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .payment-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .payment-header h1 {
            color: #007bff;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .payment-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .payment-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .payment-sidebar {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h2 {
            font-size: 18px;
            color: #007bff;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .payment-methods {
            display: grid;
            gap: 15px;
        }

        .payment-method {
            display: flex;
            gap: 12px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: #007bff;
            background: #f8faff;
        }

        .payment-method input[type="radio"] {
            cursor: pointer;
            width: 20px;
            height: 20px;
            margin-top: 2px;
        }

        .payment-method input[type="radio"]:checked ~ .method-content {
            color: #007bff;
            font-weight: 600;
        }

        .payment-method.active {
            border-color: #007bff;
            background: #f8faff;
        }

        .method-icon {
            font-size: 28px;
            color: #007bff;
            margin-right: 10px;
        }

        .method-content {
            flex: 1;
        }

        .method-title {
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }

        .method-desc {
            font-size: 13px;
            color: #999;
            margin-top: 4px;
        }

        .sidebar-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #f0f0f0;
        }

        .sidebar-section:last-child {
            border-bottom: none;
        }

        .sidebar-section h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
            color: #666;
        }

        .order-item-name {
            flex: 1;
        }

        .order-item-price {
            font-weight: 600;
            color: #007bff;
        }

        .price-summary {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
            color: #666;
        }

        .price-row.total {
            font-size: 18px;
            font-weight: 700;
            color: #007bff;
            border-top: 2px solid #e0e0e0;
            padding-top: 12px;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.3);
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .message.error {
            background: #ffcdd2;
            color: #c62828;
            border-left: 4px solid #f44336;
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
            .payment-content {
                grid-template-columns: 1fr;
            }

            .payment-sidebar {
                position: relative;
                top: 0;
            }

            .payment-form,
            .payment-sidebar {
                padding: 20px;
            }

            .payment-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="cart.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Quay lại giỏ hàng
    </a>

    <div class="payment-header">
        <h1>Thanh toán đơn hàng</h1>
        <p>Chọn hình thức thanh toán và hoàn thành đơn hàng</p>
    </div>

    <?php if ($order_message): ?>
        <div class="message <?= $order_type ?>">
            <i class="fa-solid fa-exclamation-circle"></i>
            <?= htmlspecialchars($order_message) ?>
        </div>
    <?php endif; ?>

    <div class="payment-content">
        <!-- Payment Form -->
        <form class="payment-form" method="POST">
            <!-- Customer Info -->
            <div class="form-section">
                <h2><i class="fa-solid fa-user"></i> Thông tin khách hàng</h2>
                <div class="form-group">
                    <label>Họ và tên *</label>
                    <input type="text" name="fullname" required placeholder="Nhập họ và tên" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Số điện thoại *</label>
                    <input type="tel" name="phone" required placeholder="Nhập số điện thoại" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Địa chỉ giao hàng *</label>
                    <textarea name="address" required placeholder="Nhập địa chỉ giao hàng"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Ghi chú đơn hàng (tùy chọn)</label>
                    <textarea name="note" placeholder="Ghi chú thêm..."><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="form-section">
                <h2><i class="fa-solid fa-credit-card"></i> Hình thức thanh toán</h2>
                <div class="payment-methods">
                    <!-- COD -->
                    <label class="payment-method" onclick="this.classList.toggle('active')">
                        <input type="radio" name="payment_method" value="cod" required>
                        <i class="fa-solid fa-truck-fast method-icon"></i>
                        <div class="method-content">
                            <div class="method-title">Thanh toán khi nhận hàng (COD)</div>
                            <div class="method-desc">Trả tiền trực tiếp với tài xế khi nhận hàng</div>
                        </div>
                    </label>

                    <!-- Bank Transfer -->
                    <label class="payment-method" onclick="this.classList.toggle('active')">
                        <input type="radio" name="payment_method" value="bank_transfer" required>
                        <i class="fa-solid fa-university method-icon"></i>
                        <div class="method-content">
                            <div class="method-title">Chuyển khoản ngân hàng</div>
                            <div class="method-desc">Chuyển tiền vào tài khoản ngân hàng của chúng tôi</div>
                        </div>
                    </label>

                    <!-- VNPay -->
                    <label class="payment-method" onclick="this.classList.toggle('active')">
                        <input type="radio" name="payment_method" value="vnpay" required>
                        <i class="fa-solid fa-wallet method-icon"></i>
                        <div class="method-content">
                            <div class="method-title">VNPay</div>
                            <div class="method-desc">Thanh toán qua ví điện tử VNPay</div>
                        </div>
                    </label>

                    <!-- Momo -->
                    <label class="payment-method" onclick="this.classList.toggle('active')">
                        <input type="radio" name="payment_method" value="momo" required>
                        <i class="fa-solid fa-mobile method-icon"></i>
                        <div class="method-content">
                            <div class="method-title">Momo</div>
                            <div class="method-desc">Thanh toán qua ứng dụng Momo</div>
                        </div>
                    </label>

                    <!-- PayPal -->
                    <label class="payment-method" onclick="this.classList.toggle('active')">
                        <input type="radio" name="payment_method" value="paypal" required>
                        <i class="fa-brands fa-paypal method-icon"></i>
                        <div class="method-content">
                            <div class="method-title">PayPal</div>
                            <div class="method-desc">Thanh toán quốc tế với PayPal</div>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fa-solid fa-check-circle"></i> Xác nhận đặt hàng
            </button>
        </form>

        <!-- Order Summary -->
        <aside class="payment-sidebar">
            <div class="sidebar-section">
                <h3>Chi tiết đơn hàng</h3>
                <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <span class="order-item-name">
                            <?= htmlspecialchars($item['name']) ?> <br>
                            <small style="color: #999;">x<?= $item['quantity'] ?></small>
                        </span>
                        <span class="order-item-price"><?= number_format($item['subtotal']) ?>₫</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="sidebar-section">
                <h3>Tóm tắt giá</h3>
                <div class="price-row">
                    <span>Tạm tính:</span>
                    <span><?= number_format($total_price) ?>₫</span>
                </div>
                <div class="price-row">
                    <span>Phí vận chuyển:</span>
                    <span><?= number_format($shipping_fee) ?>₫</span>
                </div>
                <div class="price-row total">
                    <span>Tổng tiền:</span>
                    <span><?= number_format($total_with_shipping) ?>₫</span>
                </div>
            </div>

            <div style="padding: 15px; background: #e3f2fd; border-radius: 8px; font-size: 13px; color: #1565c0; text-align: center;">
                <i class="fa-solid fa-shield-halved"></i> Giao dịch an toàn & bảo mật
            </div>
        </aside>
    </div>
</div>

<script>
    // Handle payment method selection
    document.querySelectorAll('input[name="payment_method"]').forEach(input => {
        input.addEventListener('change', function() {
            document.querySelectorAll('.payment-method').forEach(label => {
                label.classList.remove('active');
            });
            this.closest('.payment-method').classList.add('active');
        });
    });
</script>

</body>
</html>