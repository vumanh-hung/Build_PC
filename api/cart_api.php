<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once "../db.php";
require_once "../functions.php";

$pdo = getPDO();

// ✅ Kiểm tra đăng nhập
$user_id = getCurrentUserId();
if (!$user_id) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Bạn cần đăng nhập để sử dụng giỏ hàng.'
    ]);
    exit;
}

// === Nhận dữ liệu ===
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Hỗ trợ cả GET, POST và JSON
$action = $_GET['action'] ?? $_POST['action'] ?? ($data['action'] ?? '');

// Xử lý FormData từ products.php
if (empty($action) && !empty($_POST)) {
    $action = $_POST['action'] ?? '';
}

try {
    switch ($action) {
        // ===== THÊM SẢN PHẨM VÀO GIỎ (dùng product_id) =====
        case 'add':
            $product_id = $_POST['product_id'] ?? ($data['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? ($data['quantity'] ?? 1));
            
            if (!$product_id || $quantity < 1) {
                echo json_encode([
                    'ok' => false,
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ'
                ]);
                exit;
            }

            // Kiểm tra sản phẩm tồn tại
            $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            if (!$stmt->fetch()) {
                echo json_encode([
                    'ok' => false,
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ]);
                exit;
            }

            // Lấy hoặc tạo giỏ hàng
            $cart_id = getOrCreateCart($user_id);
            if (!$cart_id) {
                echo json_encode([
                    'ok' => false,
                    'success' => false,
                    'message' => 'Không thể tạo giỏ hàng'
                ]);
                exit;
            }

            // Kiểm tra sản phẩm đã có trong giỏ chưa
            $stmt = $pdo->prepare("
                SELECT id, quantity 
                FROM cart_items 
                WHERE cart_id = ? AND product_id = ?
            ");
            $stmt->execute([$cart_id, $product_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Cập nhật số lượng
                $new_quantity = $existing['quantity'] + $quantity;
                $stmt = $pdo->prepare("
                    UPDATE cart_items 
                    SET quantity = ?
                    WHERE id = ?
                ");
                $stmt->execute([$new_quantity, $existing['id']]);
            } else {
                // Thêm mới
                $stmt = $pdo->prepare("
                    INSERT INTO cart_items (cart_id, product_id, quantity)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$cart_id, $product_id, $quantity]);
            }

            // Lấy số lượng giỏ hàng mới
            $cart_count = getCartCount($user_id);

            echo json_encode([
                'ok' => true,
                'success' => true,
                'message' => 'Đã thêm vào giỏ hàng',
                'cart_count' => $cart_count
            ]);
            break;

        // ===== XÓA SẢN PHẨM (dùng product_id) =====
        case 'remove':
            $product_id = $_GET['id'] ?? ($data['id'] ?? 0);
            
            if (!$product_id) {
                echo json_encode([
                    'ok' => false,
                    'success' => false,
                    'message' => 'ID không hợp lệ'
                ]);
                exit;
            }

            // Lấy cart_id của user
            $cart_id = getOrCreateCart($user_id);

            // Xóa theo product_id
            $stmt = $pdo->prepare("
                DELETE FROM cart_items 
                WHERE cart_id = ? AND product_id = ?
            ");
            $stmt->execute([$cart_id, $product_id]);
            
            $success = $stmt->rowCount() > 0;
            $cart_count = getCartCount($user_id);

            echo json_encode([
                'ok' => $success,
                'success' => $success,
                'message' => $success ? 'Đã xóa sản phẩm' : 'Không thể xóa',
                'cart_count' => $cart_count
            ]);
            break;

        // ===== XÓA TOÀN BỘ GIỎ HÀNG =====
        case 'clear':
            $cart_id = getOrCreateCart($user_id);
            
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cart_id]);
            
            $success = true;

            echo json_encode([
                'ok' => $success,
                'success' => $success,
                'message' => 'Đã xóa toàn bộ giỏ hàng',
                'cart_count' => 0
            ]);
            break;

        // ===== CẬP NHẬT SỐ LƯỢNG (dùng product_id) =====
        case 'update':
            $items = $data['items'] ?? [];
            
            if (empty($items)) {
                echo json_encode([
                    'ok' => false,
                    'success' => false,
                    'message' => 'Không có dữ liệu cập nhật'
                ]);
                exit;
            }

            $cart_id = getOrCreateCart($user_id);
            $pdo->beginTransaction();
            
            foreach ($items as $product_id => $quantity) {
                $quantity = max(1, (int)$quantity);
                
                // Cập nhật theo product_id
                $stmt = $pdo->prepare("
                    UPDATE cart_items 
                    SET quantity = ? 
                    WHERE cart_id = ? AND product_id = ?
                ");
                $stmt->execute([$quantity, $cart_id, $product_id]);
            }
            
            $pdo->commit();
            
            $cart_count = getCartCount($user_id);

            echo json_encode([
                'ok' => true,
                'success' => true,
                'message' => 'Đã cập nhật giỏ hàng',
                'cart_count' => $cart_count
            ]);
            break;

        // ===== LẤY THÔNG TIN GIỎ HÀNG =====
        default:
            $items = getCartItems($user_id);
            $total = calculateCartTotal($items);
            $cart_count = getCartCount($user_id);

            echo json_encode([
                'ok' => true,
                'success' => true,
                'cart' => $items,
                'cart_count' => $cart_count,
                'total' => $total
            ]);
            break;
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Cart API Error: " . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
?>