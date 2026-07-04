<?php
/**
 * api/submit-review.php
 * API endpoint để gửi đánh giá sản phẩm
 * Khớp với cấu trúc bảng reviews trong database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    // ✅ Kiểm tra method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // ✅ Kiểm tra user đã login
    if (!isLoggedIn()) {
        $response['message'] = 'Vui lòng đăng nhập để gửi đánh giá';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $user_id = getCurrentUserId();

    // ✅ Validate dữ liệu
    $product_id = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['comment'] ?? ''); // Lưu ý: POST gửi 'comment' nhưng DB lưu 'content'

    if (!$product_id) {
        throw new Exception('Product ID không hợp lệ');
    }

    if ($rating < 1 || $rating > 5) {
        throw new Exception('Đánh giá phải từ 1 đến 5 sao');
    }

    if (strlen($title) < 5 || strlen($title) > 200) {
        throw new Exception('Tiêu đề phải từ 5 đến 200 ký tự');
    }

    if (strlen($content) < 10 || strlen($content) > 2000) {
        throw new Exception('Nội dung phải từ 10 đến 2000 ký tự');
    }

    // ✅ Kiểm tra sản phẩm tồn tại
    $product = getProduct($product_id);
    if (!$product) {
        throw new Exception('Sản phẩm không tồn tại');
    }

    // ✅ Kiểm tra user đã đánh giá sản phẩm này chưa
    $stmt = $pdo->prepare('SELECT review_id FROM reviews WHERE product_id = ? AND user_id = ?');
    $stmt->execute([$product_id, $user_id]);
    if ($stmt->fetch()) {
        throw new Exception('Bạn đã đánh giá sản phẩm này rồi');
    }

    // ✅ Xử lý upload ảnh (nếu có)
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            throw new Exception('Ảnh không hợp lệ (JPG, PNG, GIF, WebP)');
        }

        if ($_FILES['image']['size'] > $max_size) {
            throw new Exception('Kích thước ảnh không được vượt quá 5MB');
        }

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Lỗi upload ảnh');
        }

        // Tạo tên file unique
        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = 'review_' . time() . '_' . uniqid() . '.' . strtolower($file_ext);
        $upload_dir = __DIR__ . '/../uploads/reviews/';
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $upload_path = $upload_dir . $image_name;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            throw new Exception('Không thể lưu ảnh');
        }

        // ✅ Insert ảnh vào bảng review_images (nếu cần)
        // Nếu muốn lưu ảnh, bạn có thể insert vào bảng review_images sau
    }

    // ✅ Insert review vào database
    // QUAN TRỌNG: Bảng reviews chỉ có các cột: review_id, product_id, user_id, order_id, rating, title, content, helpful_count, unhelpful_count, status, created_at, updated_at
    // Không có cột 'image'
    $stmt = $pdo->prepare('
        INSERT INTO reviews (product_id, user_id, rating, title, content, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ');

    $stmt->execute([
        $product_id,
        $user_id,
        $rating,
        $title,
        $content,
        'pending' // Status chờ duyệt
    ]);

    $review_id = $pdo->lastInsertId();

    // ✅ Nếu có ảnh, insert vào bảng review_images
    if ($image_name && isset($review_id)) {
        $imgStmt = $pdo->prepare('
            INSERT INTO review_images (review_id, image_path, created_at)
            VALUES (?, ?, NOW())
        ');
        $imgStmt->execute([
            $review_id,
            'reviews/' . $image_name
        ]);
    }

    // ✅ Log hoạt động (optional)
    logActivity($user_id, 'create_review', 'Product ID: ' . $product_id);

    // ✅ Trả về response thành công
    $response['success'] = true;
    $response['message'] = 'Đánh giá của bạn đã được gửi! Admin sẽ xem xét và duyệt trong thời gian sớm nhất.';
    $response['data'] = [
        'review_id' => $review_id,
        'product_id' => $product_id,
        'product_name' => $product['name']
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log('Review API Error: ' . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>