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
    echo json_encode([
        'success' => false,
        'error' => 'Bạn cần đăng nhập để thực hiện thao tác này'
    ]);
    exit;
}

// ===== KIỂM TRA METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Chỉ chấp nhận phương thức POST'
    ]);
    exit;
}

// ===== NHẬN DỮ LIỆU =====
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

$build_id = intval($data['build_id'] ?? 0);
$item_id = intval($data['item_id'] ?? 0);
$new_product_id = intval($data['new_product_id'] ?? 0);

// ===== VALIDATE DỮ LIỆU =====
if (!$build_id || !$item_id || !$new_product_id) {
    echo json_encode([
        'success' => false,
        'error' => 'Thiếu thông tin: build_id, item_id hoặc new_product_id',
        'received' => [
            'build_id' => $build_id,
            'item_id' => $item_id,
            'new_product_id' => $new_product_id
        ]
    ]);
    exit;
}

try {
    $pdo = getPDO();
    
    // ===== BƯỚC 1: KIỂM TRA BUILD CÓ TỒN TẠI VÀ THUỘC VỀ USER =====
    $stmt = $pdo->prepare("
        SELECT b.build_id, b.user_id, b.name
        FROM builds b
        WHERE b.build_id = ?
    ");
    $stmt->execute([$build_id]);
    $build = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$build) {
        echo json_encode([
            'success' => false,
            'error' => 'Không tìm thấy cấu hình'
        ]);
        exit;
    }
    
    // ===== BƯỚC 2: KIỂM TRA QUYỀN SỞ HỮU =====
    if ($build['user_id'] != $user_id) {
        echo json_encode([
            'success' => false,
            'error' => 'Bạn không có quyền chỉnh sửa cấu hình này'
        ]);
        exit;
    }
    
    // ===== BƯỚC 3: KIỂM TRA ITEM CÓ TỒN TẠI TRONG BUILD =====
    $stmt = $pdo->prepare("
        SELECT bi.build_item_id, bi.product_id, bi.quantity,
               p.name as old_product_name, p.price as old_price,
               c.category_id, c.name as category_name
        FROM build_items bi
        JOIN products p ON bi.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE bi.build_item_id = ? AND bi.build_id = ?
    ");
    $stmt->execute([$item_id, $build_id]);
    $old_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$old_item) {
        echo json_encode([
            'success' => false,
            'error' => 'Không tìm thấy linh kiện trong cấu hình'
        ]);
        exit;
    }
    
    // ===== BƯỚC 4: KIỂM TRA SẢN PHẨM MỚI CÓ TỒN TẠI =====
    $stmt = $pdo->prepare("
        SELECT p.product_id, p.name, p.price, p.category_id,
               c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.product_id = ?
    ");
    $stmt->execute([$new_product_id]);
    $new_product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$new_product) {
        echo json_encode([
            'success' => false,
            'error' => 'Sản phẩm mới không tồn tại'
        ]);
        exit;
    }
    
    // ===== BƯỚC 5: KIỂM TRA CATEGORY CÓ KHỚP KHÔNG =====
    if ($old_item['category_id'] != $new_product['category_id']) {
        echo json_encode([
            'success' => false,
            'error' => sprintf(
                'Không thể thay thế %s bằng %s. Chỉ có thể thay thế sản phẩm cùng loại.',
                $old_item['category_name'],
                $new_product['category_name']
            ),
            'old_category' => $old_item['category_name'],
            'new_category' => $new_product['category_name']
        ]);
        exit;
    }
    
    // ===== BƯỚC 6: KIỂM TRA SẢN PHẨM MỚI ĐÃ CÓ TRONG BUILD CHƯA =====
    $stmt = $pdo->prepare("
        SELECT bi.build_item_id, p.name
        FROM build_items bi
        JOIN products p ON bi.product_id = p.product_id
        WHERE bi.build_id = ? AND bi.product_id = ? AND bi.build_item_id != ?
    ");
    $stmt->execute([$build_id, $new_product_id, $item_id]);
    $duplicate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($duplicate) {
        echo json_encode([
            'success' => false,
            'error' => sprintf(
                'Sản phẩm "%s" đã có trong cấu hình. Không thể thêm trùng.',
                $new_product['name']
            )
        ]);
        exit;
    }
    
    // ===== BƯỚC 7: THỰC HIỆN THAY THẾ =====
    $pdo->beginTransaction();
    
    try {
        // Cập nhật product_id trong build_items
        $stmt = $pdo->prepare("
            UPDATE build_items 
            SET product_id = ?, 
                quantity = ?
            WHERE build_item_id = ? AND build_id = ?
        ");
        $stmt->execute([
            $new_product_id,
            $old_item['quantity'], // Giữ nguyên số lượng
            $item_id,
            $build_id
        ]);
        
        // Kiểm tra có update được không
        if ($stmt->rowCount() === 0) {
            throw new Exception('Không thể cập nhật sản phẩm');
        }
        
        // ===== BƯỚC 8: CẬP NHẬT TỔNG GIÁ BUILD =====
        $stmt = $pdo->prepare("
            SELECT SUM(p.price * bi.quantity) as total
            FROM build_items bi
            JOIN products p ON bi.product_id = p.product_id
            WHERE bi.build_id = ?
        ");
        $stmt->execute([$build_id]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            UPDATE builds 
            SET total_price = ?
            WHERE build_id = ?
        ");
        $stmt->execute([
            $total['total'] ?? 0,
            $build_id
        ]);
        
        $pdo->commit();
        
        // ===== BƯỚC 9: TRẢ VỀ KẾT QUẢ THÀNH CÔNG =====
        echo json_encode([
            'success' => true,
            'message' => 'Đã thay thế sản phẩm thành công',
            'data' => [
                'build_id' => $build_id,
                'build_name' => $build['name'],
                'old_product' => [
                    'id' => $old_item['product_id'],
                    'name' => $old_item['old_product_name'],
                    'price' => $old_item['old_price']
                ],
                'new_product' => [
                    'id' => $new_product['product_id'],
                    'name' => $new_product['name'],
                    'price' => $new_product['price']
                ],
                'category' => $new_product['category_name'],
                'new_total' => $total['total'] ?? 0
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Replace Build Item Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi hệ thống: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>