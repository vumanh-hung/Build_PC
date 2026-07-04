<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ===== KIỂM TRA ĐĂNG NHẬP =====
$user_id = getCurrentUserId();
if (!$user_id) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Bạn cần đăng nhập để thực hiện thao tác này'
    ]);
    exit;
}

// ===== KIỂM TRA METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Chỉ chấp nhận phương thức POST'
    ]);
    exit;
}

// ===== NHẬN VÀ VALIDATE DỮ LIỆU =====
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Dữ liệu JSON không hợp lệ'
    ]);
    exit;
}

$build_id = intval($data['build_id'] ?? 0);
$product_id = intval($data['product_id'] ?? 0);
$quantity = intval($data['quantity'] ?? 1);

// ===== VALIDATE INPUT =====
if (!$build_id || !$product_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Thiếu thông tin: build_id hoặc product_id',
        'received' => [
            'build_id' => $build_id,
            'product_id' => $product_id
        ]
    ]);
    exit;
}

if ($quantity < 1 || $quantity > 99) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Số lượng không hợp lệ (phải từ 1-99)',
        'received_quantity' => $quantity
    ]);
    exit;
}

try {
    $pdo = getPDO();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ===== BƯỚC 1: KIỂM TRA BUILD TỒN TẠI VÀ QUYỀN SỞ HỮU =====
    $stmt = $pdo->prepare("
        SELECT b.build_id, b.user_id, b.name, b.total_price
        FROM builds b
        WHERE b.build_id = ?
    ");
    $stmt->execute([$build_id]);
    $build = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$build) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Không tìm thấy cấu hình với ID: ' . $build_id
        ]);
        exit;
    }
    
    // ===== BƯỚC 2: KIỂM TRA QUYỀN SỞ HỮU =====
    if ($build['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Bạn không có quyền chỉnh sửa cấu hình này'
        ]);
        exit;
    }
    
    // ===== BƯỚC 3: KIỂM TRA SẢN PHẨM TỒN TẠI VÀ ACTIVE =====
    $stmt = $pdo->prepare("
        SELECT p.product_id, p.name, p.price, p.stock, p.category_id,
               c.name as category_name, c.slug as category_slug,
               b.name as brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.product_id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Sản phẩm không tồn tại với ID: ' . $product_id
        ]);
        exit;
    }
    
    // ===== BƯỚC 4: KIỂM TRA TỒN KHO =====
    if (isset($product['stock']) && $product['stock'] < $quantity) {
        echo json_encode([
            'success' => false,
            'error' => 'Sản phẩm không đủ số lượng trong kho',
            'available_stock' => $product['stock'],
            'requested_quantity' => $quantity
        ]);
        exit;
    }
    
    // ===== BƯỚC 5: KIỂM TRA SẢN PHẨM ĐÃ CÓ TRONG BUILD CHƯA =====
    $stmt = $pdo->prepare("
        SELECT bi.build_item_id, bi.quantity, p.name
        FROM build_items bi
        JOIN products p ON bi.product_id = p.product_id
        WHERE bi.build_id = ? AND bi.product_id = ?
    ");
    $stmt->execute([$build_id, $product_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo json_encode([
            'success' => false,
            'error' => 'Sản phẩm này đã có trong cấu hình',
            'product_name' => $product['name'],
            'current_quantity' => $existing['quantity'],
            'suggestion' => 'Vui lòng thay thế hoặc xóa sản phẩm cũ trước'
        ]);
        exit;
    }
    
    // ===== BƯỚC 6: KIỂM TRA CATEGORY RESTRICTED =====
    // Một số category chỉ cho phép 1 sản phẩm
    $restricted_categories = [
        'CPU', 'Mainboard', 'CARD màn hình', 'VGA', 
        'Màn hình', 'Vỏ case', 'Nguồn máy tính', 'Case'
    ];
    
    if (in_array($product['category_name'], $restricted_categories)) {
        $stmt = $pdo->prepare("
            SELECT bi.build_item_id, p.name, p.product_id, 
                   c.name as category_name
            FROM build_items bi
            JOIN products p ON bi.product_id = p.product_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE bi.build_id = ? AND p.category_id = ?
        ");
        $stmt->execute([$build_id, $product['category_id']]);
        $category_exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category_exists) {
            echo json_encode([
                'success' => false,
                'error' => sprintf(
                    'Cấu hình đã có %s (%s)',
                    $category_exists['category_name'],
                    $category_exists['name']
                ),
                'existing_product' => [
                    'id' => $category_exists['product_id'],
                    'name' => $category_exists['name'],
                    'category' => $category_exists['category_name']
                ],
                'suggestion' => 'Vui lòng thay thế hoặc xóa sản phẩm cũ trước khi thêm sản phẩm mới'
            ]);
            exit;
        }
    }
    
    // ===== BƯỚC 7: BẮT ĐẦU TRANSACTION =====
    $pdo->beginTransaction();
    
    try {
        // Thêm sản phẩm vào build_items
        $stmt = $pdo->prepare("
            INSERT INTO build_items (build_id, product_id, quantity)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$build_id, $product_id, $quantity]);
        
        $new_item_id = $pdo->lastInsertId();
        
        if (!$new_item_id) {
            throw new Exception('Không thể thêm sản phẩm vào cấu hình');
        }
        
        // ===== BƯỚC 8: CẬP NHẬT TỔNG GIÁ BUILD =====
        $stmt = $pdo->prepare("
            SELECT SUM(p.price * bi.quantity) as total
            FROM build_items bi
            JOIN products p ON bi.product_id = p.product_id
            WHERE bi.build_id = ?
        ");
        $stmt->execute([$build_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_total = floatval($result['total'] ?? 0);
        
        // Cập nhật tổng giá vào builds table
        $stmt = $pdo->prepare("
            UPDATE builds 
            SET total_price = ?
            WHERE build_id = ?
        ");
        $stmt->execute([$new_total, $build_id]);
        
        // ===== BƯỚC 9: ĐẾM TỔNG SỐ LINH KIỆN =====
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as item_count,
                   SUM(bi.quantity) as total_quantity
            FROM build_items bi
            WHERE bi.build_id = ?
        ");
        $stmt->execute([$build_id]);
        $count_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ===== COMMIT TRANSACTION =====
        $pdo->commit();
        
        // ===== BƯỚC 10: LOG ACTIVITY (OPTIONAL) =====
        // Có thể thêm log activity ở đây nếu cần
        
        // ===== TRẢ VỀ KẾT QUẢ THÀNH CÔNG =====
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào cấu hình thành công',
            'data' => [
                'build' => [
                    'id' => $build_id,
                    'name' => $build['name'],
                    'old_total' => floatval($build['total_price']),
                    'new_total' => $new_total,
                    'total_items' => intval($count_data['item_count'] ?? 0),
                    'total_quantity' => intval($count_data['total_quantity'] ?? 0)
                ],
                'new_item' => [
                    'id' => $new_item_id,
                    'product_id' => $product['product_id'],
                    'product_name' => $product['name'],
                    'price' => floatval($product['price']),
                    'quantity' => $quantity,
                    'category' => $product['category_name'],
                    'brand' => $product['brand_name']
                ],
                'price_change' => $new_total - floatval($build['total_price'])
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    // Lỗi database
    error_log("Database Error in add_product_to_build.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi cơ sở dữ liệu',
        'message' => 'Không thể thêm sản phẩm. Vui lòng thử lại sau.',
        'debug' => [
            'code' => $e->getCode(),
            'sql_state' => $e->errorInfo[0] ?? null
        ]
    ]);
    
} catch (Exception $e) {
    // Lỗi khác
    error_log("Error in add_product_to_build.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi hệ thống',
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
?>