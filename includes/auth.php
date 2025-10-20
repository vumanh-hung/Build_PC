<?php
// ensures DB available
require_once __DIR__ . '/../db.php';

// ensure session config is consistent across app
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', 'localhost'); // nếu không phải localhost, thay bằng domain thật
    session_start();
}

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
    // đảm bảo session đã start (đã xử lý ở trên nhưng gọi lại an toàn)
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_path', '/');
        ini_set('session.cookie_domain', 'localhost');
        session_start();
    }

    // Lưu thông tin sang hai vị trí: user (mảng) và user_id (biến rút gọn)
    $_SESSION['user_id'] = $user['user_id'] ?? $user['id'] ?? null;

    $_SESSION['user'] = [
        'user_id'   => $_SESSION['user_id'],
        'username'  => $user['username'] ?? '',
        'full_name' => $user['full_name'] ?? '',
        'role'      => $user['role'] ?? ''
    ];
}
?>
