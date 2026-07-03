<?php
session_start();
require_once dirname(dirname(__FILE__)) . '/db.php';
require_once dirname(dirname(__FILE__)) . '/functions.php';

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

// ✅ Lấy thông tin đơn hàng
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

// Thông tin ngân hàng nhận thanh toán
$selected_bank = [
    'bank_id' => 'vietcombank',
    'code' => 'VCB',
    'name' => 'Vietcombank (Ngân Hàng TMCP Ngoại Thương Việt Nam)',
    'account' => '1029384756',
    'holder' => 'CONG TY CP BUILD PC VIETNAM',
    'branch' => 'Chi nhánh TP. Hồ Chí Minh'
];

$transfer_content = "BUILDPC" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
$vietqr_url = "https://img.vietqr.io/image/VCB-" . $selected_bank['account'] . "-compact2.png?amount=" . intval($order['total_price']) . "&addInfo=" . urlencode($transfer_content) . "&accountName=" . urlencode($selected_bank['holder']);

// ✅ Xử lý xác nhận thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_payment'])) {
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    if (empty($transaction_id)) {
        $transaction_id = 'FT' . date('YmdHis') . rand(100, 999);
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET order_status = 'paid', updated_at = NOW()
            WHERE order_id = ? AND user_id = ?
        ");
        $stmt->execute([$order_id, $user_id]);
        
        $stmt = $pdo->prepare("
            UPDATE order_shipping 
            SET payment_method = 'bank_transfer'
            WHERE order_id = ?
        ");
        $stmt->execute([$order_id]);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payment_history (order_id, user_id, payment_method, transaction_id, amount, status, created_at)
                VALUES (?, ?, 'bank_transfer', ?, ?, 'completed', NOW())
            ");
            $stmt->execute([$order_id, $user_id, $transaction_id, $order['total_price']]);
        } catch (PDOException $e) {}
        
        $pdo->commit();
        $verify_message = 'Xác nhận thanh toán thành công! Hệ thống đang xử lý đơn hàng.';
        header('Refresh: 2; url=order-detail.php?order_id=' . $order_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        $verify_error = 'Có lỗi xảy ra: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chuyển Khoản VietQR - BuildPC.vn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --card-bg: rgba(255, 255, 255, 0.98);
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
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
            margin-bottom: 25px;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 18px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-4px);
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 30px;
            background: var(--card-bg);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        }

        @media (max-width: 850px) {
            .main-grid { grid-template-columns: 1fr; }
        }

        .qr-card {
            background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
            border: 2px solid #dbeafe;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr-header {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qr-image-wrapper {
            background: white;
            padding: 15px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.15);
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }

        .qr-image-wrapper img {
            width: 230px;
            height: 230px;
            object-fit: contain;
            display: block;
        }

        .timer-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 10px 18px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .bank-details-box {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .box-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .box-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 25px;
        }

        .info-row {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-label {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .info-val {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .copy-btn {
            background: #e0e7ff;
            color: #4338ca;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .copy-btn:hover {
            background: #c7d2fe;
        }

        .action-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #e2e8f0;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1.5px solid #cbd5e1;
            font-size: 14px;
            font-weight: 600;
            outline: none;
            transition: border 0.2s ease;
        }

        .input-group input:focus {
            border-color: var(--primary);
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 25px -5px rgba(37, 99, 235, 0.5);
        }

        .alert {
            padding: 14px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* Toast notifications */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #0f172a;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            z-index: 9999;
        }
        .toast.show { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <a href="payment-methods.php?order_id=<?= $order_id ?>" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Đổi phương thức thanh toán
        </a>
    </div>

    <?php if ($verify_message): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $verify_message ?></div>
    <?php endif; ?>
    <?php if ($verify_error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $verify_error ?></div>
    <?php endif; ?>

    <div class="main-grid">
        <!-- QR Display -->
        <div class="qr-card">
            <div class="qr-header">
                <i class="fa-solid fa-qrcode"></i> Quét Mã VietQR
            </div>
            <div class="qr-image-wrapper">
                <img src="<?= $vietqr_url ?>" alt="VietQR Thanh toán">
            </div>
            <div class="timer-box">
                <i class="fa-solid fa-clock"></i> Hạn thanh toán: <span id="countdown">14:59</span>
            </div>
        </div>

        <!-- Bank Details & Action -->
        <div class="bank-details-box">
            <div>
                <div class="box-title">Thông Tin Chuyển Khoản</div>
                <div class="box-subtitle">Mở app ngân hàng bất kỳ để quét mã QR hoặc chuyển khoản thủ công</div>

                <div class="info-row">
                    <span class="info-label">Ngân hàng:</span>
                    <span class="info-val"><?= $selected_bank['name'] ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Số tài khoản:</span>
                    <div class="info-val">
                        <span><?= $selected_bank['account'] ?></span>
                        <button class="copy-btn" onclick="copyText('<?= $selected_bank['account'] ?>', 'Số tài khoản')">Coppy</button>
                    </div>
                </div>

                <div class="info-row">
                    <span class="info-label">Chủ tài khoản:</span>
                    <span class="info-val"><?= $selected_bank['holder'] ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Số tiền:</span>
                    <div class="info-val" style="color: #2563eb; font-size: 17px;">
                        <span><?= formatPrice($order['total_price']) ?></span>
                        <button class="copy-btn" onclick="copyText('<?= intval($order['total_price']) ?>', 'Số tiền')">Coppy</button>
                    </div>
                </div>

                <div class="info-row" style="background: #f0fdf4; border-color: #bbf7d0;">
                    <span class="info-label" style="color: #166534;">Nội dung CK:</span>
                    <div class="info-val" style="color: #15803d;">
                        <span id="memoVal"><?= $transfer_content ?></span>
                        <button class="copy-btn" style="background: #dcfce7; color: #15803d;" onclick="copyText('<?= $transfer_content ?>', 'Nội dung chuyển khoản')">Coppy</button>
                    </div>
                </div>
            </div>

            <form method="POST" class="action-form">
                <div class="input-group">
                    <label>Mã giao dịch (Nếu có):</label>
                    <input type="text" name="transaction_id" placeholder="VD: FT260629837 (Tự động tạo nếu để trống)">
                </div>
                <button type="submit" name="verify_payment" class="btn-submit">
                    <i class="fa-solid fa-circle-check"></i> Tôi Đã Chuyển Khoản Xong
                </button>
            </form>
        </div>
    </div>
</div>

<div class="toast" id="toast">Đã sao chép vào bộ nhớ tạm!</div>

<script>
function copyText(text, label) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Đã sao chép ' + label + '!');
    });
}

function showToast(msg) {
    const t = document.getElementById('toast');
    t.innerText = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

// Countdown timer 15 mins
let time = 15 * 60;
const timerEl = document.getElementById('countdown');
setInterval(() => {
    if (time <= 0) return;
    time--;
    const m = Math.floor(time / 60).toString().padStart(2, '0');
    const s = (time % 60).toString().padStart(2, '0');
    timerEl.innerText = `${m}:${s}`;
}, 1000);
</script>
</body>
</html>