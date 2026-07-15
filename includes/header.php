<?php

/**
 * includes/header.php - Premium Global Header + Mobile Bottom Nav
 * BuildPC.vn
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

// Avatar logic
function getUserAvatar($basePath = '../')
{
    if (!isset($_SESSION['user'])) {
        return 'https://ui-avatars.com/api/?name=Guest&background=2563eb&color=fff&size=128';
    }
    $user = $_SESSION['user'];
    $userName = $user['full_name'] ?? $user['username'] ?? 'User';

    if (!empty($user['avatar'])) {
        if (strpos($user['avatar'], 'http') === 0) return $user['avatar'];
        return $basePath . ltrim($user['avatar'], '/');
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=2563eb&color=fff&size=128';
}

$userAvatar    = getUserAvatar($basePath);
$isLoggedIn    = isset($_SESSION['user']);
$isAdmin       = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
$userName      = $_SESSION['user']['full_name'] ?? $_SESSION['user']['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'BuildPC.vn - Cấu hình máy tính theo ý bạn' ?></title>
    <meta name="description" content="<?= $pageDesc ?? 'BuildPC.vn - Mua linh kiện máy tính, laptop, cấu hình PC gaming chất lượng cao, giá tốt nhất Việt Nam.' ?>">
    <link rel="icon" href="<?= $basePath ?>assets/images/icon.png" type="image/png">

    <!-- Google Fonts: Inter + Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Core CSS: Design System + Header + Mobile Nav + Responsive -->
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/style.css?v=2.0">
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/header.css?v=2.0">
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/mobile_nav.css?v=2.0">
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/mobile.css?v=2.0">

    <!-- Page-specific CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?= $basePath . $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Site Config for JS -->
    <script>
        window.SITE_CONFIG = {
            BASE_PATH: <?= json_encode($basePath) ?>,
            IS_LOGGED_IN: <?= $isLoggedIn ? 'true' : 'false' ?>,
            USER_ID: <?= isset($_SESSION['user']['user_id']) ? $_SESSION['user']['user_id'] : 'null' ?>,
            CART_COUNT: <?= $cartCount ?>
        };
    </script>
</head>

<body class="page-transition">

<!-- ==========================================
     SCROLL PROGRESS BAR
     ========================================== -->
<div class="scroll-progress" id="scrollProgress"></div>

<!-- ==========================================
     MAIN HEADER
     ========================================== -->
<header class="main-header" id="mainHeader">

    <!-- Logo -->
    <div class="header-logo">
        <a href="<?= $basePath ?>index.php" class="logo-link">
            <div class="logo-icon">🖥️</div>
            <span class="logo-text">Build<span class="logo-dot">PC</span>.vn</span>
        </a>
    </div>

    <!-- Desktop Navigation -->
    <nav class="header-nav">
        <a href="<?= $basePath ?>index.php"           class="nav-link <?= isActivePage('index') ?>">Trang chủ</a>
        <a href="<?= $basePath ?>page/products.php"   class="nav-link <?= isActivePage('products') ?>">Sản phẩm</a>
        <a href="<?= $basePath ?>page/brands.php"     class="nav-link <?= isActivePage('brands') ?>">Thương hiệu</a>
        <a href="<?= $basePath ?>page/builds.php"     class="nav-link <?= isActivePage('builds') ?>">Cấu hình PC</a>
        <a href="<?= $basePath ?>page/about.php"      class="nav-link <?= isActivePage('about') ?>">Giới thiệu</a>
        <a href="<?= $basePath ?>page/contact.php"    class="nav-link <?= isActivePage('contact') ?>">Liên hệ</a>
    </nav>

    <!-- Desktop Actions -->
    <div class="header-actions">
        <!-- Search -->
        <form class="search-box" method="GET" action="<?= $basePath ?>page/products.php">
            <input type="text" name="keyword" placeholder="Tìm sản phẩm..."
                value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>" class="search-input" autocomplete="off">
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

        <!-- Auth -->
        <div class="auth-section">
            <?php if (!$isLoggedIn): ?>
                <a href="<?= $basePath ?>page/login.php" class="login-btn">
                    <i class="fa-solid fa-user"></i>
                    <span>Đăng nhập</span>
                </a>
            <?php else: ?>
                <a href="<?= $basePath ?>page/account.php" class="account-btn" title="Quản lý tài khoản">
                    <img src="<?= htmlspecialchars($userAvatar) ?>"
                        alt="Avatar"
                        class="user-avatar"
                        onerror="this.src='https://ui-avatars.com/api/?name=User&background=2563eb&color=fff&size=128'">
                    <span>Tài khoản</span>
                </a>

                <?php if ($isAdmin): ?>
                    <a href="<?= $basePath ?>page/admin.php" class="admin-btn" title="Quản trị Admin">
                        <i class="fa-solid fa-user-shield"></i>
                        <span>Admin</span>
                    </a>
                <?php endif; ?>

                <a href="<?= $basePath ?>page/logout.php" class="logout-btn" title="Đăng xuất">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile Hamburger -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>

<!-- ==========================================
     MOBILE SIDE DRAWER
     ========================================== -->
<div class="mobile-nav-overlay" id="mobileNavOverlay">
    <div class="mobile-nav-content">
        <!-- Drawer Header -->
        <div class="mobile-nav-header">
            <span class="mobile-nav-logo">🖥️ BuildPC.vn</span>
            <button class="mobile-nav-close" id="mobileNavClose" aria-label="Đóng">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <!-- Nav Links -->
        <nav class="mobile-nav">
            <a href="<?= $basePath ?>index.php" class="mobile-nav-link <?= isActivePage('index') ?>">
                <i class="fa-solid fa-house"></i> Trang chủ
            </a>
            <a href="<?= $basePath ?>page/products.php" class="mobile-nav-link <?= isActivePage('products') ?>">
                <i class="fa-solid fa-box-open"></i> Sản phẩm
            </a>
            <a href="<?= $basePath ?>page/brands.php" class="mobile-nav-link <?= isActivePage('brands') ?>">
                <i class="fa-solid fa-tags"></i> Thương hiệu
            </a>
            <a href="<?= $basePath ?>page/builds.php" class="mobile-nav-link <?= isActivePage('builds') ?>">
                <i class="fa-solid fa-screwdriver-wrench"></i> Cấu hình PC
            </a>
            <a href="<?= $basePath ?>page/about.php" class="mobile-nav-link <?= isActivePage('about') ?>">
                <i class="fa-solid fa-circle-info"></i> Giới thiệu
            </a>
            <a href="<?= $basePath ?>page/contact.php" class="mobile-nav-link <?= isActivePage('contact') ?>">
                <i class="fa-solid fa-envelope"></i> Liên hệ
            </a>
        </nav>

        <!-- User Section in Drawer -->
        <div class="mobile-nav-user">
            <?php if ($isLoggedIn): ?>
                <a href="<?= $basePath ?>page/account.php" class="mobile-nav-user-card">
                    <img src="<?= htmlspecialchars($userAvatar) ?>"
                        alt="Avatar"
                        class="mobile-nav-user-avatar"
                        onerror="this.src='https://ui-avatars.com/api/?name=User&background=2563eb&color=fff&size=128'">
                    <div class="mobile-nav-user-info">
                        <strong><?= htmlspecialchars($userName) ?></strong>
                        <span><?= $isAdmin ? '👑 Quản trị viên' : 'Khách hàng' ?></span>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="color:#94a3b8; margin-left:auto;"></i>
                </a>
                <?php if ($isAdmin): ?>
                    <a href="<?= $basePath ?>page/admin.php" class="mobile-nav-link" style="color:#7c3aed; font-weight:700;">
                        <i class="fa-solid fa-user-shield" style="color:#7c3aed;"></i> Trang quản trị
                    </a>
                <?php endif; ?>
                <a href="<?= $basePath ?>page/logout.php" class="mobile-nav-link" style="color:#ef4444;">
                    <i class="fa-solid fa-right-from-bracket" style="color:#ef4444;"></i> Đăng xuất
                </a>
            <?php else: ?>
                <a href="<?= $basePath ?>page/login.php" class="mobile-nav-link" style="color:#2563eb; font-weight:700;">
                    <i class="fa-solid fa-user" style="color:#2563eb;"></i> Đăng nhập
                </a>
                <a href="<?= $basePath ?>page/register.php" class="mobile-nav-link">
                    <i class="fa-solid fa-user-plus"></i> Đăng ký tài khoản
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ==========================================
     MOBILE SEARCH OVERLAY
     ========================================== -->
<div class="mobile-search-overlay" id="mobileSearchOverlay">
    <form class="mobile-search-box" method="GET" action="<?= $basePath ?>page/products.php">
        <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8; padding-left:8px; font-size:16px; flex-shrink:0;"></i>
        <input type="text" name="keyword" class="mobile-search-input"
            placeholder="Tìm sản phẩm, thương hiệu..."
            value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>"
            id="mobileSearchInput" autocomplete="off" autofocus>
        <button type="button" class="mobile-search-close" id="mobileSearchClose" aria-label="Đóng">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <button type="submit" class="mobile-search-btn">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </form>
</div>

<!-- ==========================================
     MOBILE BOTTOM NAVIGATION BAR
     ========================================== -->
<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <div class="mobile-bottom-nav__inner">
        <!-- Home -->
        <a href="<?= $basePath ?>index.php" class="mobile-nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i>
            <span>Trang chủ</span>
        </a>

        <!-- Products -->
        <a href="<?= $basePath ?>page/products.php" class="mobile-nav-item <?= $currentPage === 'products' || $currentPage === 'products_template' ? 'active' : '' ?>">
            <i class="fa-solid fa-box-open"></i>
            <span>Sản phẩm</span>
        </a>

        <!-- Search trigger -->
        <button class="mobile-nav-item" id="mobileSearchTrigger" type="button" aria-label="Tìm kiếm">
            <i class="fa-solid fa-magnifying-glass"></i>
            <span>Tìm kiếm</span>
        </button>

        <!-- Cart -->
        <a href="<?= $basePath ?>page/cart.php" class="mobile-nav-item <?= $currentPage === 'cart' ? 'active' : '' ?>" style="position:relative;">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Giỏ hàng</span>
            <?php if ($cartCount > 0): ?>
                <span class="mobile-badge" id="mobileCartBadge"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>

        <!-- Account -->
        <a href="<?= $basePath ?><?= $isLoggedIn ? 'page/account.php' : 'page/login.php' ?>"
            class="mobile-nav-item <?= ($currentPage === 'account' || $currentPage === 'login') ? 'active' : '' ?>">
            <?php if ($isLoggedIn): ?>
                <img src="<?= htmlspecialchars($userAvatar) ?>"
                    alt="Avatar"
                    style="width:24px;height:24px;border-radius:50%;object-fit:cover;border:2px solid #dbeafe;"
                    onerror="this.src='https://ui-avatars.com/api/?name=U&background=2563eb&color=fff&size=48'">
            <?php else: ?>
                <i class="fa-solid fa-user"></i>
            <?php endif; ?>
            <span><?= $isLoggedIn ? 'Tài khoản' : 'Đăng nhập' ?></span>
        </a>
    </div>
</nav>

<!-- ==========================================
     TOAST CONTAINER
     ========================================== -->
<div class="toast-container" id="toastContainer"></div>

<!-- Back to Top -->
<button class="back-to-top" id="backToTop" aria-label="Về đầu trang">
    <i class="fa-solid fa-chevron-up"></i>
</button>

<!-- ==========================================
     GLOBAL JAVASCRIPT
     ========================================== -->
<script>
(function () {
    // ── Header scroll shrink ──────────────────
    const header = document.getElementById('mainHeader');
    const progress = document.getElementById('scrollProgress');
    const backTop = document.getElementById('backToTop');

    function onScroll() {
        const scrollY = window.scrollY;
        const docH = document.documentElement.scrollHeight - window.innerHeight;

        // Shrink header
        header && header.classList.toggle('scrolled', scrollY > 60);

        // Progress bar
        if (progress) progress.style.width = (docH > 0 ? (scrollY / docH * 100) : 0) + '%';

        // Back to top
        if (backTop) {
            if (scrollY > 150) {
                backTop.classList.add('visible');
            } else {
                backTop.classList.remove('visible');
            }
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    // Run initial scroll check in case page is already scrolled on load
    onScroll();

    backTop?.addEventListener('click', () => {
        // Target product header or product listing elements first if they exist
        const target = document.querySelector('.product-grid, .product-list, .product-detail-wrapper, .cart-table, .checkout-wrapper, .section');
        if (target) {
            const headerHeight = 80; // approximate main header height
            const targetPosition = target.getBoundingClientRect().top + window.scrollY - headerHeight;
            window.scrollTo({
                top: targetPosition >= 0 ? targetPosition : 0,
                behavior: 'smooth'
            });
        } else {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    // ── Mobile hamburger menu ─────────────────
    const toggle   = document.getElementById('mobileMenuToggle');
    const overlay  = document.getElementById('mobileNavOverlay');
    const closeBtn = document.getElementById('mobileNavClose');

    function openDrawer() {
        overlay.classList.add('active');
        toggle && toggle.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        overlay.classList.remove('active');
        toggle && toggle.classList.remove('open');
        document.body.style.overflow = '';
    }

    toggle?.addEventListener('click', openDrawer);
    closeBtn?.addEventListener('click', closeDrawer);
    overlay?.addEventListener('click', e => { if (e.target === overlay) closeDrawer(); });

    // ── Mobile Search Overlay ─────────────────
    const searchTrigger = document.getElementById('mobileSearchTrigger');
    const searchOverlay = document.getElementById('mobileSearchOverlay');
    const searchClose   = document.getElementById('mobileSearchClose');
    const searchInput   = document.getElementById('mobileSearchInput');

    searchTrigger?.addEventListener('click', () => {
        searchOverlay.classList.add('active');
        setTimeout(() => searchInput?.focus(), 100);
    });

    searchClose?.addEventListener('click', () => searchOverlay.classList.remove('active'));

    searchOverlay?.addEventListener('click', e => {
        if (e.target === searchOverlay) searchOverlay.classList.remove('active');
    });

    // Close search on Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            searchOverlay.classList.remove('active');
            closeDrawer();
        }
    });

    // ── Scroll Reveal ─────────────────────────
    const revealEls = document.querySelectorAll('.reveal');
    if (revealEls.length && 'IntersectionObserver' in window) {
        const io = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
        revealEls.forEach(el => io.observe(el));
    } else {
        revealEls.forEach(el => el.classList.add('visible'));
    }

    // ── Toast System ──────────────────────────
    window.showToast = function(message, type = 'info', duration = 3500) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const icons = {
            success: 'fa-circle-check',
            error:   'fa-circle-xmark',
            warning: 'fa-triangle-exclamation',
            info:    'fa-circle-info'
        };

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i><span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 350);
        }, duration);
    };

    // ── Update cart count from API ─────────────
    window.updateCartBadge = function(count) {
        const desktopBadge = document.getElementById('headerCartCount');
        const mobileBadge  = document.getElementById('mobileCartBadge');
        const cartLinks    = document.querySelectorAll('.cart-link');

        [desktopBadge, mobileBadge].forEach(el => {
            if (el) {
                el.textContent = count;
                el.style.display = count > 0 ? 'flex' : 'none';
            }
        });
    };
})();
</script>

<main class="main-content">