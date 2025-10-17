<?php
require_once 'db.php';
$pdo = getPDO();
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.user_id";
    echo json_encode($pdo->query($sql)->fetchAll());
}
?>
