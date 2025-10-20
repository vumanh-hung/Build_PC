<?php
/**
 * HEADER - Phần header của trang index.php
 * Thêm vào đầu file index.php của bạn
 */

session_start();
require_once 'db.php';
require_once 'functions.php';

// Lấy số lượng giỏ hàng
$cart_count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pid => $it) {
        $cart_count += is_array($it) && isset($it['quantity']) ? (int)$it['quantity'] : (int)$it;
    }
}

// Kiểm tra xem user có phải admin không
$is_admin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildPC.vn - Xây dựng PC mơ ước</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Header Styles */
        header {
            background: linear-gradient(90deg, #007bff 0%, #00aaff 50%, #007bff 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 40px;
            box-shadow: 0 8px 24px rgba(0, 107, 255, 0.15);
            position: sticky;
            top: 0;
            z-index: 999;
            gap: 20px;
            flex-wrap: wrap;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .logo a {
            text-decoration: none;
        }

        .logo span {
            color: white;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: 0.5px;
            white-space: nowrap;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav a:hover,
        .nav a.active {
            color: #ffeb3b;
        }

        .header-center {
            display: flex;
            align-items: center;
            flex: 1;
            max-width: 400px;
        }

        .search-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
            overflow: hidden;
            width: 100%;
            height: 38px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .search-container:focus-within {
            box-shadow: 0 8px 25px rgba(0, 107, 255, 0.25);
            transform: translateY(-2px);
        }

        .search-container input {
            flex: 1;
            border: none;
            outline: none;
            padding: 0 16px;
            font-size: 13px;
            color: #333;
            height: 38px;
            background: transparent;
        }

        .search-container button {
            background: none;
            border: none;
            width: 38px;
            height: 38px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #007bff;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .search-container button:hover {
            color: #ff9800;
            transform: scale(1.15);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-shrink: 0;
        }

        .btn-header {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
            border: none;
            cursor: pointer;
        }

        .cart-link {
            position: relative;
            background: rgba(255, 255, 255, 0.95);
            color: #007bff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .cart-link:hover {
            background: white;
            box-shadow: 0 6px 20px rgba(0, 107, 255, 0.3);
            transform: translateY(-3px);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #ffeb3b, #ff9800);
            color: #111;
            font-size: 10px;
            font-weight: 900;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
        }

        .admin-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.2);
        }

        .admin-btn:hover {
            background: linear-gradient(135deg, #ff5252, #ee5a6f);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.3);
            transform: translateY(-3px);
        }

        .login-btn {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border: 2px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .login-btn:hover {
            background: #ffffff;
            color: #007bff;
            border-color: #ffffff;
            transform: translateY(-3px);
        }

        .logout-btn {
            background: linear-gradient(135deg, #ff5252, #ff1744);
            color: white;
        }

        .logout-btn:hover {
            background: linear-gradient(135deg, #ff1744, #d50000);
            transform: translateY(-3px);
        }

        .welcome {
            color: #fff;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 1024px) {
            header {
                padding: 10px 24px;
                gap: 16px;
            }

            .nav {
                gap: 20px;
            }

            .header-center {
                max-width: 300px;
            }
        }

        @media (max-width: 768px) {
            header {
                padding: 10px 16px;
                flex-wrap: wrap;
            }

            .header-left {
                gap: 20px;
                width: 100%;
            }

            .logo span {
                font-size: 16px;
            }

            .nav {
                gap: 16px;
                font-size: 12px;
            }

            .header-center {
                width: 100%;
                max-width: 100%;
                order: 3;
                margin-top: 10px;
            }

            .header-right {
                width: 100%;
                justify-content: flex-start;
                order: 4;
                margin-top: 10px;
            }

            .btn-header {
                font-size: 11px;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header>
    <div class="header-left">
        <div class="logo">
            <a href="index.php">
                <span>🖥️ BuildPC.vn</span>
            </a>
        </div>

        <nav class="nav">
            <a href="index.php" class="active">Trang chủ</a>
            <a href="page/products.php">Sản phẩm</a>
            <a href="page/brands.php">Thương hiệu</a>
            <a href="page/builds.php">Xây dựng cấu hình</a>
            <a href="page/about.php">Giới thiệu</a>
            <a href="page/contact.php">Liên hệ</a>
        </nav>
    </div>

    <div class="header-center">
        <form class="search-container" method="GET" action="page/products.php">
            <input type="text" name="keyword" placeholder="Tìm sản phẩm...">
            <button type="submit">
                <i class="fa-solid fa-search"></i>
            </button>
        </form>
    </div>

    <div class="header-right">
        <!-- CART BUTTON -->
        <a href="cart.php" class="btn-header cart-link">
            <i class="fa-solid fa-cart-shopping"></i> Giỏ hàng
            <?php if ($cart_count > 0): ?>
                <span class="cart-count"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>

        <!-- ADMIN BUTTON (chỉ hiện nếu là admin) -->
        <?php if ($is_admin): ?>
            <a href="admin_panel.php" class="btn-header admin-btn" title="Vào trang quản lý admin">
                <i class="fa-solid fa-screwdriver-wrench"></i> Admin
            </a>
        <?php endif; ?>

        <!-- LOGIN/LOGOUT -->
        <?php if (isset($_SESSION['user'])): ?>
            <span class="welcome">👋 <?= htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['full_name']) ?></span>
            <a href="page/logout.php" class="btn-header logout-btn">
                <i class="fa-solid fa-sign-out-alt"></i> Đăng xuất
            </a>
        <?php else: ?>
            <a href="page/login.php" class="btn-header login-btn">
                <i class="fa-solid fa-user"></i> Đăng nhập
            </a>
        <?php endif; ?>
    </div>
</header>

<!-- Phần còn lại của trang index.php của bạn đặt dưới đây -->