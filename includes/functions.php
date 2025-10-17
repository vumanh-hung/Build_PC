<?php
// === FUNCTIONS.PHP ===
require_once 'config.php';

// Lấy danh sách sản phẩm
function getProducts($limit = 12) {
  global $pdo;
  $stmt = $pdo->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT :limit");
  $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
