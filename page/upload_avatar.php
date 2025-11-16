<?php

/**
 * page/upload_avatar.php - Avatar Upload Handler (Fixed)
 * Xử lý upload và cập nhật avatar - đã sửa lỗi mất ảnh khi F5
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

// Disable output buffering for JSON response
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$pdo = getPDO();

// ===== CONFIGURATION =====
$upload_dir = __DIR__ . '/../uploads/avatars/';
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$max_size = 5 * 1024 * 1024; // 5MB

// Create upload directory if not exists
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['ok' => false, 'message' => 'Không thể tạo thư mục uploads']);
        exit;
    }
}

// ===== CHECK FILE =====
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $error_msg = 'Không có file được upload';
    if (isset($_FILES['avatar']['error'])) {
        switch ($_FILES['avatar']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_msg = 'File quá lớn';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_msg = 'File chỉ được upload một phần';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_msg = 'Không có file nào được upload';
                break;
        }
    }
    echo json_encode(['ok' => false, 'message' => $error_msg]);
    exit;
}

$file = $_FILES['avatar'];

// Validate file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['ok' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)']);
    exit;
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['ok' => false, 'message' => 'Kích thước file tối đa 5MB']);
    exit;
}

// ===== VALIDATE IMAGE =====
$image_info = @getimagesize($file['tmp_name']);
if ($image_info === false) {
    echo json_encode(['ok' => false, 'message' => 'File không phải là ảnh hợp lệ']);
    exit;
}

// ===== GENERATE FILENAME =====
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (empty($extension)) {
    $extension = 'jpg';
}
$new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
$upload_path = $upload_dir . $new_filename;
$db_path = 'uploads/avatars/' . $new_filename;

// ===== DELETE OLD AVATAR =====
try {
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $old_avatar = $stmt->fetchColumn();

    if ($old_avatar && file_exists(__DIR__ . '/../' . $old_avatar)) {
        @unlink(__DIR__ . '/../' . $old_avatar);
    }
} catch (Exception $e) {
    // Continue even if delete fails
    error_log('Failed to delete old avatar: ' . $e->getMessage());
}

// ===== RESIZE IMAGE =====
function resizeImage($source_path, $target_path, $max_width = 300, $max_height = 300)
{
    list($width, $height, $type) = getimagesize($source_path);

    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);

    // If image is smaller than max, don't upscale
    if ($ratio > 1) {
        $ratio = 1;
    }

    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);

    // Create image resource
    $source = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = @imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = @imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source = @imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $source = @imagecreatefromwebp($source_path);
            }
            break;
    }

    if ($source === false || $source === null) {
        return false;
    }

    // Create new image
    $target = imagecreatetruecolor($new_width, $new_height);

    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
        imagefilledrectangle($target, 0, 0, $new_width, $new_height, $transparent);
    }

    // Resize
    imagecopyresampled($target, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Save
    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($target, $target_path, 90);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($target, $target_path, 9);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($target, $target_path);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                $success = imagewebp($target, $target_path, 90);
            }
            break;
    }

    imagedestroy($source);
    imagedestroy($target);

    return $success;
}

// ===== UPLOAD FILE =====
try {
    // Try to resize and save
    $uploaded = false;

    if (resizeImage($file['tmp_name'], $upload_path, 300, 300)) {
        $uploaded = true;
    } else {
        // Fallback: move without resize
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $uploaded = true;
        }
    }

    if ($uploaded) {
        // Update database
        $stmt = $pdo->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE user_id = ?");
        if ($stmt->execute([$db_path, $user_id])) {
            // Update session
            $_SESSION['user']['avatar'] = $db_path;

            echo json_encode([
                'ok' => true,
                'message' => 'Cập nhật avatar thành công!',
                'avatar_url' => '../' . $db_path . '?v=' . time()
            ]);
        } else {
            // Database update failed, delete uploaded file
            @unlink($upload_path);
            echo json_encode(['ok' => false, 'message' => 'Không thể cập nhật database']);
        }
    } else {
        throw new Exception('Không thể lưu file');
    }
} catch (Exception $e) {
    // Clean up on error
    if (file_exists($upload_path)) {
        @unlink($upload_path);
    }
    echo json_encode(['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
