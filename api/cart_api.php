<?php
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', 'localhost');

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once "../db.php";

if (empty($_SESSION['user_id']) && empty($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'msg' => 'Bạn cần đăng nhập để sử dụng giỏ hàng.']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['user']['user_id'] ?? 0;


// === HÀM TRỢ GIÚP ===
function getCartId($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cart) return $cart['id'];

    // Nếu chưa có giỏ hàng thì tạo mới
    $pdo->prepare("INSERT INTO cart (user_id, created_at) VALUES (?, NOW())")->execute([$user_id]);
    return $pdo->lastInsertId();
}

function getCartCount($pdo, $cart_id) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cart_id]);
    return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
}

// === XỬ LÝ HÀNH ĐỘNG ===
$action = $_REQUEST['action'] ?? '';
$id = intval($_REQUEST['id'] ?? 0);
$cart_id = getCartId($pdo, $user_id);

switch ($action) {
    case 'add':
        if ($id > 0) {
            // Kiểm tra sản phẩm có trong DB không
            $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->execute([$id]);
            $p = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("🧩 CART DEBUG: id=$id\n", 3, "../debug_cart.txt");
            $logFile = __DIR__ . "/debug_cart.txt";
                error_log("🧩 CART DEBUG: id=$id\n", 3, $logFile);
                if (!$p) {
                    error_log("❌ Không tìm thấy sản phẩm có product_id=$id trong bảng products\n", 3, $logFile);
                } else {
                    error_log("✅ Tìm thấy sản phẩm: " . $p['name'] . "\n", 3, $logFile);
                }
            if ($p) {
                // Kiểm tra đã có trong giỏ chưa
                $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
                $stmt->execute([$cart_id, $id]);
                $exist = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($exist) {
                    $pdo->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE id = ?")
                        ->execute([$exist['id']]);
                } else {
                    $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, 1)")
                        ->execute([$cart_id, $id]);
                }
            }
        }
        echo json_encode(['ok' => true, 'cart_count' => getCartCount($pdo, $cart_id)]);
        break;

    case 'remove':
        if ($id > 0) {
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?")
                ->execute([$cart_id, $id]);
        }
        echo json_encode(['ok' => true, 'cart_count' => getCartCount($pdo, $cart_id)]);
        break;

    case 'update':
        $items = $_POST['items'] ?? [];
        foreach ($items as $pid => $qty) {
            $qty = max(1, intval($qty));
            $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?")
                ->execute([$qty, $cart_id, $pid]);
        }
        echo json_encode(['ok' => true, 'cart_count' => getCartCount($pdo, $cart_id)]);
        break;

    case 'clear':
        $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cart_id]);
        echo json_encode(['ok' => true, 'cart_count' => 0]);
        break;

    default:
        $stmt = $pdo->prepare("
            SELECT 
                p.product_id AS id, 
                p.name, 
                p.price, 
                p.image, 
                ci.quantity, 
                (p.price * ci.quantity) AS subtotal
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.product_id
            WHERE ci.cart_id = ?
        ");
        $stmt->execute([$cart_id]);
        $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'ok' => true,
            'cart' => $cart,
            'cart_count' => getCartCount($pdo, $cart_id)
        ]);
        break;
}
