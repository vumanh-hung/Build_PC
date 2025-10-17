<?php
require_once 'db.php';
$pdo = getPDO();
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        echo json_encode($pdo->query("SELECT * FROM categories")->fetchAll());
        break;
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
        $stmt->execute([$data['name'], $data['slug'], $data['description'] ?? null]);
        echo json_encode(['message' => 'Thêm danh mục thành công']);
        break;
}
?>
