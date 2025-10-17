<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include_once("db.php");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT * FROM products");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $pdo->prepare("INSERT INTO products (TenSP, GiaBan, SoLuong, MaTH) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['TenSP'], $data['GiaBan'], $data['SoLuong'], $data['MaTH']]);
        echo json_encode(["status" => "success"]);
        break;

    case 'DELETE':
        parse_str($_SERVER['QUERY_STRING'], $query);
        $id = $query['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE MaSP = ?");
            $stmt->execute([$id]);
            echo json_encode(["status" => "deleted"]);
        } else {
            echo json_encode(["error" => "missing id"]);
        }
        break;
}
