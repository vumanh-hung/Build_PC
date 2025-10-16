<?php
session_start();
require_once 'functions.php';
require_once 'config.php';

if (!isset($_SESSION['is_admin'])) {
    header('Location: admin.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$pdo = getPDO();
$stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
$stmt->execute([$id]);
header('Location: admin.php');
exit;
