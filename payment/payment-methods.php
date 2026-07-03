<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/db.php';
require_once dirname(dirname(__FILE__)) . '/functions.php';

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
    <title>Phương Thức Thanh Toán - BuildPC.vn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #311042 100%);
            --card-bg: rgba(255, 255, 255, 0.96);
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            color: var(--text-main);
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 960px;
            margin: 0 auto;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-4px);
            color: #ffffff;
        }

        .header-title {
            text-align: center;
            color: white;
            margin-bottom: 35px;
        }

        .header-title h1 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
            background: linear-gradient(to right, #ffffff, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-title p {
            color: #94a3b8;
            font-size: 15px;
            font-weight: 500;
        }

        .main-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 35px;
            backdrop-filter: blur(20px);
        }

        @media (max-width: 868px) {
            .main-card {
                grid-template-columns: 1fr;
            }
        }

        .order-summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .summary-header {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px dashed #cbd5e1;
        }

        .summary-header i {
            color: var(--primary);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .summary-item strong {
            color: var(--text-main);
        }

        .summary-total {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 2px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-total span {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
        }

        .summary-total .price {
            font-size: 24px;
            font-weight: 800;
            color: #4f46e5;
        }

        .security-badge-box {
            margin-top: 25px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1e40af;
            font-size: 13px;
            font-weight: 600;
        }

        .security-badge-box i {
            font-size: 20px;
            color: #3b82f6;
        }

        .methods-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .methods-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .method-card {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .method-card:hover {
            border-color: #6366f1;
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -10px rgba(99, 102, 241, 0.3);
            background: #f8fafc;
        }

        .method-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            flex-shrink: 0;
            margin-right: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .method-info {
            flex: 1;
        }

        .method-info h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .method-info p {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .badge-recommended {
            background: #dcfce7;
            color: #15803d;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .method-arrow {
            font-size: 16px;
            color: #94a3b8;
            transition: transform 0.3s ease, color 0.3s ease;
        }

        .method-card:hover .method-arrow {
            color: #6366f1;
            transform: translateX(4px);
        }

        /* MODAL STYLES */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-box {
            background: white;
            width: 90%;
            max-width: 450px;
            border-radius: 24px;
            padding: 32px;
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-overlay.active .modal-box {
            transform: scale(1);
        }

        .modal-icon {
            width: 70px;
            height: 70px;
            background: #ffedd5;
            color: #ea580c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
        }

        .modal-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 10px;
        }

        .modal-desc {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .btn-modal {
            flex: 1;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        .btn-confirm {
            background: #ea580c;
            color: white;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3);
        }

        .btn-confirm:hover {
            background: #c2410c;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <a href="payment-history.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Lịch sử đơn hàng
        </a>
    </div>

    <div class="header-title">
        <h1>Thanh Toán Đơn Hàng</h1>
        <p>Mã đơn hàng: #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?> • Chọn phương thức bên dưới để tiếp tục</p>
    </div>

    <div class="main-card">
        <!-- Sidebar Summary -->
        <div class="order-summary-box">
            <div>
                <div class="summary-header">
                    <i class="fa-solid fa-receipt"></i> Thông Tin Đơn Hàng
                </div>
                <div class="summary-item">
                    <span>Sản phẩm</span>
                    <strong><?= $items_info['item_count'] ?> loại (<?= $items_info['total_qty'] ?> món)</strong>
                </div>
                <div class="summary-item">
                    <span>Phí vận chuyển</span>
                    <strong style="color: #16a34a;">Miễn phí</strong>
                </div>
                <div class="summary-item">
                    <span>Khách hàng</span>
                    <strong><?= htmlspecialchars($order['full_name'] ?? 'Khách hàng') ?></strong>
                </div>
            </div>

            <div>
                <div class="summary-total">
                    <span>Tổng tiền:</span>
                    <div class="price"><?= formatPrice($order['total_price']) ?></div>
                </div>

                <div class="security-badge-box">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Bảo mật 100% qua chuẩn mã hóa giao dịch SSL</span>
                </div>
            </div>
        </div>

        <!-- Payment Methods List -->
        <div>
            <div class="methods-title">
                <i class="fa-solid fa-wallet" style="color: var(--primary);"></i> Chọn Phương Thức
            </div>

            <div class="methods-list">
                <!-- VietQR Bank Transfer -->
                <a href="transfer-verify.php?order_id=<?= $order_id ?>" class="method-card">
                    <div class="method-icon" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <div class="method-info">
                        <h4>Chuyển khoản Ngân hàng (VietQR) <span class="badge-recommended">Khuyên dùng</span></h4>
                        <p>Quét mã QR chuyển khoản nhanh 24/7 với Vietcombank, Techcombank,...</p>
                    </div>
                    <div class="method-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                </a>

                <!-- MoMo -->
                <a href="momo-redirect.php?order_id=<?= $order_id ?>" class="method-card">
                    <div class="method-icon" style="background: linear-gradient(135deg, #a50064 0%, #d60070 100%);">
                        <i class="fa-solid fa-mobile-screen"></i>
                    </div>
                    <div class="method-info">
                        <h4>Ví điện tử MoMo</h4>
                        <p>Thanh toán siêu tốc qua ứng dụng MoMo QR Code</p>
                    </div>
                    <div class="method-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                </a>

                <!-- VNPay -->
                <a href="vnpay-redirect.php?order_id=<?= $order_id ?>" class="method-card">
                    <div class="method-icon" style="background: linear-gradient(135deg, #005baa 0%, #0088ff 100%);">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <div class="method-info">
                        <h4>Cổng thanh toán VNPay</h4>
                        <p>Hỗ trợ thẻ ATM nội địa, Thẻ quốc tế Visa/Master, VNPay-QR</p>
                    </div>
                    <div class="method-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                </a>

                <!-- ZaloPay -->
                <a href="zalopay-redirect.php?order_id=<?= $order_id ?>" class="method-card">
                    <div class="method-icon" style="background: linear-gradient(135deg, #0068ff 0%, #0284c7 100%);">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="method-info">
                        <h4>Ví ZaloPay</h4>
                        <p>Thanh toán an toàn, nhận ưu đãi hấp dẫn từ ZaloPay</p>
                    </div>
                    <div class="method-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                </a>

                <!-- COD -->
                <div onclick="openCodModal()" class="method-card">
                    <div class="method-icon" style="background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <div class="method-info">
                        <h4>Thanh toán khi nhận hàng (COD)</h4>
                        <p>Nhận hàng kiểm tra xong mới thanh toán cho nhân viên giao hàng</p>
                    </div>
                    <div class="method-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- COD Confirmation Modal -->
<div class="modal-overlay" id="codModal">
    <div class="modal-box">
        <div class="modal-icon">
            <i class="fa-solid fa-truck-fast"></i>
        </div>
        <div class="modal-title">Xác nhận thanh toán COD</div>
        <div class="modal-desc">
            Bạn chọn thanh toán khi nhận hàng cho đơn hàng <strong>#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></strong>.<br>
            Nhân viên BuildPC sẽ liên hệ xác nhận và giao hàng cho bạn trong thời gian sớm nhất.
        </div>
        <div class="modal-actions">
            <button class="btn-modal btn-cancel" onclick="closeCodModal()">Hủy</button>
            <button class="btn-modal btn-confirm" onclick="processCodPayment()">Đồng ý xác nhận</button>
        </div>
    </div>
</div>

<script>
function openCodModal() {
    document.getElementById('codModal').classList.add('active');
}

function closeCodModal() {
    document.getElementById('codModal').classList.remove('active');
}

function processCodPayment() {
    window.location.href = 'payment-detail.php?order_id=<?= $order_id ?>&method=cod&action=confirm_cod';
}
</script>
</body>
</html>