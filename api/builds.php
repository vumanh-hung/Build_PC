<?php
require_once 'db.php';
$pdo = getPDO();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT b.*, u.username FROM builds b LEFT JOIN users u ON b.user_id = u.user_id";
    echo json_encode($pdo->query($sql)->fetchAll());
}
?>
