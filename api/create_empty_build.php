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
    $name = $data['name'] ?? 'Cấu hình mới ' . date('d/m/Y H:i');
    
    $pdo = getPDO();
    
    // Create empty build
    $stmt = $pdo->prepare("
        INSERT INTO builds (user_id, name, total_price, created_at)
        VALUES (:user_id, :name, 0, NOW())
    ");
    
    $stmt->execute([
        ':user_id' => $user_id,
        ':name' => $name
    ]);
    
    $build_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'build_id' => $build_id,
        'message' => 'Đã tạo cấu hình mới'
    ]);
    
} catch (PDOException $e) {
    error_log('❌ Create build error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi database: ' . $e->getMessage()
    ]);
}