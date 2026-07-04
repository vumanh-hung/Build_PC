<?php
require_once '../db.php';
$pdo = getPDO();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT * FROM users");
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['username'], $data['email'], $data['password_hash'])) {
            echo json_encode(['error' => 'Thiếu thông tin bắt buộc']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['username'], $data['email'], $data['password_hash'], $data['full_name'] ?? null, $data['role'] ?? 'customer']);
        echo json_encode(['message' => 'Thêm người dùng thành công']);
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $data);
        $id = $data['user_id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        echo json_encode(['message' => 'Xóa người dùng thành công']);
        break;
}
?>
