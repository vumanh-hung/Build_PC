<?php
require_once __DIR__ . '/../db.php';
$pdo = getPDO();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ chấp nhận phương thức POST'
    ]);
    exit;
}

$build_id = $_POST['build_id'] ?? 0;
if (!$build_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu ID cấu hình'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Xóa chi tiết linh kiện
    $pdo->prepare("DELETE FROM build_items WHERE build_id = ?")->execute([$build_id]);

    // Xóa cấu hình chính
    $pdo->prepare("DELETE FROM builds WHERE build_id = ?")->execute([$build_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa cấu hình thành công'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi xóa: ' . $e->getMessage()
    ]);
}
exit;
