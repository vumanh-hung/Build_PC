<?php
require_once '../db.php';
$pdo = getPDO();
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT bi.*, p.name AS product_name FROM build_items bi
            LEFT JOIN products p ON bi.product_id = p.product_id";
    echo json_encode($pdo->query($sql)->fetchAll());
}
?>
