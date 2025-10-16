<?php
session_start();
require_once("db.php");

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["username"], $data["password"])) {
    echo json_encode(["success" => false, "message" => "Thiếu thông tin đăng nhập"]);
    exit;
}

$username = trim($data["username"]);
$password = trim($data["password"]);

$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && hash("sha256", $password) === $user["password"]) {
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["username"] = $user["username"];
    $_SESSION["role"] = $user["role"];
    echo json_encode(["success" => true, "message" => "Đăng nhập thành công"]);
} else {
    echo json_encode(["success" => false, "message" => "Sai tài khoản hoặc mật khẩu"]);
}
?>
