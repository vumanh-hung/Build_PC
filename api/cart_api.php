<?php

/**
 * api/cart_api.php - Cart API Handler
 * ✅ FIXED: Session + JSON response
 */

// ✅ Start session trước tiên
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ CORS headers
header('Access-Control-Allow-Origin: http://localhost:9000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// ✅ Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ✅ Debug logging
error_log('🔍 Cart API - Session ID: ' . session_id());
error_log('🔍 Cart API - Has user: ' . (isset($_SESSION['user']) ? 'YES' : 'NO'));

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ✅ Kiểm tra đăng nhập
$user_id = getCurrentUserId();
if (!$user_id) {
    error_log('❌ Cart API - User not logged in');
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Bạn cần đăng nhập để sử dụng giỏ hàng.',
        'debug' => [
            'session_id' => session_id(),
            'has_session' => isset($_SESSION['user']),
            'session_keys' => array_keys($_SESSION ?? [])
        ]
    ]);
    exit;
}

error_log('✅ Cart API - User ID: ' . $user_id);

try {
    $pdo = getPDO();

    // ✅ Nhận dữ liệu
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    error_log('📦 Cart API - Input data: ' . $raw);

    // Hỗ trợ cả POST form-data và JSON
    $action = $_GET['action'] ?? $_POST['action'] ?? ($data['action'] ?? '');

    error_log('🎯 Cart API - Action: ' . $action);

    switch ($action) {
        // ===== THÊM VÀO GIỎ HÀNG =====
        case 'add':
            $product_id = intval($_POST['product_id'] ?? ($data['product_id'] ?? 0));
            $quantity = intval($_POST['quantity'] ?? ($data['quantity'] ?? 1));

            error_log('🛒 Adding: product_id=' . $product_id . ', quantity=' . $quantity);

            if (!$product_id || $quantity < 1) {
                echo json_encode([
                    'ok' => false,
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ'
                ]);
                exit;
            }

            // Kiểm tra sản phẩm tồn tại
            $stmt = $pdo->prepare("
                SELECT product_id, name, stock 
                FROM products 
                WHERE product_id = ?
            ");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode([
                    'ok' => false,
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ]);
                exit;
            }

            // Kiểm tra tồn kho
            if ($product['stock'] < $quantity) {
                echo json_encode([
                    'ok' => false,
                    'success' => false,
                    'message' => 'Sản phẩm không đủ số lượng trong kho'
                ]);
                exit;
            }

            // Lấy hoặc tạo giỏ hàng
            $cart_id = getOrCreateCart($user_id);

            error_log('🛒 Cart ID: ' . $cart_id);

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

                // Kiểm tra không vượt quá tồn kho
                if ($new_quantity > $product['stock']) {
                    $new_quantity = $product['stock'];
                }

                $stmt = $pdo->prepare("
                    UPDATE cart_items 
                    SET quantity = ?
                    WHERE id = ?
                ");
                $stmt->execute([$new_quantity, $existing['id']]);

                error_log('✅ Updated quantity: ' . $new_quantity);
            } else {
                // Thêm mới
                $stmt = $pdo->prepare("
                    INSERT INTO cart_items (cart_id, product_id, quantity)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$cart_id, $product_id, $quantity]);

                error_log('✅ Inserted new item');
            }

            // Lấy số lượng giỏ hàng
            $cart_count = getCartCount($user_id);

            error_log('✅ Cart count: ' . $cart_count);

            echo json_encode([
                'ok' => true,
                'success' => true,
                'message' => 'Đã thêm vào giỏ hàng',
                'cart_count' => $cart_count
            ]);
            break;

        // ===== XÓA KHỎI GIỎ =====
        case 'remove':
            $product_id = intval($_GET['id'] ?? ($data['id'] ?? 0));

            if (!$product_id) {
                echo json_encode([
                    'ok' => false,
                    'success' => false,
                    'message' => 'ID không hợp lệ'
                ]);
                exit;
            }

            $cart_id = getOrCreateCart($user_id);

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

        // ===== XÓA TẤT CẢ =====
        case 'clear':
            $cart_id = getOrCreateCart($user_id);

            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cart_id]);

            echo json_encode([
                'ok' => true,
                'success' => true,
                'message' => 'Đã xóa toàn bộ giỏ hàng',
                'cart_count' => 0
            ]);
            break;

        // ===== CẬP NHẬT SỐ LƯỢNG =====
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
                $quantity = max(1, intval($quantity));

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

        // ===== LẤY THÔNG TIN GIỎ =====
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
    error_log("❌ Cart API Error: " . $e->getMessage());
    error_log("❌ Stack trace: " . $e->getTraceAsString());

    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
