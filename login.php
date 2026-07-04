<?php
session_start();
require_once '../includes/auth.php';

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['username'], $data['password'])) {
    echo json_encode(["success" => false, "message" => "Thiếu thông tin đăng nhập"]);
    exit;
}

$user = authenticate_user($data['username'], $data['password']);

if ($user) {
    login_user_session($user);
    echo json_encode(["success" => true, "message" => "Đăng nhập thành công", "role" => $user['role']]);
} else {
    echo json_encode(["success" => false, "message" => "Sai tên đăng nhập hoặc mật khẩu"]);
}
?>
