<?php
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

header('Content-Type: application/json; charset=utf-8');

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Chỉ chấp nhận phương thức POST']);
    exit;
}

// Đọc dữ liệu JSON từ body
$data = json_decode(file_get_contents('php://input'), true);

// Lấy dữ liệu đầu vào
$build_id = $data['build_id'] ?? 0;
$name = trim($data['name'] ?? '');
$parts = $data['parts'] ?? [];

if (!$build_id) {
    echo json_encode(['error' => 'Thiếu ID cấu hình']);
    exit;
}

// Nếu không có linh kiện nào, coi như lỗi
if (!is_array($parts) || count($parts) === 0) {
    echo json_encode(['error' => 'Chưa chọn linh kiện nào']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Cập nhật tên cấu hình (nếu có thay đổi)
    if ($name !== '') {
        $stmt = $pdo->prepare("UPDATE builds SET name = ?, updated_at = NOW() WHERE build_id = ?");
        $stmt->execute([$name, $build_id]);
    }

    // Xóa linh kiện cũ
    $pdo->prepare("DELETE FROM build_items WHERE build_id = ?")->execute([$build_id]);

    // Chuẩn bị câu lệnh thêm mới
    $stmtItem = $pdo->prepare("INSERT INTO build_items (build_id, product_id, quantity) VALUES (?, ?, 1)");

    $total = 0;
    $priceStmt = $pdo->prepare("SELECT price FROM products WHERE product_id = ?");

    foreach ($parts as $pid) {
        if (!empty($pid)) {
            // Lưu linh kiện vào build_items
            $stmtItem->execute([$build_id, $pid]);

            // Lấy giá sản phẩm
            $priceStmt->execute([$pid]);
            $price = (float)$priceStmt->fetchColumn();
            $total += $price;
        }
    }

    // Cập nhật tổng tiền
    $stmtUpdate = $pdo->prepare("UPDATE builds SET total_price = ?, updated_at = NOW() WHERE build_id = ?");
    $stmtUpdate->execute([$total, $build_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '✅ Đã cập nhật cấu hình thành công!',
        'total' => $total
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => '❌ Lỗi cập nhật: ' . $e->getMessage()]);
}
