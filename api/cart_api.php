<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db.php';

header('Content-Type: application/json; charset=utf-8');

function getCartCount() {
    $count = 0;
    foreach ($_SESSION['cart'] ?? [] as $it) {
        $count += $it['quantity'] ?? 0;
    }
    return $count;
}

$action = $_REQUEST['action'] ?? '';
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

switch ($action) {
    case 'add':
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($p) {
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $id) {
                    $item['quantity']++;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $_SESSION['cart'][] = [
                    'id' => $p['product_id'],
                    'name' => $p['name'],
                    'price' => $p['price'],
                    'image' => $p['image'],
                    'quantity' => 1
                ];
            }
        }
        echo json_encode(['ok' => true, 'cart_count' => getCartCount()]);
        break;

    case 'remove':
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($i) => $i['id'] != $id));
        echo json_encode(['ok' => true, 'cart_count' => getCartCount()]);
        break;

    case 'update':
        $items = $_POST['items'] ?? $_POST['qty'] ?? [];
        foreach ($items as $pid => $qty) {
            foreach ($_SESSION['cart'] as &$it) {
                if ($it['id'] == (int)$pid) {
                    $it['quantity'] = max(1, (int)$qty);
                    break;
                }
            }
        }
        echo json_encode(['ok' => true, 'cart_count' => getCartCount()]);
        break;

    case 'clear':
        unset($_SESSION['cart']);
        echo json_encode(['ok' => true, 'cart_count' => 0]);
        break;

    default:
        echo json_encode([
            'ok' => true,
            'cart' => $_SESSION['cart'] ?? [],
            'cart_count' => getCartCount()
        ]);
        break;
}
