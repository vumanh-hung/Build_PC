<?php
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

header('Content-Type: application/json; charset=utf-8');

try {
    $category_id = $_GET['category_id'] ?? null;
    $keyword = $_GET['search'] ?? '';

    $sql = "
        SELECT 
            p.product_id,
            p.name,
            p.slug,
            p.category_id,
            c.name AS category_name,
            p.brand_id,
            b.name AS brand_name,
            p.price,
            p.stock,
            p.description,
            p.main_image,
            p.created_at
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE 1=1
    ";

    $params = [];
    if ($category_id) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
    }
    if (!empty($keyword)) {
        $sql .= " AND (p.name LIKE ? OR b.name LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
    }

    $sql .= " ORDER BY p.product_id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi truy vấn: ' . $e->getMessage()]);
}