<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// Check if user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Vui lòng đăng nhập'
    ]);
    exit;
}

try {
    $user_id = $_SESSION['user']['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    
    $build_id = intval($data['build_id'] ?? 0);
    $name = trim($data['name'] ?? '');
    
    // Validate
    if (!$build_id || $build_id <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Build ID không hợp lệ'
        ]);
        exit;
    }
    
    if (empty($name)) {
        echo json_encode([
            'success' => false,
            'error' => 'Tên không được để trống'
        ]);
        exit;
    }
    
    if (strlen($name) < 3) {
        echo json_encode([
            'success' => false,
            'error' => 'Tên phải có ít nhất 3 ký tự'
        ]);
        exit;
    }
    
    if (strlen($name) > 100) {
        echo json_encode([
            'success' => false,
            'error' => 'Tên quá dài (tối đa 100 ký tự)'
        ]);
        exit;
    }
    
    $pdo = getPDO();
    
    // Check if build belongs to user
    $stmt = $pdo->prepare("
        SELECT user_id 
        FROM builds 
        WHERE build_id = :build_id
    ");
    $stmt->execute([':build_id' => $build_id]);
    $build = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$build) {
        echo json_encode([
            'success' => false,
            'error' => 'Không tìm thấy cấu hình'
        ]);
        exit;
    }
    
    if ($build['user_id'] != $user_id) {
        echo json_encode([
            'success' => false,
            'error' => 'Bạn không có quyền sửa cấu hình này'
        ]);
        exit;
    }
    
    // Update build name
    $stmt = $pdo->prepare("
        UPDATE builds 
        SET name = :name,
            updated_at = NOW()
        WHERE build_id = :build_id
    ");
    
    $result = $stmt->execute([
        ':name' => $name,
        ':build_id' => $build_id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã cập nhật tên thành công',
            'name' => $name
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Không thể cập nhật tên'
        ]);
    }
    
} catch (PDOException $e) {
    error_log('❌ Update build name error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi database: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('❌ Update build name error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}