<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

try {
    $build_id = isset($_GET['build_id']) ? intval($_GET['build_id']) : 0;
    
    if (!$build_id || $build_id <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Build ID không hợp lệ'
        ]);
        exit;
    }
    
    $pdo = getPDO();
    
    // Check if build exists
    $stmt = $pdo->prepare("SELECT build_id FROM builds WHERE build_id = :build_id");
    $stmt->execute([':build_id' => $build_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'error' => 'Không tìm thấy cấu hình'
        ]);
        exit;
    }
    
    // Get build items
    $stmt = $pdo->prepare("
        SELECT 
            bi.build_item_id,
            bi.build_id,
            bi.product_id,
            bi.quantity,
            p.name as product_name,
            p.price,
            c.name as category_name
        FROM build_items bi
        LEFT JOIN products p ON bi.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE bi.build_id = :build_id
        ORDER BY bi.build_item_id ASC
    ");
    
    $stmt->execute([':build_id' => $build_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items)
    ]);
    
} catch (PDOException $e) {
    error_log('❌ Get build items error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi database: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('❌ Get build items error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}