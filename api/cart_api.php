<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once "../db.php";

$pdo = getPDO();

// ✅ Kiểm tra đăng nhập
$user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['user_id'] ?? 0);
if (!$user_id) {
    echo json_encode(['ok' => false, 'msg' => 'Bạn cần đăng nhập để sử dụng giỏ hàng.']);
    exit;
}

// === Hàm phụ trợ ===
function getCartId($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cart) return $cart['id'];

    $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)")->execute([$user_id]);
    return $pdo->lastInsertId();
}

function getCartCount($pdo, $cart_id) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cart_id]);
    return (int)($stmt->fetchColumn() ?? 0);
}

// === Nhận dữ liệu JSON ===
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$action = $_GET['action'] ?? $data['action'] ?? '';
$id = (int)($_GET['id'] ?? ($data['id'] ?? 0));
$items = $data['items'] ?? [];

$cart_id = getCartId($pdo, $user_id);

try {
    switch ($action) {
        case 'add':
            if ($id <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Thiếu ID sản phẩm']);
                exit;
            }

            // Kiểm tra sản phẩm tồn tại
            $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                echo json_encode(['ok' => false, 'msg' => 'Sản phẩm không tồn tại']);
                exit;
            }

            // Thêm hoặc tăng số lượng
            $pdo->prepare("
                INSERT INTO cart_items (cart_id, product_id, quantity)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE quantity = quantity + 1
            ")->execute([$cart_id, $id]);

            echo json_encode([
                'ok' => true,
                'msg' => 'Đã thêm sản phẩm vào giỏ hàng!',
                'cart_count' => getCartCount($pdo, $cart_id)
            ]);
            break;

        case 'remove':
            if ($id <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Thiếu ID sản phẩm cần xóa']);
                exit;
            }
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?")
                ->execute([$cart_id, $id]);

            echo json_encode([
                'ok' => true,
                'msg' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
                'cart_count' => getCartCount($pdo, $cart_id)
            ]);
            break;

        case 'update':
            if (!is_array($items) || empty($items)) {
                echo json_encode(['ok' => false, 'msg' => 'Không có dữ liệu cập nhật']);
                exit;
            }

            $pdo->beginTransaction();
            foreach ($items as $pid => $qty) {
                $pid = (int)$pid;
                $qty = max(1, (int)$qty);
                $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?")
                    ->execute([$qty, $cart_id, $pid]);
            }
            $pdo->commit();

            echo json_encode([
                'ok' => true,
                'msg' => 'Đã cập nhật giỏ hàng!',
                'cart_count' => getCartCount($pdo, $cart_id)
            ]);
            break;

        case 'clear':
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cart_id]);
            echo json_encode([
                'ok' => true,
                'msg' => 'Đã xóa toàn bộ giỏ hàng!',
                'cart_count' => 0
            ]);
            break;

        default:
            // Trả về toàn bộ giỏ hàng
            $stmt = $pdo->prepare("
                SELECT 
                    p.product_id AS id,
                    p.name,
                    p.price,
                    p.main_image AS image,
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
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Lỗi: ' . $e->getMessage()]);
}
