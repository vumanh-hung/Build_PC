<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để lưu cấu hình']);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['items']) || !is_array($data['items'])) {
    echo json_encode(['status' => 'error', 'message' => 'Không có dữ liệu linh kiện']);
    exit;
}

$total_price = 0;
foreach ($data['items'] as $item) {
    $total_price += (float)($item['price'] ?? 0);
}

try {
    $pdo->beginTransaction();

    // 1️⃣ Thêm vào bảng builds
    $stmt = $pdo->prepare("INSERT INTO builds (user_id, name, total_price) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $data['name'] ?? 'Cấu hình mới', $total_price]);
    $build_id = $pdo->lastInsertId();

    // 2️⃣ Thêm chi tiết vào build_items
    $stmtItem = $pdo->prepare("INSERT INTO build_items (build_id, product_id, quantity) VALUES (?, ?, 1)");
    foreach ($data['items'] as $item) {
        $stmtItem->execute([$build_id, $item['product_id']]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Lưu cấu hình thành công!']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Lỗi khi lưu: ' . $e->getMessage()]);
}
