<?php
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', 'localhost');

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once "../db.php";

// =================== DEBUG GHI LOG ===================
$logFile = __DIR__ . "/debug_cart.txt";
function logMsg($msg) {
    global $logFile;
    @error_log(date('[Y-m-d H:i:s] ') . $msg . "\n", 3, $logFile);
}
logMsg("---- New Request ----");
logMsg("REQUEST: " . print_r($_REQUEST, true));
logMsg("COOKIE: " . print_r($_COOKIE, true));
logMsg("SESSION: " . print_r($_SESSION, true));
// =====================================================

if (empty($_SESSION['user_id']) && empty($_SESSION['user'])) {
    logMsg("❌ Không có session user_id, cần đăng nhập!");
    echo json_encode(['ok' => false, 'msg' => 'Bạn cần đăng nhập để sử dụng giỏ hàng.']);
    exit;
}

// Lấy user_id
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
$id = intval($_REQUEST['id'] ?? ($_REQUEST['product_id'] ?? 0)); // ✅ nhận cả id hoặc product_id
$cart_id = getCartId($pdo, $user_id);

logMsg("🧩 Action: $action | Product ID: $id | Cart ID: $cart_id | User ID: $user_id");

switch ($action) {
    case 'add':
        if ($id > 0) {
            // Kiểm tra sản phẩm có trong DB không
            $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->execute([$id]);
            $p = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$p) {
                logMsg("❌ Không tìm thấy sản phẩm có product_id=$id");
            } else {
                logMsg("✅ Tìm thấy sản phẩm: {$p['name']}");

                // Kiểm tra đã có trong giỏ chưa
                $stmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
                $stmt->execute([$cart_id, $id]);
                $exist = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($exist) {
                    $pdo->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE cart_id = ? AND product_id = ?")
                        ->execute([$cart_id, $id]);
                    logMsg("🔁 Tăng số lượng cho product_id=$id");
                } else {
                    $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, 1)")
                        ->execute([$cart_id, $id]);
                    logMsg("🆕 Thêm mới product_id=$id vào cart_items");
                }
            }
        } else {
            logMsg("⚠️ ID không hợp lệ: $id");
        }

        echo json_encode(['ok' => true, 'cart_count' => getCartCount($pdo, $cart_id)]);
        break;

    case 'remove':
        if ($id > 0) {
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?")
                ->execute([$cart_id, $id]);
            logMsg("🗑️ Xóa product_id=$id khỏi giỏ hàng");
        }
        echo json_encode(['ok' => true, 'cart_count' => getCartCount($pdo, $cart_id)]);
        break;

    case 'update':
        $items = $_POST['items'] ?? [];
        foreach ($items as $pid => $qty) {
            $qty = max(1, intval($qty));
            $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?")
                ->execute([$qty, $cart_id, $pid]);
            logMsg("🔧 Cập nhật quantity=$qty cho product_id=$pid");
        }
        echo json_encode(['ok' => true, 'cart_count' => getCartCount($pdo, $cart_id)]);
        break;

    case 'clear':
        $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cart_id]);
        logMsg("🧹 Xóa toàn bộ giỏ hàng cart_id=$cart_id");
        echo json_encode(['ok' => true, 'cart_count' => 0]);
        break;

    default:
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

        logMsg("📦 Lấy giỏ hàng ($cart_id) - " . count($cart) . " sản phẩm");

        echo json_encode([
            'ok' => true,
            'cart' => $cart,
            'cart_count' => getCartCount($pdo, $cart_id)
        ]);
        break;
}
