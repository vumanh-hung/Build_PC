<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

// Kiểm tra user đã login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $user_id = $_SESSION['user']['user_id'];
    $product_id = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    // Validation
    if (!$product_id || !$rating || !$title || !$content) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Đánh giá không hợp lệ']);
        exit;
    }
    
    if (strlen($title) > 200) {
        echo json_encode(['success' => false, 'message' => 'Tiêu đề quá dài']);
        exit;
    }
    
    if (strlen($content) > 2000) {
        echo json_encode(['success' => false, 'message' => 'Nội dung quá dài']);
        exit;
    }
    
    // Kiểm tra sản phẩm có tồn tại
    $stmt = $pdo->prepare('SELECT product_id FROM products WHERE product_id = ?');
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }
    
    // Tạo thư mục upload nếu chưa có
    $upload_dir = __DIR__ . '/../uploads/reviews/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Xử lý hình ảnh
    $image_paths = [];
    if (isset($_FILES['images'])) {
        $files = $_FILES['images'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $max_files = 5;
        
        $file_count = count(array_filter($files['name']));
        if ($file_count > $max_files) {
            echo json_encode(['success' => false, 'message' => 'Tối đa ' . $max_files . ' ảnh']);
            exit;
        }
        
        for ($i = 0; $i < $file_count; $i++) {
            if (empty($files['name'][$i])) continue;
            
            $file = [
                'name' => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'size' => $files['size'][$i],
                'type' => $files['type'][$i],
                'error' => $files['error'][$i]
            ];
            
            // Kiểm tra lỗi upload
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Lỗi upload hình ảnh']);
                exit;
            }
            
            // Kiểm tra loại file
            if (!in_array($file['type'], $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'Chỉ hỗ trợ file ảnh (JPG, PNG, GIF, WebP)']);
                exit;
            }
            
            // Kiểm tra kích thước
            if ($file['size'] > $max_size) {
                echo json_encode(['success' => false, 'message' => 'Kích thước ảnh tối đa 5MB']);
                exit;
            }
            
            // Tạo tên file an toàn
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'review_' . time() . '_' . uniqid() . '.' . strtolower($ext);
            $filepath = $upload_dir . $filename;
            
            // Upload file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu hình ảnh']);
                exit;
            }
            
            $image_paths[] = 'uploads/reviews/' . $filename;
        }
    }
    
    // Lưu vào database (tự động duyệt - status = 'approved')
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare('
        INSERT INTO reviews (product_id, user_id, rating, title, content, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ');
    
    $stmt->execute([
        $product_id,
        $user_id,
        $rating,
        $title,
        $content,
        'approved'  // ✅ Tự động duyệt
    ]);
    
    $review_id = $pdo->lastInsertId();
    
    // Lưu hình ảnh
    if (!empty($image_paths)) {
        $stmt = $pdo->prepare('INSERT INTO review_images (review_id, image_path) VALUES (?, ?)');
        foreach ($image_paths as $image_path) {
            $stmt->execute([$review_id, $image_path]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đánh giá đã được gửi thành công',
        'review_id' => $review_id
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>