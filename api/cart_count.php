<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once "../db.php";

$pdo = getPDO();

// Kiểm tra đăng nhập
$user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['user_id'] ?? 0);
if (!$user_id) {
    echo json_encode(['ok' => false, 'cart_count' => 0]);
    exit;
}

// Lấy cart_id
$stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cart) {
    echo json_encode(['ok' => true, 'cart_count' => 0]);
    exit;
}

// Tính tổng số lượng sản phẩm trong giỏ
$stmt = $pdo->prepare("SELECT SUM(quantity) AS total FROM cart_items WHERE cart_id = ?");
$stmt->execute([$cart['id']]);
$count = (int)($stmt->fetchColumn() ?? 0);

echo json_encode(['ok' => true, 'cart_count' => $count]);
