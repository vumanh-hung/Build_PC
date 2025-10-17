<?php
require_once 'db.php';
$pdo = getPDO();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT oi.*, p.name AS product_name FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.product_id";
    echo json_encode($pdo->query($sql)->fetchAll());
}
?>
