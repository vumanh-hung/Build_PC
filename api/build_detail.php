<?php
require_once __DIR__ . '/../db.php';
$pdo = getPDO();
header('Content-Type: application/json; charset=utf-8');

$build_id = $_GET['id'] ?? 0;
if (!$build_id) {
    echo json_encode(['error' => 'Thiếu ID cấu hình']);
    exit;
}

try {
    // Lấy thông tin build
    $stmt = $pdo->prepare("
        SELECT b.*, u.username, u.full_name
        FROM builds b
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE b.build_id = ?
    ");
    $stmt->execute([$build_id]);
    $build = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$build) {
        echo json_encode(['error' => 'Không tìm thấy cấu hình']);
        exit;
    }

    // Lấy danh sách linh kiện
    $stmt = $pdo->prepare("
        SELECT bi.*, p.name AS product_name, p.price, c.name AS category_name
        FROM build_items bi
        JOIN products p ON bi.product_id = p.product_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE bi.build_id = ?
    ");
    $stmt->execute([$build_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'build' => $build,
        'items' => $items
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
