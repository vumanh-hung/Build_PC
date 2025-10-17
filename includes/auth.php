<?php
require_once __DIR__ . '/../db.php';

function authenticate_user($username, $password) {
    global $pdo;
    
    // Lấy thông tin user từ database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) return false;

 $hash = $user['password_hash'];

    // Trường hợp 1: Mật khẩu được mã hóa bằng password_hash()
    if (password_verify($password, $hash)) {
        return $user;
    }

    // Trường hợp 2: Tài khoản cũ – lưu mật khẩu thường hoặc hash SHA256
    if ($hash === $password || $hash === hash('sha256', $password)) {
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
