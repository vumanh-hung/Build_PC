<?php

/**
 * page/account.php - User Account Management Page
 * Quản lý thông tin tài khoản người dùng với upload avatar
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ================================================
// REQUIRE LOGIN
// ================================================

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// ================================================
// INITIALIZATION
// ================================================

$pdo = getPDO();
$user_id = getCurrentUserId();
$user = getUserById($user_id);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ================================================
// PAGE CONFIGURATION
// ================================================

$pageTitle = 'Tài khoản của tôi - BuildPC.vn';
$additionalCSS = [
    'assets/css/account.css',
    'assets/css/footer.css'
];
$basePath = '../';

// ================================================
// HANDLE FORM SUBMISSIONS
// ================================================

$success_message = '';
$error_message = '';

// Update Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validation
    if (empty($full_name)) {
        $error_message = 'Vui lòng nhập họ tên';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Email không hợp lệ';
    } elseif (!empty($phone) && !preg_match('/^0\d{9}$/', $phone)) {
        $error_message = 'Số điện thoại không hợp lệ (phải có 10 số và bắt đầu bằng 0)';
    } else {
        // Check if email exists for other users
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);

        if ($stmt->fetch()) {
            $error_message = 'Email này đã được sử dụng bởi tài khoản khác';
        } else {
            // Update user info
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW()
                WHERE user_id = ?
            ");

            if ($stmt->execute([$full_name, $email, $phone, $address, $user_id])) {
                // Update session
                $_SESSION['user']['full_name'] = $full_name;
                $_SESSION['user']['email'] = $email;

                // Reload user data
                $user = getUserById($user_id);

                $success_message = 'Cập nhật thông tin thành công!';
            } else {
                $error_message = 'Có lỗi xảy ra, vui lòng thử lại';
            }
        }
    }
}

// Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password)) {
        $error_message = 'Vui lòng nhập mật khẩu hiện tại';
    } elseif (empty($new_password)) {
        $error_message = 'Vui lòng nhập mật khẩu mới';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Mật khẩu mới phải có ít nhất 6 ký tự';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Mật khẩu xác nhận không khớp';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current_password, $hash)) {
            $error_message = 'Mật khẩu hiện tại không đúng';
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?");

            if ($stmt->execute([$hashed_password, $user_id])) {
                $success_message = 'Đổi mật khẩu thành công!';
            } else {
                $error_message = 'Có lỗi xảy ra, vui lòng thử lại';
            }
        }
    }
}

// ================================================
// GET USER STATISTICS
// ================================================

// Get order summary
$order_summary = getOrderSummary($user_id);

// Get total builds
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM builds WHERE user_id = ?");
$stmt->execute([$user_id]);
$builds_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get recent orders
$recent_orders = getUserOrders($user_id, 5, 0);

// ================================================
// INCLUDE HEADER
// ================================================

include __DIR__ . '/../includes/header.php';
?>

<!-- ===== ACCOUNT CONTAINER ===== -->
<div class="account-wrapper">
    <div class="account-container">

        <!-- ===== SIDEBAR ===== -->
        <aside class="account-sidebar">
            <!-- User Info Card -->
            <div class="user-info-card">
                <div class="user-avatar-wrapper">
                    <?php
                    // ===== AVATAR LOGIC =====
                    $avatarUrl = '';
                    $isGoogleAccount = !empty($user['google_id']);

                    if (!empty($user['avatar'])) {
                        // Nếu là URL Google (bắt đầu bằng http)
                        if (strpos($user['avatar'], 'http') === 0) {
                            $avatarUrl = $user['avatar'];
                        }
                        // Nếu là avatar local
                        elseif (file_exists(__DIR__ . '/../' . $user['avatar'])) {
                            $avatarUrl = '../' . $user['avatar'] . '?v=' . time();
                        }
                    }

                    // Fallback: Nếu không có avatar, dùng UI Avatars
                    if (empty($avatarUrl)) {
                        $userName = $user['full_name'] ?? $user['username'] ?? 'User';
                        $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=667eea&color=fff&size=200&bold=true';
                    }
                    ?>

                    <!-- ✅ AVATAR CONTAINER -->
                    <div class="user-avatar">
                        <img src="<?= htmlspecialchars($avatarUrl) ?>"
                            alt="<?= htmlspecialchars($user['full_name'] ?? 'Avatar') ?>"
                            id="avatarPreview"
                            width="120"
                            height="120"
                            onerror="this.src='https://ui-avatars.com/api/?name=User&background=0D8ABC&color=fff&size=200';">
                    </div>

                    <?php if (!$isGoogleAccount): ?>
                        <!-- Nút đổi avatar -->
                        <button type="button" class="btn-change-avatar" id="btnChangeAvatar" title="Đổi ảnh đại diện">
                            <i class="fa-solid fa-camera"></i>
                        </button>
                        <input type="file" id="avatarInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display: none;">
                    <?php else: ?>
                        <!-- Badge Google -->
                        <div class="google-avatar-badge" title="Avatar từ tài khoản Google">
                            <i class="fa-brands fa-google"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <h3 class="user-name"><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></h3>
                <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>

                <?php if ($isGoogleAccount): ?>
                    <span class="google-account-badge">
                        <i class="fa-brands fa-google"></i>
                        Đăng nhập bằng Google
                    </span>
                <?php endif; ?>

                <span class="user-role-badge <?= $user['role'] === 'admin' ? 'admin' : 'user' ?>">
                    <?= $user['role'] === 'admin' ? '👑 Quản trị viên' : '👤 Khách hàng' ?>
                </span>
            </div>

            <!-- Navigation Menu -->
            <nav class="account-menu">
                <a href="#profile" class="menu-item active" data-section="profile">
                    <i class="fa-solid fa-user"></i>
                    <span>Thông tin tài khoản</span>
                </a>
                <a href="#orders" class="menu-item" data-section="orders">
                    <i class="fa-solid fa-box"></i>
                    <span>Đơn hàng của tôi</span>
                    <?php if ($order_summary['count_pending'] > 0): ?>
                        <span class="badge"><?= $order_summary['count_pending'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="#builds" class="menu-item" data-section="builds">
                    <i class="fa-solid fa-tools"></i>
                    <span>Cấu hình của tôi</span>
                    <?php if ($builds_count > 0): ?>
                        <span class="badge"><?= $builds_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="#password" class="menu-item" data-section="password">
                    <i class="fa-solid fa-key"></i>
                    <span>Đổi mật khẩu</span>
                </a>
                <a href="logout.php" class="menu-item logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Đăng xuất</span>
                </a>
            </nav>

            <!-- Stats Summary -->
            <div class="stats-summary">
                <div class="stat-item">
                    <i class="fa-solid fa-box"></i>
                    <div class="stat-info">
                        <span class="stat-value"><?= $order_summary['total_orders'] ?></span>
                        <span class="stat-label">Đơn hàng</span>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fa-solid fa-tools"></i>
                    <div class="stat-info">
                        <span class="stat-value"><?= $builds_count ?></span>
                        <span class="stat-label">Cấu hình</span>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fa-solid fa-coins"></i>
                    <div class="stat-info">
                        <span class="stat-value"><?= formatPrice($order_summary['total_paid']) ?>₫</span>
                        <span class="stat-label">Đã chi tiêu</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ===== MAIN CONTENT ===== -->
        <main class="account-content">

            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i>
                    <span><?= htmlspecialchars($success_message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <!-- ===== PROFILE SECTION ===== -->
            <section id="profile-section" class="content-section active">
                <div class="section-header">
                    <h1 class="section-title">
                        <i class="fa-solid fa-user"></i>
                        Thông tin tài khoản
                    </h1>
                    <p class="section-desc">Quản lý thông tin cá nhân của bạn</p>
                </div>

                <form method="POST" class="profile-form">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">
                                <i class="fa-solid fa-user"></i>
                                Họ và tên <span class="required">*</span>
                            </label>
                            <input type="text"
                                id="full_name"
                                name="full_name"
                                value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                                required
                                placeholder="Nhập họ và tên">
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fa-solid fa-envelope"></i>
                                Email <span class="required">*</span>
                            </label>
                            <input type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($user['email']) ?>"
                                required
                                placeholder="email@example.com">
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fa-solid fa-phone"></i>
                                Số điện thoại
                            </label>
                            <input type="tel"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                placeholder="0123456789"
                                pattern="0\d{9}">
                        </div>

                        <div class="form-group">
                            <label for="username">
                                <i class="fa-solid fa-at"></i>
                                Tên đăng nhập
                            </label>
                            <input type="text"
                                id="username"
                                value="<?= htmlspecialchars($user['username']) ?>"
                                disabled
                                title="Không thể thay đổi tên đăng nhập">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="address">
                            <i class="fa-solid fa-location-dot"></i>
                            Địa chỉ
                        </label>
                        <textarea id="address"
                            name="address"
                            rows="3"
                            placeholder="Nhập địa chỉ của bạn"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save"></i>
                            Lưu thay đổi
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fa-solid fa-rotate-left"></i>
                            Hủy bỏ
                        </button>
                    </div>
                </form>

                <!-- Account Info -->
                <div class="info-cards">
                    <div class="info-card">
                        <div class="info-card-header">
                            <i class="fa-solid fa-calendar"></i>
                            <h3>Ngày tham gia</h3>
                        </div>
                        <p><?= formatDate($user['created_at'], 'd/m/Y') ?></p>
                    </div>

                    <div class="info-card">
                        <div class="info-card-header">
                            <i class="fa-solid fa-clock"></i>
                            <h3>Lần cập nhật cuối</h3>
                        </div>
                        <p><?= formatDate($user['updated_at'] ?? $user['created_at'], 'd/m/Y H:i') ?></p>
                    </div>

                    <div class="info-card">
                        <div class="info-card-header">
                            <i class="fa-solid fa-shield-halved"></i>
                            <h3>Trạng thái tài khoản</h3>
                        </div>
                        <p class="status-active">
                            <i class="fa-solid fa-circle-check"></i>
                            Đang hoạt động
                        </p>
                    </div>
                </div>
            </section>

            <!-- ===== ORDERS SECTION ===== -->
            <section id="orders-section" class="content-section">
                <div class="section-header">
                    <h1 class="section-title">
                        <i class="fa-solid fa-box"></i>
                        Đơn hàng của tôi
                    </h1>
                    <p class="section-desc">Quản lý và theo dõi đơn hàng</p>
                </div>

                <?php if (empty($recent_orders)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-box-open"></i>
                        <h3>Chưa có đơn hàng nào</h3>
                        <p>Bạn chưa đặt hàng. Hãy khám phá sản phẩm của chúng tôi!</p>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fa-solid fa-shopping-bag"></i>
                            Mua sắm ngay
                        </a>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-id">
                                        <i class="fa-solid fa-hashtag"></i>
                                        <strong>Đơn hàng #<?= $order['order_id'] ?></strong>
                                    </div>
                                    <span class="order-status status-<?= $order['status'] ?>">
                                        <?= getOrderStatus($order['status'])['label'] ?? $order['status'] ?>
                                    </span>
                                </div>

                                <div class="order-body">
                                    <div class="order-info">
                                        <p>
                                            <i class="fa-solid fa-calendar"></i>
                                            <?= formatDate($order['created_at'], 'd/m/Y H:i') ?>
                                        </p>
                                        <p>
                                            <i class="fa-solid fa-boxes-stacked"></i>
                                            <?= $order['item_count'] ?> sản phẩm
                                        </p>
                                        <p class="order-total">
                                            <i class="fa-solid fa-coins"></i>
                                            <strong><?= formatPriceVND($order['total_price']) ?></strong>
                                        </p>
                                    </div>
                                </div>

                                <div class="order-footer">
                                    <a href="order-detail.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-eye"></i>
                                        Chi tiết
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="section-footer">
                        <a href="orders.php" class="btn btn-secondary">
                            Xem tất cả đơn hàng
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ===== BUILDS SECTION ===== -->
            <section id="builds-section" class="content-section">
                <div class="section-header">
                    <h1 class="section-title">
                        <i class="fa-solid fa-tools"></i>
                        Cấu hình của tôi
                    </h1>
                    <p class="section-desc">Quản lý các cấu hình máy tính</p>
                </div>

                <?php if ($builds_count == 0): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                        <h3>Chưa có cấu hình nào</h3>
                        <p>Bạn chưa tạo cấu hình PC nào. Hãy bắt đầu xây dựng cấu hình đầu tiên của bạn!</p>
                        <a href="builds.php" class="btn btn-primary">
                            <i class="fa-solid fa-plus-circle"></i>
                            Tạo cấu hình mới
                        </a>
                    </div>
                <?php else: ?>
                    <?php
                    // Get user builds
                    $user_builds = getUserBuilds($user_id, 6);
                    ?>

                    <div class="builds-grid-account">
                        <?php foreach ($user_builds as $build): ?>
                            <div class="build-card-account">
                                <div class="build-card-header">
                                    <h3 class="build-name">
                                        <i class="fa-solid fa-desktop"></i>
                                        <?= escape($build['name']) ?>
                                    </h3>
                                    <span class="build-date">
                                        <i class="fa-solid fa-calendar"></i>
                                        <?= formatDate($build['created_at'], 'd/m/Y') ?>
                                    </span>
                                </div>

                                <div class="build-card-body">
                                    <?php if (!empty($build['description'])): ?>
                                        <p class="build-description">
                                            <?= escape(truncateText($build['description'], 80)) ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="build-stats">
                                        <div class="stat-item-build">
                                            <i class="fa-solid fa-box"></i>
                                            <span><?= $build['item_count'] ?? 0 ?> linh kiện</span>
                                        </div>
                                        <div class="stat-item-build build-price-stat">
                                            <i class="fa-solid fa-tag"></i>
                                            <span class="price-highlight"><?= formatPriceVND($build['total_price']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="build-card-actions">
                                    <a href="build_manage.php?id=<?= $build['build_id'] ?>"
                                        class="btn-build-action btn-view">
                                        <i class="fa-solid fa-eye"></i>
                                        Chi tiết
                                    </a>
                                    <button class="btn-build-action btn-cart-add"
                                        onclick="addBuildToCart(<?= $build['build_id'] ?>)">
                                        <i class="fa-solid fa-cart-plus"></i>
                                        Thêm vào giỏ
                                    </button>
                                    <button class="btn-build-action btn-delete-build"
                                        onclick="deleteBuild(<?= $build['build_id'] ?>)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($builds_count > 6): ?>
                        <div class="section-footer">
                            <a href="builds.php" class="btn btn-secondary">
                                Xem tất cả <?= $builds_count ?> cấu hình
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <!-- ===== PASSWORD SECTION ===== -->
            <section id="password-section" class="content-section">
                <div class="section-header">
                    <h1 class="section-title">
                        <i class="fa-solid fa-key"></i>
                        Đổi mật khẩu
                    </h1>
                    <p class="section-desc">Cập nhật mật khẩu để bảo mật tài khoản</p>
                </div>

                <form method="POST" class="password-form">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="current_password">
                            <i class="fa-solid fa-lock"></i>
                            Mật khẩu hiện tại <span class="required">*</span>
                        </label>
                        <input type="password"
                            id="current_password"
                            name="current_password"
                            required
                            placeholder="Nhập mật khẩu hiện tại">
                    </div>

                    <div class="form-group">
                        <label for="new_password">
                            <i class="fa-solid fa-key"></i>
                            Mật khẩu mới <span class="required">*</span>
                        </label>
                        <input type="password"
                            id="new_password"
                            name="new_password"
                            required
                            minlength="6"
                            placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fa-solid fa-shield-halved"></i>
                            Xác nhận mật khẩu mới <span class="required">*</span>
                        </label>
                        <input type="password"
                            id="confirm_password"
                            name="confirm_password"
                            required
                            minlength="6"
                            placeholder="Nhập lại mật khẩu mới">
                    </div>

                    <div class="password-tips">
                        <h4><i class="fa-solid fa-lightbulb"></i> Mẹo tạo mật khẩu mạnh:</h4>
                        <ul>
                            <li>Sử dụng ít nhất 8 ký tự</li>
                            <li>Kết hợp chữ hoa, chữ thường, số và ký tự đặc biệt</li>
                            <li>Không sử dụng thông tin cá nhân dễ đoán</li>
                            <li>Không tái sử dụng mật khẩu cũ</li>
                        </ul>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i>
                            Đổi mật khẩu
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fa-solid fa-times"></i>
                            Hủy bỏ
                        </button>
                    </div>
                </form>
            </section>

        </main>
    </div>
</div>

<!-- JavaScript -->
<script src="../assets/js/account.js?v=<?= time() ?>"></script>

<?php
// Include Footer
include __DIR__ . '/../includes/footer.php';
?>