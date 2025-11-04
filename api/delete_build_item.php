<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'] ?? 0;
$build_id = $data['build_id'] ?? 0;

if (!$item_id || !$build_id) {
    echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
    exit;
}

try {
    // Xóa build_item
    $stmt = $pdo->prepare("DELETE FROM build_items WHERE build_item_id = ? AND build_id = ?");
    $stmt->execute([$item_id, $build_id]);
    
    // Cập nhật lại tổng giá của build
    $stmt = $pdo->prepare("
        SELECT SUM(p.price) as total
        FROM build_items bi
        JOIN products p ON bi.product_id = p.product_id
        WHERE bi.build_id = ?
    ");
    $stmt->execute([$build_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'] ?? 0;
    
    // Update lại total_price trong builds
    $pdo->prepare("UPDATE builds SET total_price = ? WHERE build_id = ?")
        ->execute([$total, $build_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>