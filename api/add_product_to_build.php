<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$build_id = (int)($data['build_id'] ?? 0);
$product_id = (int)($data['product_id'] ?? 0);

if (!$build_id || !$product_id) {
    echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
    exit;
}

try {
    // Kiểm tra sản phẩm có tồn tại không
    $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Sản phẩm không tồn tại');
    }
    
    // Thêm vào build_items
    $stmt = $pdo->prepare("INSERT INTO build_items (build_id, product_id, quantity) VALUES (?, ?, 1)");
    $stmt->execute([$build_id, $product_id]);
    
    // Cập nhật total_price
    $stmt = $pdo->prepare("
        SELECT SUM(p.price) as total FROM build_items bi
        JOIN products p ON bi.product_id = p.product_id
        WHERE bi.build_id = ?
    ");
    $stmt->execute([$build_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'] ?? 0;
    
    $pdo->prepare("UPDATE builds SET total_price = ? WHERE build_id = ?")
        ->execute([$total, $build_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>