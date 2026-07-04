<?php

/**
 * admin/promotions_manage.php - Quản lý khuyến mãi (Flash Sale / Giảm giá)
 * Sử dụng các helper CRUD trong functions.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: ../page/login.php');
    exit;
}

// Kiểm tra quyền admin
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "<h3 style='color:red; text-align:center; margin-top:50px'>Ban khong co quyen truy cap trang nay!</h3>";
    exit;
}

// CSRF Token
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$message = '';
$message_type = '';

// Dữ liệu để pre-fill form khi chỉnh sửa
$edit_promo = null;

// XỬ LÝ POST (THÊM / SỬA / XÓA / BẬT-TẮT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if (empty($_POST['csrf']) || !hash_equals($csrf, $_POST['csrf'])) {
        $message = 'Token khong hop le';
        $message_type = 'error';
    } else {
        $action = $_POST['action'];

        $data = [
            'product_id'       => intval($_POST['product_id'] ?? 0),
            'promotion_name'   => trim($_POST['promotion_name'] ?? ''),
            'promotion_type'   => $_POST['promotion_type'] ?? 'flash_sale',
            'discount_type'    => $_POST['discount_type'] ?? 'percent',
            'discount_percent' => $_POST['discount_percent'] ?? 0,
            'discount_amount'  => $_POST['discount_amount'] ?? 0,
            'start_date'       => $_POST['start_date'] ?? '',
            'end_date'         => $_POST['end_date'] ?? '',
            'max_quantity'     => $_POST['max_quantity'] ?? 0,
            'is_active'        => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($action === 'add') {
            $result = createPromotion($data);
            $message = $result['success'] ? "Them khuyen mai thanh cong!" : $result['error'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($action === 'update') {
            $promotion_id = intval($_POST['promotion_id'] ?? 0);
            $result = updatePromotion($promotion_id, $data);
            $message = $result['success'] ? "Cap nhat khuyen mai thanh cong!" : $result['error'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($action === 'delete') {
            $promotion_id = intval($_POST['promotion_id'] ?? 0);
            $result = deletePromotion($promotion_id);
            $message = $result['success'] ? "Da xoa khuyen mai!" : $result['error'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($action === 'toggle') {
            $promotion_id = intval($_POST['promotion_id'] ?? 0);
            $result = togglePromotionStatus($promotion_id);
            $message = $result['success'] ? "Da doi trang thai khuyen mai!" : $result['error'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}

// Nếu có ?edit=ID => lấy dữ liệu điền vào form
if (isset($_GET['edit'])) {
    $edit_promo = getPromotionById(intval($_GET['edit']));
}

// Lấy dữ liệu hiển thị
$promotions = getAllPromotions();
$products = getAllProducts();

// Giá trị discount_type hiện tại (để chọn option)
$cur_discount_type = $edit_promo['discount_type'] ?? 'percent';
$cur_promotion_type = $edit_promo['promotion_type'] ?? 'flash_sale';
$start_val = $edit_promo ? date('Y-m-d\TH:i', strtotime($edit_promo['start_date'])) : '';
$end_val = $edit_promo ? date('Y-m-d\TH:i', strtotime($edit_promo['end_date'])) : '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Khuyến mãi - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container { max-width: 1250px; margin: 0 auto; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .header h1 {
            color: #667eea;
            font-size: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-actions { display: flex; gap: 10px; align-items: center; }

        .promo-count {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
        }

        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border: 2px solid #667ea;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .back-link:hover { background: #667eea; color: #fff; }

        .message {
            pading: 16px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 5px solid;
            box-shadow: 0 5px 15px rgba(0, 0, 0.1);
        }

        .message.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left-color: #28a745;
        }

        .message.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left-color: #f44336;
        }

        .card {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0.2);
            margin-bottom: 30px;
        }

        .card h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group { margin-bottom: 5px; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-row input { width: auto; }

        .btn-group { display: flex; gap: 12px; margin-top: 25px; }

        .btn {
            padding: 13px 28px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-cancel { background: #e0e0e0; color: #333; }
        .btn-cancel:hover { background: #d0d0d0; }

        .table-responsive { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        table thead th {
            background: linear-gradient(135deg, #667ea, #764ba2);
            color: white;
            padding: 14px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
        }

        table tbody tr { border-bottom: 1px solid #f0f0f0; transition: all 0.2s; }
        table tbody tr:hover { background: #f9f9; }
        table td { padding: 14px; font-size: 13px; vertical-align: middle; }

        .badge {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .badge-discount { background: #e8ecff; color: #4959d0; }

        .actions { display: flex; gap: 6px; }

        .btn-sm {
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 12px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-edit { background: linear-gradient(135deg, #1a73e8, #0d47a1); color: #fff; }
        .btn-edit:hover { transform: translateY(-2px); }

        .btn-toggle { background: linear-gradient(135deg, #ff9800, #f57c00); color: #fff; }
        .btn-toggle:hover { transform: translateY(-2px); }

        .btn-del { background: linear-gradient(135deg, #f44336, #d32f2f); color: #fff; }
        .btn-del:hover { transform: translateY(-2px); }

        .no-data { text-align: center; padding: 50px 20px; color: #999; }
        .no-data i { font-size: 46px; color: #ddd; display: block; margin-bottom: 15px; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bolt"></i> Quản lý Khuyến mãi</h1>
            <div class="header-actions">
                <span class="promo-count"><i class="fas fa-database"></i> <?= count($promotions) ?> khuyến mãi</span>
                <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Dashboard</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- FORM THÊM / SỬA -->
        <div class="card">
            <h2>
                <i class="fas fa-<?= $edit_promo ? 'pen' : 'plus-circle' ?>"></i>
                <?= $edit_promo ? 'Chỉnh sửa khuyến mãi #' . (int)$edit_promo['promotion_id'] : 'Thêm khuyến mãi mới' ?>
            </h2>

            <form method="POST">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="<?= $edit_promo ? 'update' : 'add' ?>">
                <?php if ($edit_promo): ?>
                    <input type="hidden" name="promotion_id" value="<?= (int)$edit_promo['promotion_id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                <div class="form-group">
                        <label><i class="fas fa-box"></i> Sản phẩm áp dụng</label>
                        <select name="product_id" required>
                            <option value="">-- Chọn sản phẩm --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['product_id'] ?>" <?= ($edit_promo && $edit_promo['product_id'] == $p['product_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?> (<?= formatPriceVND($p['price']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Tên chương trình</label>
                <input type="text" name="promotion_name" value="<?= htmlspecialchars($edit_promo['promotion_name'] ?? '') ?>" placeholder="VD: Flash Sale cuối tuần" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-layer-group"></i> Loại chương trình</label>
                        <select name="promotion_type">
                <?php
                            $types = ['flash_sale' => 'Flash Sale', 'discount' => 'Giảm giá', 'gift' => 'Quà tặng', 'bundle' => 'Combo'];
                            foreach ($types as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $cur_promotion_type === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-percent"></i> Hình thức giảm</label>
                        <select name="discount_type" id="discountType" onchange="toggleDiscountFields()">
                <option value="percent" <?= $cur_discount_type === 'percent' ? 'selected' : '' ?>>Theo phần trăm (%)</option>
                            <option value="fixed" <?= $cur_discount_type === 'fixed' ? 'selected' : '' ?>>Theo số tiền (₫)</option>
                        </select>
                    </div>

                    <div class="form-group" id="percentField">
                        <label><i class="fas fa-percentage"></i> Phần trăm giảm (1-100)</label>
                        <input type="number" name="discount_percent" min="0" max="100" step="0.01" value="<?= htmlspecialchars($edit_promo['discount_percent'] ?? '') ?>" placeholder="VD: 15">
                    </div>

                    <div class="form-group" id="amountField">
                        <label><i class="fas fa-money-bill-wave"></i> Số tiền giảm (₫)</label>
                        <input type="number" name="discount_amount" min="0" step="1000" value="<?= htmlspecialchars($edit_promo['discount_amount'] ?? '') ?>" placeholder="VD: 5000">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-calendar-plus"></i> Thời gian bắt đầu</label>
                        <input type="datetime-local" name="start_date" value="<?= htmlspecialchars($start_val) ?>" required>
                    </div>

                <div class="form-group">
                        <label><i class="fas fa-calendar-check"></i> Thời gian kết thúc</label>
                        <input type="datetime-local" name="end_date" value="<?= htmlspecialchars($end_val) ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-boxes-stacked"></i> Số lượng tối đa (0 = không giới hạn)</label>
                        <input type="number" name="max_quantity" min="0" value="<?= htmlspecialchars($edit_promo['max_quantity'] ?? 0) ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Trạng thái</label>
                        <div class="checkbox-row">
                <input type="checkbox" name="is_active" id="is_active" value="1" <?= (!$edit_promo || (int)$edit_promo['is_active'] === 1) ? 'checked' : '' ?>>
                            <label for="is_active" style="margin:0; font-weight:500;">Kích hoạt khuyến mãi</label>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-check"></i> <?= $edit_promo ? 'Cập nhật' : 'Thêm khuyến mãi' ?>
                    </button>
                    <?php if ($edit_promo): ?>
                        <a href="promotions_manage.php" class="btn btn-cancel"><i class="fas fa-times"></i> Hủy</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- DANH SÁCH -->
        <div class="card">
            <h2><i class="fas fa-list"></i> Danh sách Khuyến mãi</h2>

            <?php if (count($promotions) > 0): ?>
                <div class="table-responsive">
                    <table>
                <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên</th>
                                <th>Sản phẩm</th>
                                <th>Giảm</th>
                                <th>Thời gian</th>
                <th>SL</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($promotions as $pr): ?>
                                <?php
                                $is_percent = ($pr['discount_type'] ?? 'percent') === 'percent';
                                $discount_display = $is_percent
                                    ? rtrim(rtrim(number_format((float)$pr['discount_percent'], 2), '0'), '.') . '%'
                                    : formatPriceVND($pr['discount_amount']);
                                $now = time();
                                $is_running = (int)$pr['is_active'] === 1
                                    && strtotime($pr['start_date']) <= $now
                                    && strtotime($pr['end_date']) >= $now;
                ?>
                                <tr>
                                    <td>#<?= (int)$pr['promotion_id'] ?></td>
                                    <td>
                        <strong><?= htmlspecialchars($pr['promotion_name']) ?></strong><br>
                                        <span class="badge badge-discount"><?= htmlspecialchars($pr['promotion_type']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($pr['product_name'] ?? '(Không xác định)') ?></td>
                                    <td><strong style="color:#f44336;">-<?= $discount_display ?></strong></td>
                                    <td>
                        <?= date('d/m/Y H:i', strtotime($pr['start_date'])) ?><br>
                                        &rarr; <?= date('d/m/Y H:i', strtotime($pr['end_date'])) ?>
                                    </td>
                                    <td><?= (int)$pr['used_quantity'] ?>/<?= ((int)$pr['max_quantity'] === 0) ? 'KGH' : (int)$pr['max_quantity'] ?></td>
                                <td>
                                        <?php if ((int)$pr['is_active'] === 1): ?>
                                            <span class="badge badge-active"><?= $is_running ? 'Đang chạy' : 'Đã bật' ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Đã tắt</span>
                                <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="promotions_manage.php?edit=<?= (int)$pr['promotion_id'] ?>" class="btn-sm btn-edit" title="Sửa">
                                                <i class="fas fa-pen"></i>
                            </a>
                                <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="promotion_id" value="<?= (int)$pr['promotion_id'] ?>">
                                <button type="submit" class="btn-sm btn-toggle" title="Bật/Tắt">
                                                <i class="fas fa-power-off"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa khuyến mãi này?');">
                                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="promotion_id" value="<?= (int)$pr['promotion_id'] ?>">
                                                <button type="submit" class="btn-sm btn-del" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>Chưa có khuyến mãi nào. Thêm chương trình đầu tiên ở form phía trên.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleDiscountFields() {
            const type = document.getElementById('discountType').value;
            document.getElementById('percentField').style.display = (type === 'percent') ? 'block' : 'none';
            document.getElementById('amountField').style.display = (type === 'fixed') ? 'block' : 'none';
        }
        toggleDiscountFields();
    </script>
</body>

</html>