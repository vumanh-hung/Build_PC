<?php
session_start();
require_once '../db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Kiểm tra quyền admin
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo "<h3 style='color:red; text-align:center; margin-top:50px'>🚫 Bạn không có quyền truy cập trang này!</h3>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang quản trị - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
        }

        header h1 {
            color: #667eea;
            margin: 0;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .welcome {
            color: #333;
            font-weight: 500;
        }

        .welcome strong {
            color: #667eea;
        }

        .header-links {
            display: flex;
            gap: 10px;
        }

        .btn-header {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .back-btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }

        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        main {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            color: white;
            font-size: 28px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .card:hover {
            transform: translateY(-12px);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.2);
        }

        .card:hover::before {
            left: 0;
        }

        .card:hover h3,
        .card:hover p,
        .card:hover i {
            color: white;
        }

        .card i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 12px;
            transition: color 0.3s ease;
        }

        .card h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .card p {
            color: #666;
            font-size: 13px;
            line-height: 1.5;
            transition: color 0.3s ease;
        }

        .badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
            }

            .header-right {
                width: 100%;
                flex-direction: column;
                gap: 10px;
            }

            .header-links {
                width: 100%;
                flex-direction: column;
            }

            .btn-header {
                width: 100%;
                justify-content: center;
            }

            main {
                padding: 20px;
            }

            .section-title {
                font-size: 22px;
                margin-bottom: 20px;
            }

            .cards-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }

            .card {
                padding: 20px;
            }

            .card i {
                font-size: 36px;
            }

            .card h3 {
                font-size: 16px;
            }

            .card p {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>
            <i class="fas fa-crown"></i>
            Trang quản trị BuildPC.vn
        </h1>
        <div class="header-right">
            <span class="welcome">
                👋 Xin chào, <strong><?= htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['username']) ?></strong>
            </span>
            <div class="header-links">
                <a href="../index.php" class="btn-header back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Về trang chủ
                </a>
                <a href="logout.php" class="btn-header logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Đăng xuất
                </a>
            </div>
        </div>
    </header>

    <main>
        <h2 class="section-title">📂 Quản lý hệ thống</h2>

        <div class="cards-grid">
            <!-- QUẢN LÝ THƯƠNG HIỆU -->
            <a href="../admin/brands_manage.php" class="card">
                <i class="fas fa-building"></i>
                <h3>Quản lý Thương hiệu</h3>
                <p>Thêm, sửa, xóa thương hiệu công nghệ</p>
                <span class="badge">📦 Brands</span>
            </a>

            <!-- QUẢN LÝ SẢN PHẨM -->
            <a href="../admin/products_manage.php" class="card">
                <i class="fas fa-box"></i>
                <h3>Quản lý Sản phẩm</h3>
                <p>Quản lý danh sách sản phẩm trong kho</p>
                <span class="badge">🏪 Products</span>
            </a>

            <!-- QUẢN LÝ DANH MỤC -->
            <a href="../admin/categories_manage.php" class="card">
                <i class="fas fa-list"></i>
                <h3>Quản lý Danh mục</h3>
                <p>Tổ chức danh mục sản phẩm</p>
                <span class="badge">📑 Categories</span>
            </a>

            <!-- QUẢN LÝ NHÂN VIÊN -->
            <a href="../admin/users_manage.php" class="card">
                <i class="fas fa-users"></i>
                <h3>Quản lý Nhân viên</h3>
                <p>Quản lý tài khoản nhân viên hệ thống</p>
                <span class="badge">👥 Users</span>
            </a>

            <!-- QUẢN LÝ ĐƠN HÀNG -->
            <a href="../admin/orders_manage.php" class="card">
                <i class="fas fa-shopping-cart"></i>
                <h3>Quản lý Đơn hàng</h3>
                <p>Xem và xử lý đơn hàng khách hàng</p>
                <span class="badge">🛒 Orders</span>
            </a>

            <!-- QUẢN LÝ TÀI KHOẢN -->
            <a href="../admin/account_manage.php" class="card">
                <i class="fas fa-user-circle"></i>
                <h3>Tài khoản của tôi</h3>
                <p>Cập nhật thông tin cá nhân</p>
                <span class="badge">⚙️ Account</span>
            </a>
        </div>
    </main>
</body>
</html>