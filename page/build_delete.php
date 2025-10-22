<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';

// ✅ Lấy ID cấu hình cần xóa
$build_id = $_GET['id'] ?? 0;
if (!$build_id) {
    $message = "Thiếu ID cấu hình";
    $success = false;
} else {
    // ✅ Gọi API thật để xóa
    $apiUrl = dirname(SITE_URL) . '/api/delete_build.php';
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['build_id' => $build_id]),
    ]);
    $result = curl_exec($ch);
    // 🧠 Debug: in ra nội dung phản hồi của API
    //var_dump($result);
    //exit;
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        $message = "Lỗi CURL: $error";
        $success = false;
    } else {
        $data = json_decode($result, true);
        $message = $data['message'] ?? 'Lỗi không xác định';
        $success = !empty($data['success']);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Xóa cấu hình</title>
<style>
body { font-family: Arial; text-align: center; margin-top: 100px; background:#f4f7fb; color:#333; }
.success { color: #28a745; }
.error { color: #dc3545; }
a { display:inline-block; margin-top:20px; text-decoration:none; color:#1a73e8; font-weight:bold; }
a:hover { text-decoration:underline; }
</style>
</head>
<body>
<h1 class="<?= $success ? 'success' : 'error' ?>">
  <?= htmlspecialchars($message) ?>
</h1>
<a href="builds.php">⬅ Quay lại danh sách</a>
</body>
</html>
