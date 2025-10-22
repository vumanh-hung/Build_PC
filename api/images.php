<?php
require_once '../db.php';
$pdo = getPDO();
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        echo json_encode($pdo->query("SELECT * FROM images")->fetchAll());
        break;
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("INSERT INTO images (product_id, image_path, alt_text) VALUES (?, ?, ?)");
        $stmt->execute([$data['product_id'], $data['image_path'], $data['alt_text']]);
        echo json_encode(['message' => 'Thêm ảnh phụ thành công']);
        break;
}
?>
