<?php
require_once 'db.php';

function getCategories() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT DISTINCT category FROM products ORDER BY category');
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getProductsByCategory($category) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE category = ? ORDER BY price');
    $stmt->execute([$category]);
    return $stmt->fetchAll();
}

function getProduct($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllProducts() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM products ORDER BY category, name');
    return $stmt->fetchAll();
}

function createConfiguration($name, $productIds) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO configurations (name) VALUES (?)');
    $stmt->execute([$name]);
    $configId = $pdo->lastInsertId();
    $stmt2 = $pdo->prepare('INSERT INTO configuration_items (configuration_id, product_id) VALUES (?, ?)');
    foreach ($productIds as $pid) {
        $stmt2->execute([$configId, $pid]);
    }
    return $configId;
}

function getConfigurations() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM configurations ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function getConfigurationItems($configId) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT p.* FROM configuration_items ci JOIN products p ON ci.product_id = p.id WHERE ci.configuration_id = ?');
    $stmt->execute([$configId]);
    return $stmt->fetchAll();
}
