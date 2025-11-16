<?php

/**
 * includes/header.php - Optimized Global Header
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-detect base path
$basePath = (strpos($_SERVER['PHP_SELF'], '/page/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false)
    ? '../'
    : './';

// Get cart count
$cartCount = 0;
if (isset($_SESSION['user']['user_id'])) {
    require_once __DIR__ . '/../functions.php';
    $cartCount = getCartCount($_SESSION['user']['user_id']);
}

// Get current page for active nav
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

function isActivePage($page)
{
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'BuildPC.vn - Cấu hình máy tính theo ý bạn' ?></title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Core CSS -->
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/header.css?v=1.0">

    <!-- Page-specific CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?= $basePath . $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<body>
    <header class="main-header">
        <!-- Logo -->
        <div class="header-logo">
            <a href="<?= $basePath ?>index.php" class="logo-link">
                <span class="logo-text">🖥️ BuildPC.vn</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="header-nav">
            <a href="<?= $basePath ?>index.php" class="nav-link <?= isActivePage('index') ?>">Trang chủ</a>
            <a href="<?= $basePath ?>page/products.php" class="nav-link <?= isActivePage('products') ?>">Sản phẩm</a>
            <a href="<?= $basePath ?>page/brands.php" class="nav-link <?= isActivePage('brands') ?>">Thương hiệu</a>
            <a href="<?= $basePath ?>page/builds.php" class="nav-link <?= isActivePage('builds') ?>">Xây dựng cấu hình</a>
            <a href="<?= $basePath ?>page/about.php" class="nav-link <?= isActivePage('about') ?>">Giới thiệu</a>
            <a href="<?= $basePath ?>page/contact.php" class="nav-link <?= isActivePage('contact') ?>">Liên hệ</a>
        </nav>

        <!-- Actions -->
        <div class="header-actions">
            <!-- Search -->
            <form class="search-box" method="GET" action="<?= $basePath ?>page/products.php">
                <input type="text" name="keyword" placeholder="Tìm sản phẩm..."
                    value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>" class="search-input">
                <button type="submit" class="search-btn">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>

            <!-- Cart -->
            <a href="<?= $basePath ?>page/cart.php" class="cart-link" id="headerCartLink">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="cart-text">Giỏ hàng</span>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-count" id="headerCartCount"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>

            <!-- Auth Section -->
            <div class="auth-section">
                <?php if (!isset($_SESSION['user'])): ?>
                    <a href="<?= $basePath ?>page/login.php" class="login-btn">
                        <i class="fa-solid fa-user"></i>
                        <span>Đăng nhập</span>
                    </a>
                <?php else: ?>
                    <!-- ⭐ NÚT TÀI KHOẢN -->
                    <a href="<?= $basePath ?>page/account.php" class="account-btn" title="Quản lý tài khoản">
                        <i class="fa-solid fa-user-circle"></i>
                        <span>Tài khoản</span>
                    </a>

                    <!-- NÚT ĐĂNG XUẤT -->
                    <a href="<?= $basePath ?>page/logout.php" class="logout-btn">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Đăng xuất</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
    </header>

    <!-- Mobile Navigation -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay">
        <div class="mobile-nav-content">
            <button class="mobile-nav-close" id="mobileNavClose">
                <i class="fa-solid fa-times"></i>
            </button>
            <nav class="mobile-nav">
                <a href="<?= $basePath ?>index.php" class="mobile-nav-link <?= isActivePage('index') ?>">
                    <i class="fa-solid fa-house"></i> Trang chủ
                </a>
                <a href="<?= $basePath ?>page/products.php" class="mobile-nav-link <?= isActivePage('products') ?>">
                    <i class="fa-solid fa-box"></i> Sản phẩm
                </a>
                <a href="<?= $basePath ?>page/brands.php" class="mobile-nav-link <?= isActivePage('brands') ?>">
                    <i class="fa-solid fa-tags"></i> Thương hiệu
                </a>
                <a href="<?= $basePath ?>page/builds.php" class="mobile-nav-link <?= isActivePage('builds') ?>">
                    <i class="fa-solid fa-tools"></i> Xây dựng cấu hình
                </a>
                <a href="<?= $basePath ?>page/about.php" class="mobile-nav-link <?= isActivePage('about') ?>">
                    <i class="fa-solid fa-info-circle"></i> Giới thiệu
                </a>
                <a href="<?= $basePath ?>page/contact.php" class="mobile-nav-link <?= isActivePage('contact') ?>">
                    <i class="fa-solid fa-envelope"></i> Liên hệ
                </a>
            </nav>
        </div>
    </div>

    <script>
        window.SITE_CONFIG = {
            BASE_PATH: <?= json_encode($basePath) ?>,
            IS_LOGGED_IN: <?= isset($_SESSION['user']) ? 'true' : 'false' ?>,
            USER_ID: <?= isset($_SESSION['user']['user_id']) ? $_SESSION['user']['user_id'] : 'null' ?>
        };

        // Mobile menu
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('mobileMenuToggle');
            const overlay = document.getElementById('mobileNavOverlay');
            const close = document.getElementById('mobileNavClose');

            toggle?.addEventListener('click', () => {
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            });

            close?.addEventListener('click', () => {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            });

            overlay?.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>

    <main class="main-content">