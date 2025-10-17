<?php
require_once __DIR__ . '/../db.php';

function authenticate_user($username, $password) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) return false;

    // So sánh cả 2 trường hợp: mật khẩu mã hóa hoặc chưa mã hóa
    $password_hash = hash("sha256", $password);
    if ($user['password_hash'] === $password || $user['password_hash'] === $password_hash) {
        return $user;
    }
    return false;
}

function login_user_session($user) {
    $_SESSION['user'] = [
        'user_id'   => $user['user_id'],
        'username'  => $user['username'],
        'full_name' => $user['full_name'],
        'role'      => $user['role']
    ];
}
?>
