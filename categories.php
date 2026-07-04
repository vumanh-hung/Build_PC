<?php
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
        $stmt->execute([$data['name'], $data['slug'], $data['description'] ?? null]);
        echo json_encode(['message' => 'Thêm danh mục thành công']);
        exit;
    }

    // ✅ Chỉ trả về một JSON duy nhất cho GET
    $stmt = $pdo->query("SELECT category_id, name, slug, description FROM categories ORDER BY category_id ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($categories, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi truy vấn CSDL: ' . $e->getMessage()]);
}
?>
