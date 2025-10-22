<?php
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();

    $stmt = $pdo->query("
        SELECT 
            p.product_id,
            p.name,
            p.slug,
            p.category_id,
            c.name AS category_name,   -- ✅ thêm dòng này
            p.brand_id,
            p.price,
            p.stock,
            p.description,
            p.main_image,
            p.created_at
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        ORDER BY p.product_id ASC
    ");

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($products, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi truy vấn CSDL: ' . $e->getMessage()]);
}
?>
