<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';

// ===== Kiểm tra đăng nhập & quyền =====
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo "<h3 style='color:red; text-align:center; margin-top:50px'>🚫 Bạn không có quyền truy cập trang này!</h3>";
    exit;
}

// ===== CSRF =====
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$message = '';
$message_type = '';

// ===== XỬ LÝ CẬP NHẬT TRẠNG THÁI =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $csrf) {
        $message = '❌ Token không hợp lệ!';
        $message_type = 'error';
    } else {
        $order_id = intval($_POST['order_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        $allowed_status = ['pending', 'paid', 'shipping', 'completed', 'cancelled'];
        if (!in_array($status, $allowed_status)) {
            $message = '❌ Trạng thái không hợp lệ!';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?");
                $stmt->execute([$status, $order_id]);
                $message = "✅ Cập nhật trạng thái đơn hàng #$order_id thành công!";
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = '❌ Lỗi khi cập nhật: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// ===== LẤY DANH SÁCH ĐƠN HÀNG =====
try {
    $stmt = $pdo->query("
        SELECT o.*, 
               o.order_status AS status,
               o.total_price AS total_amount,
               u.username, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
    $message = '❌ Lỗi truy vấn dữ liệu: ' . $e->getMessage();
    $message_type = 'error';
}

// Thống kê
$total_orders = count($orders);
$total_revenue = array_sum(array_column($orders, 'total_amount'));
$pending_count = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'pending'));
$completed_count = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'completed'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {margin:0; padding:0; box-sizing:border-box;}
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: auto;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #2d3748;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 i {
            color: #667eea;
            font-size: 36px;
        }
        
        .breadcrumb {
            color: #718096;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .stat-card.blue::before {background: linear-gradient(180deg, #667eea, #764ba2);}
        .stat-card.green::before {background: linear-gradient(180deg, #48bb78, #38a169);}
        .stat-card.orange::before {background: linear-gradient(180deg, #ed8936, #dd6b20);}
        .stat-card.purple::before {background: linear-gradient(180deg, #9f7aea, #805ad5);}
        
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-card.blue .icon {background: linear-gradient(135deg, #667eea20, #764ba220); color: #667eea;}
        .stat-card.green .icon {background: linear-gradient(135deg, #48bb7820, #38a16920); color: #48bb78;}
        .stat-card.orange .icon {background: linear-gradient(135deg, #ed893620, #dd6b2020); color: #ed8936;}
        .stat-card.purple .icon {background: linear-gradient(135deg, #9f7aea20, #805ad520); color: #9f7aea;}
        
        .stat-card .label {
            color: #718096;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-card .value {
            color: #2d3748;
            font-size: 28px;
            font-weight: 700;
        }
        
        /* Message */
        .message {
            padding: 18px 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: none;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {transform: translateY(-20px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        
        .message.show {display: flex;}
        .message.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .message.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .message i {font-size: 20px;}
        
        /* Orders Section */
        .orders-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-header h2 {
            color: #2d3748;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-box {
            position: relative;
            flex: 1;
            max-width: 300px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }
        
        /* Table */
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        table th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        table td {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
            font-size: 14px;
        }
        
        table tbody tr {
            transition: all 0.2s ease;
        }
        
        table tbody tr:hover {
            background: #f7fafc;
            transform: scale(1.01);
        }
        
        .order-id {
            color: #667eea;
            font-weight: 700;
            font-size: 15px;
        }
        
        .customer-info strong {
            color: #2d3748;
            display: block;
            margin-bottom: 3px;
        }
        
        .customer-info small {
            color: #a0aec0;
            font-size: 12px;
        }
        
        .amount {
            color: #2d3748;
            font-weight: 700;
            font-size: 16px;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .status-pending {background: #fff3cd; color: #856404;}
        .status-processing {background: #cce5ff; color: #004085;}
        .status-shipped {background: #d1ecf1; color: #0c5460;}
        .status-completed {background: #d4edda; color: #155724;}
        .status-cancelled {background: #f8d7da; color: #721c24;}
        
        /* Select */
        select {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #4a5568;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        select:hover {
            border-color: #cbd5e0;
        }
        
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-view:hover {
            background: linear-gradient(135deg, #5568d3, #6a3f91);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        /* No Orders */
        .no-orders {
            text-align: center;
            padding: 80px 20px;
            color: #a0aec0;
        }
        
        .no-orders i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #cbd5e0;
        }
        
        .no-orders h3 {
            color: #718096;
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        /* Responsive */
        @media(max-width: 768px) {
            body {padding: 15px;}
            .header {padding: 20px;}
            .header h1 {font-size: 24px;}
            .stats-grid {grid-template-columns: 1fr;}
            .section-header {flex-direction: column; align-items: stretch;}
            .search-box {max-width: 100%;}
            table {font-size: 12px;}
            table th, table td {padding: 12px 10px;}
            .action-buttons {flex-direction: column;}
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% {transform: rotate(0deg);}
            100% {transform: rotate(360deg);}
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-shopping-cart"></i> Quản lý Đơn hàng</h1>
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a> 
                <i class="fas fa-chevron-right" style="font-size:10px; margin:0 5px;"></i> 
                Đơn hàng
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="label">Tổng đơn hàng</div>
                <div class="value"><?= number_format($total_orders) ?></div>
            </div>
            <div class="stat-card green">
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="label">Tổng doanh thu</div>
                <div class="value"><?= number_format($total_revenue, 0, ',', '.') ?>₫</div>
            </div>
            <div class="stat-card orange">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="label">Chờ xử lý</div>
                <div class="value"><?= $pending_count ?></div>
            </div>
            <div class="stat-card purple">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div class="label">Hoàn thành</div>
                <div class="value"><?= $completed_count ?></div>
            </div>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
            <div class="message show <?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= $message ?></span>
            </div>
        <?php endif; ?>

        <!-- Orders Section -->
        <div class="orders-section">
            <div class="section-header">
                <h2><i class="fas fa-list"></i> Danh sách Đơn hàng</h2>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Tìm kiếm đơn hàng...">
                </div>
            </div>

            <?php if (count($orders) > 0): ?>
            <div class="table-responsive">
                <table id="ordersTable">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Mã ĐH</th>
                            <th><i class="fas fa-user"></i> Khách hàng</th>
                            <th><i class="fas fa-money-bill-wave"></i> Tổng tiền</th>
                            <th><i class="fas fa-info-circle"></i> Trạng thái</th>
                            <th><i class="fas fa-calendar"></i> Ngày tạo</th>
                            <th><i class="fas fa-cog"></i> Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="order-id">#<?= $o['order_id'] ?></td>
                            <td class="customer-info">
                                <strong><?= htmlspecialchars($o['username'] ?? 'Khách vãng lai') ?></strong>
                                <small><?= htmlspecialchars($o['email'] ?? 'Không có email') ?></small>
                            </td>
                            <td class="amount"><?= number_format($o['total_amount'], 0, ',', '.') ?>₫</td>
                            <td>
                                <span class="status-badge status-<?= $o['status'] ?? 'pending' ?>">
                                    <?php
                                    $icons = [
                                        'pending'   => 'clock',
                                        'paid'      => 'credit-card',
                                        'shipping'  => 'shipping-fast',
                                        'completed' => 'check-circle',
                                        'cancelled' => 'times-circle'
                                    ];
                                    $labels = [
                                        'pending'   => 'Chờ xử lý',
                                        'paid'      => 'Đã thanh toán',
                                        'shipping'  => 'Đang giao hàng',
                                        'completed' => 'Hoàn tất',
                                        'cancelled' => 'Đã hủy'
                                    ];
                                    $status = $o['status'] ?? 'pending';
                                    ?>
                                    <i class="fas fa-<?= $icons[$status] ?? 'clock' ?>"></i>
                                    <?= $labels[$status] ?? 'Không rõ' ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="pending"   <?= $status=='pending'  ?'selected':'' ?>>⏳ Chờ xử lý</option>
                                            <option value="paid"      <?= $status=='paid'     ?'selected':'' ?>>💳 Đã thanh toán</option>
                                            <option value="shipping"  <?= $status=='shipping' ?'selected':'' ?>>🚚 Đang giao hàng</option>
                                            <option value="completed" <?= $status=='completed'?'selected':'' ?>>✅ Hoàn tất</option>
                                            <option value="cancelled" <?= $status=='cancelled'?'selected':'' ?>>❌ Đã hủy</option>
                                        </select>
                                    </form>
                                    <a href="order_detail.php?id=<?= $o['order_id'] ?>" class="btn btn-view">
                                        <i class="fas fa-eye"></i> Chi tiết
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-inbox"></i>
                    <h3>Chưa có đơn hàng nào</h3>
                    <p>Các đơn hàng sẽ xuất hiện ở đây khi khách hàng đặt mua</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Message auto hide
        const msg = document.querySelector('.message');
        if (msg && msg.classList.contains('show')) {
            setTimeout(() => {
                msg.style.opacity = '0';
                msg.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => msg.style.display = 'none', 300);
            }, 5000);
        }

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('ordersTable');
        
        if (searchInput && table) {
            searchInput.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                }
            });
        }

        // Form submit animation
        document.querySelectorAll('select[name="status"]').forEach(select => {
            select.addEventListener('change', function() {
                this.style.opacity = '0.5';
                this.disabled = true;
                const loader = document.createElement('span');
                loader.className = 'loading';
                this.parentElement.appendChild(loader);
            });
        });
    </script>
</body>
</html>