<?php
session_start();
require_once '../db.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }

$id = $_GET['id'];
$stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
$stmt->execute([$id]);
header('Location: products.php');
exit;
?>
