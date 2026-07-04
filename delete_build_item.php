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

// ===== NHẬN DỮ LIỆU =====
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

$item_id = intval($data['item_id'] ?? 0);
$build_id = intval($data['build_id'] ?? 0);

// ===== VALIDATE INPUT =====
if (!$item_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Thiếu thông tin: item_id',
        'received' => $data
    ]);
    exit;
}

if (!$build_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Thiếu thông tin: build_id',
        'received' => $data
    ]);
    exit;
}

try {
    $pdo = getPDO();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ===== BƯỚC 1: KIỂM TRA ITEM TỒN TẠI VÀ LẤY THÔNG TIN =====
    $stmt = $pdo->prepare("
        SELECT bi.build_item_id, bi.build_id, bi.product_id, bi.quantity,
               b.user_id, b.name as build_name,
               p.name as product_name, p.price
        FROM build_items bi
        JOIN builds b ON bi.build_id = b.build_id
        JOIN products p ON bi.product_id = p.product_id
        WHERE bi.build_item_id = ? AND bi.build_id = ?
    ");
    $stmt->execute([$item_id, $build_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Không tìm thấy item trong build',
            'item_id' => $item_id,
            'build_id' => $build_id
        ]);
        exit;
    }
    
    // ===== BƯỚC 2: KIỂM TRA QUYỀN SỞ HỮU =====
    if ($item['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Bạn không có quyền xóa item này'
        ]);
        exit;
    }
    
    // ===== BƯỚC 3: BẮT ĐẦU TRANSACTION =====
    $pdo->beginTransaction();
    
    try {
        // Xóa item
        $stmt = $pdo->prepare("
            DELETE FROM build_items 
            WHERE build_item_id = ? AND build_id = ?
        ");
        $stmt->execute([$item_id, $build_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Không thể xóa item');
        }
        
        // ===== BƯỚC 4: CẬP NHẬT TỔNG GIÁ BUILD =====
        $stmt = $pdo->prepare("
            SELECT SUM(p.price * bi.quantity) as total
            FROM build_items bi
            JOIN products p ON bi.product_id = p.product_id
            WHERE bi.build_id = ?
        ");
        $stmt->execute([$build_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_total = floatval($result['total'] ?? 0);
        
        // Cập nhật tổng giá
        $stmt = $pdo->prepare("
            UPDATE builds 
            SET total_price = ?
            WHERE build_id = ?
        ");
        $stmt->execute([$new_total, $build_id]);
        
        // ===== BƯỚC 5: ĐẾM SỐ ITEM CÒN LẠI =====
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as item_count
            FROM build_items
            WHERE build_id = ?
        ");
        $stmt->execute([$build_id]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ===== COMMIT TRANSACTION =====
        $pdo->commit();
        
        // ===== TRẢ VỀ KẾT QUẢ =====
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa item thành công',
            'data' => [
                'deleted_item' => [
                    'item_id' => $item['build_item_id'],
                    'product_name' => $item['product_name'],
                    'price' => floatval($item['price'])
                ],
                'build' => [
                    'build_id' => $build_id,
                    'build_name' => $item['build_name'],
                    'new_total' => $new_total,
                    'remaining_items' => intval($count['item_count'] ?? 0)
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database Error in delete_build_item.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi cơ sở dữ liệu',
        'message' => 'Không thể xóa item. Vui lòng thử lại sau.'
    ]);
    
} catch (Exception $e) {
    error_log("Error in delete_build_item.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi hệ thống',
        'message' => $e->getMessage()
    ]);
}
?>