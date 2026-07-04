<?php

/**
 * page/google_callback.php - Google OAuth Callback Handler
 * Updated: Đồng bộ avatar Google chính xác
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../google_config.php';
require_once __DIR__ . '/../db.php';

// Kiểm tra có code trả về không
if (!isset($_GET['code'])) {
    $_SESSION['error'] = 'Không nhận được mã xác thực từ Google!';
    header('Location: login.php');
    exit;
}

try {
    // Khởi tạo Google Client
    $client = getGoogleClient();

    // Lấy access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    // Kiểm tra lỗi
    if (isset($token['error'])) {
        throw new Exception('Lỗi xác thực: ' . $token['error']);
    }

    // Set access token
    $client->setAccessToken($token['access_token']);

    // Lấy thông tin user từ Google
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();

    // Thông tin user
    $google_id = $google_account_info->id;
    $email = $google_account_info->email;
    $name = $google_account_info->name;
    $google_token = $token['access_token'];

    // ✅ XỬ LÝ AVATAR - Lấy ảnh độ phân giải cao
    $picture = $google_account_info->picture;

    // Nếu là URL từ Google, tối ưu hóa để lấy ảnh chất lượng cao
    if (!empty($picture) && strpos($picture, 'googleusercontent.com') !== false) {
        // Loại bỏ size parameter cũ (nếu có)
        $picture = preg_replace('/=s\d+-c/', '', $picture);
        // Thêm parameter để lấy ảnh size 400x400 (chất lượng cao)
        if (strpos($picture, '?') !== false) {
            $picture .= '&sz=400';
        } else {
            $picture .= '?sz=400';
        }
    }

    // Nếu không có ảnh từ Google, dùng UI Avatars
    if (empty($picture)) {
        $picture = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=667eea&color=fff&size=200&bold=true';
    }

    // Log thông tin debug
    error_log("=== Google OAuth Login ===");
    error_log("Google ID: " . $google_id);
    error_log("Email: " . $email);
    error_log("Name: " . $name);
    error_log("Avatar URL: " . $picture);

    // Kết nối database
    $pdo = getPDO();

    // Kiểm tra user đã tồn tại chưa (theo google_id)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->execute([$google_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // ===== USER ĐÃ TỒN TẠI - CẬP NHẬT THÔNG TIN =====
        error_log("Updating existing user: " . $user['username']);

        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = ?, 
                avatar = ?, 
                google_token = ?,
                last_login = NOW(),
                updated_at = NOW()
            WHERE google_id = ?
        ");

        $update_success = $stmt->execute([
            $name,
            $picture,
            $google_token,
            $google_id
        ]);

        if (!$update_success) {
            throw new Exception('Không thể cập nhật thông tin user');
        }

        // Lấy lại thông tin user sau khi update
        $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->execute([$google_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("✅ Updated user successfully. New avatar: " . $user['avatar']);
    } else {
        // ===== USER MỚI - TẠO TÀI KHOẢN =====
        error_log("Creating new user or linking to existing account...");

        // Kiểm tra email đã tồn tại chưa (trường hợp đã đăng ký bằng email/password)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            // Email đã tồn tại - Liên kết Google với tài khoản cũ
            error_log("Email exists. Linking Google to account: " . $existing_user['username']);

            $stmt = $pdo->prepare("
                UPDATE users 
                SET google_id = ?,
                    google_token = ?,
                    avatar = ?,
                    full_name = ?,
                    last_login = NOW(),
                    updated_at = NOW()
                WHERE email = ?
            ");

            $link_success = $stmt->execute([
                $google_id,
                $google_token,
                $picture,
                $name,
                $email
            ]);

            if (!$link_success) {
                throw new Exception('Không thể liên kết tài khoản Google');
            }

            // Lấy lại user sau khi link
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("✅ Linked Google account successfully");
        } else {
            // Tạo user hoàn toàn mới
            error_log("Creating completely new user...");

            // Tạo username từ email (phần trước @)
            $username = explode('@', $email)[0];

            // Loại bỏ ký tự đặc biệt trong username
            $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);

            // Nếu username rỗng sau khi filter, dùng tên
            if (empty($username)) {
                $username = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(str_replace(' ', '', $name)));
            }

            // Đảm bảo username không rỗng
            if (empty($username)) {
                $username = 'user' . substr($google_id, -6);
            }

            // Đảm bảo username unique
            $base_username = $username;
            $counter = 1;
            while (true) {
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if (!$stmt->fetch()) break;
                $username = $base_username . $counter;
                $counter++;
            }

            // Insert user mới
            $stmt = $pdo->prepare("
                INSERT INTO users 
                (username, email, full_name, avatar, google_id, google_token, 
                 password_hash, role, status, created_at, last_login) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'customer', 'active', NOW(), NOW())
            ");

            // Password hash ngẫu nhiên cho tài khoản Google (không dùng password)
            $dummy_password = password_hash(random_bytes(32), PASSWORD_BCRYPT);

            $insert_success = $stmt->execute([
                $username,
                $email,
                $name,
                $picture,
                $google_id,
                $google_token,
                $dummy_password
            ]);

            if (!$insert_success) {
                throw new Exception('Không thể tạo tài khoản mới');
            }

            // Lấy thông tin user vừa tạo
            $user_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("✅ Created new Google user: " . $user['username']);
        }
    }

    // Kiểm tra user có được lấy thành công không
    if (!$user) {
        throw new Exception('Không thể lấy thông tin user sau khi xử lý');
    }

    // ===== LƯU THÔNG TIN VÀO SESSION =====
    $_SESSION['user'] = [
        'user_id' => $user['user_id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'avatar' => $user['avatar'], // Avatar từ database (đã được update)
        'role' => $user['role'],
        'google_id' => $user['google_id'],
        'login_type' => 'google'
    ];

    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    // Log session info
    error_log("✅ Session created successfully");
    error_log("Username: " . $_SESSION['user']['username']);
    error_log("Google ID: " . $_SESSION['user']['google_id']);
    error_log("Avatar in session: " . $_SESSION['user']['avatar']);
    error_log("========================");

    // Chuyển hướng về trang chủ
    $redirect = ($user['role'] === 'admin') ? 'admin.php' : '../index.php';
    header("Location: $redirect");
    exit;
} catch (Exception $e) {
    // Log lỗi chi tiết
    error_log("❌ Google OAuth Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    $_SESSION['error'] = 'Đăng nhập Google thất bại: ' . $e->getMessage();
    header('Location: login.php');
    exit;
}
