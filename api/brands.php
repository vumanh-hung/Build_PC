<?php
require_once '../db.php';
$pdo = getPDO();
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        echo json_encode($pdo->query("SELECT * FROM brands")->fetchAll());
        break;
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("INSERT INTO brands (name, slug) VALUES (?, ?)");
        $stmt->execute([$data['name'], $data['slug']]);
        echo json_encode(['message' => 'Thêm thương hiệu thành công']);
        break;
}
?>
