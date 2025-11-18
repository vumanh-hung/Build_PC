<?php

/**
 * Product Compare Page
 * So sánh 2-4 sản phẩm theo ID
 */

// ✅ CRITICAL: Bắt đầu session TRƯỚC KHI include bất kỳ file nào
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ===== GET PRODUCT IDS FROM URL =====
$compare_ids = isset($_GET['ids']) ? array_filter(array_map('intval', explode(',', $_GET['ids']))) : [];
$compare_products = [];

if (empty($compare_ids)) {
    header('Location: products.php');
    exit;
}

// Limit to 4 products
if (count($compare_ids) > 4) {
    $compare_ids = array_slice($compare_ids, 0, 4);
}

// ===== DATABASE =====
$pdo = getPDO();

// ===== GET PRODUCT DETAILS =====
foreach ($compare_ids as $product_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, b.name AS brand_name, b.logo AS brand_logo
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.product_id = :product_id
    ");
    $stmt->execute([':product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // ===== GET PRODUCT IMAGES =====
        $imgStmt = $pdo->prepare("
            SELECT * FROM product_images 
            WHERE product_id = :product_id 
            ORDER BY is_primary DESC, image_id ASC
            LIMIT 1
        ");
        $imgStmt->execute([':product_id' => $product_id]);
        $product_image = $imgStmt->fetch(PDO::FETCH_ASSOC);

        if ($product_image) {
            $product['display_image'] = getProductImagePath($product_image['image_path']);
        } elseif ($product['main_image']) {
            $product['display_image'] = getProductImagePath($product['main_image']);
        } else {
            $product['display_image'] = 'uploads/img/no-image.png';
        }

        // ===== GET PRODUCT SPECIFICATIONS =====
        $specStmt = $pdo->prepare("
            SELECT spec_name, spec_value FROM product_specifications 
            WHERE product_id = :product_id
            ORDER BY spec_order ASC, spec_id ASC
        ");
        $specStmt->execute([':product_id' => $product_id]);
        $product['specifications'] = $specStmt->fetchAll(PDO::FETCH_ASSOC);

        // ===== GET REVIEW STATISTICS =====
        $reviewStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
            FROM reviews
            WHERE product_id = :product_id AND status = 'approved'
        ");
        $reviewStmt->execute([':product_id' => $product_id]);
        $product['review_stats'] = $reviewStmt->fetch(PDO::FETCH_ASSOC);

        // ===== CHECK FOR PROMOTIONS =====
        $promoStmt = $pdo->prepare("
            SELECT * FROM promotions 
            WHERE product_id = :product_id 
            AND start_date <= NOW() 
            AND end_date >= NOW()
            AND is_active = 1
            ORDER BY discount_percent DESC
            LIMIT 1
        ");
        $promoStmt->execute([':product_id' => $product_id]);
        $promotion = $promoStmt->fetch(PDO::FETCH_ASSOC);

        if ($promotion) {
            $product['promotion'] = $promotion;
            $product['sale_price'] = $product['price'] * (1 - $promotion['discount_percent'] / 100);
            $product['discount_percent'] = $promotion['discount_percent'];
        }

        $compare_products[] = $product;
    }
}

if (empty($compare_products)) {
    header('Location: products.php');
    exit;
}

// ===== GET CART COUNT =====
$user_id = getCurrentUserId();
$cart_count = $user_id ? getCartCount($user_id) : 0;

// ===== CSRF TOKEN =====
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>So sánh sản phẩm - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .compare-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }

        .compare-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .compare-header h1 {
            font-size: 28px;
            margin: 0;
            color: #333;
        }

        .btn-back {
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #5a6268;
        }

        .compare-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .product-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        .product-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .product-card img {
            width: 100%;
            height: 220px;
            object-fit: contain;
            border-radius: 6px;
            margin-bottom: 15px;
            background: #f5f5f5;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .product-card img:hover {
            transform: scale(1.05);
        }

        .brand-logo {
            height: 30px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo img {
            max-height: 30px;
            max-width: 100px;
            object-fit: contain;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            min-height: 48px;
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-category {
            font-size: 13px;
            color: #999;
            margin-bottom: 10px;
        }

        .product-price-section {
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }

        .product-price {
            color: #e30019;
            font-size: 24px;
            font-weight: bold;
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 14px;
            margin-right: 10px;
        }

        .discount-badge {
            background: #e30019;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-rating {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 14px;
        }

        .product-stock {
            font-size: 13px;
            margin-bottom: 15px;
            padding: 8px;
            border-radius: 4px;
            background: #f0f0f0;
        }

        .stock-available {
            color: #28a745;
            font-weight: 600;
        }

        .stock-limited {
            color: #ff9800;
            font-weight: 600;
        }

        .stock-unavailable {
            color: #dc3545;
            font-weight: 600;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            margin-bottom: 0;
        }

        .btn-view,
        .btn-cart {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-view {
            background: #007bff;
            color: white;
        }

        .btn-view:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .btn-cart {
            background: #28a745;
            color: white;
        }

        .btn-cart:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .specs-section {
            margin-top: 40px;
        }

        .specs-section h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
        }

        .specs-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .specs-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        .specs-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .spec-label {
            font-weight: 600;
            background: #fafafa;
            width: 200px;
            color: #333;
        }

        .specs-table tbody tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
        }

        .empty-state-icon {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state p {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }

        .btn-outline-primary {
            padding: 12px 30px;
            background: white;
            border: 2px solid #007bff;
            color: #007bff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-outline-primary:hover {
            background: #007bff;
            color: white;
        }

        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 15px 25px;
            background: #27ae60;
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .notification.success {
            background: #27ae60;
        }

        .notification.info {
            background: #3498db;
        }

        .notification.error {
            background: #e74c3c;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        @media (max-width: 768px) {
            .compare-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .compare-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }

            .specs-table {
                font-size: 13px;
            }

            .specs-table th,
            .specs-table td {
                padding: 10px;
            }

            .product-actions {
                flex-direction: column;
            }

            .btn-view,
            .btn-cart {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <?php include_once('../includes/header.php'); ?>

    <div class="compare-container">
        <div class="compare-header">
            <h1><i class="fa-solid fa-balance-scale"></i> So sánh sản phẩm</h1>
            <a href="javascript:history.back()" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Quay lại
            </a>
        </div>

        <?php if (empty($compare_products)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fa-solid fa-inbox"></i>
                </div>
                <p>Chưa có sản phẩm nào để so sánh</p>
                <a href="products.php" class="btn-outline-primary">
                    <i class="fa-solid fa-shopping-bag"></i> Xem danh sách sản phẩm
                </a>
            </div>
        <?php else: ?>
            <!-- THÔNG TIN CƠ BẢN -->
            <div class="compare-grid">
                <?php foreach ($compare_products as $product): ?>
                    <div class="product-card">
                        <?php if (!empty($product['brand_logo'])): ?>
                            <div class="brand-logo">
                                <img src="../<?php echo htmlspecialchars($product['brand_logo']); ?>"
                                    alt="<?php echo htmlspecialchars($product['brand_name']); ?>"
                                    onerror="this.style.display='none'">
                            </div>
                        <?php endif; ?>

                        <img src="../<?php echo htmlspecialchars($product['display_image']); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            onclick="window.location.href='product_detail.php?id=<?php echo $product['product_id']; ?>'"
                            onerror="this.src='../uploads/img/no-image.png'">

                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></div>

                        <div class="product-price-section">
                            <?php if (isset($product['sale_price'])): ?>
                                <div>
                                    <span class="original-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</span>
                                    <span class="discount-badge">-<?php echo intval($product['discount_percent']); ?>%</span>
                                </div>
                                <div class="product-price"><?php echo number_format($product['sale_price'], 0, ',', '.'); ?>₫</div>
                            <?php else: ?>
                                <div class="product-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</div>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($product['review_stats']) && $product['review_stats']['total_reviews'] > 0): ?>
                            <div class="product-rating">
                                <div class="rating-stars">
                                    <?php
                                    $rating = round($product['review_stats']['avg_rating']);
                                    for ($i = 0; $i < 5; $i++):
                                        echo $i < $rating ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                                    endfor;
                                    ?>
                                    <span style="color: #666; font-size: 12px; margin-left: 5px;">
                                        (<?php echo $product['review_stats']['total_reviews']; ?>)
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="product-stock">
                            <?php if ($product['stock'] > 50): ?>
                                <span class="stock-available"><i class="fa-solid fa-check-circle"></i> Còn hàng</span>
                            <?php elseif ($product['stock'] > 0): ?>
                                <span class="stock-limited"><i class="fa-solid fa-exclamation-circle"></i> Hạn hàng (<?php echo $product['stock']; ?>)</span>
                            <?php else: ?>
                                <span class="stock-unavailable"><i class="fa-solid fa-times-circle"></i> Hết hàng</span>
                            <?php endif; ?>
                        </div>

                        <div class="product-actions">
                            <button class="btn-view" onclick="window.location.href='product_detail.php?id=<?php echo $product['product_id']; ?>'">
                                <i class="fa-solid fa-eye"></i> Chi tiết
                            </button>
                            <button class="btn-cart" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                <i class="fa-solid fa-cart-plus"></i> Giỏ
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- BẢNG SO SÁNH THÔNG SỐ -->
            <div class="specs-section">
                <h2><i class="fa-solid fa-list"></i> Thông số kỹ thuật</h2>
                <table class="specs-table">
                    <thead>
                        <tr>
                            <th>Thông số</th>
                            <?php foreach ($compare_products as $product): ?>
                                <th><?php echo htmlspecialchars($product['name']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($compare_products[0]['specifications'])): ?>
                            <?php
                            $all_specs = [];
                            foreach ($compare_products as $product) {
                                foreach ($product['specifications'] as $spec) {
                                    $all_specs[$spec['spec_name']] = true;
                                }
                            }
                            ?>
                            <?php foreach (array_keys($all_specs) as $spec_name): ?>
                                <tr>
                                    <td class="spec-label"><?php echo htmlspecialchars($spec_name); ?></td>
                                    <?php
                                    foreach ($compare_products as $product) {
                                        $spec_value = 'N/A';
                                        foreach ($product['specifications'] as $spec) {
                                            if ($spec['spec_name'] === $spec_name) {
                                                $spec_value = htmlspecialchars($spec['spec_value']);
                                                break;
                                            }
                                        }
                                    ?>
                                        <td><?php echo $spec_value; ?></td>
                                    <?php
                                    }
                                    ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo count($compare_products) + 1; ?>" style="text-align: center; padding: 30px;">
                                    <i class="fa-solid fa-circle-info"></i> Không có thông số kỹ thuật
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php include_once('../includes/footer.php'); ?>

    <!-- ===== AUDIO SOUND ===== -->
    <audio id="tingSound" preload="auto">
        <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
    </audio>

    <!-- ===== ENABLE AUDIO AUTOPLAY ===== -->
    <script>
        // Allow audio to play after first user interaction
        document.addEventListener("click", () => {
            const sound = document.getElementById("tingSound");
            if (sound && sound.paused) {
                sound.play().then(() => {
                    sound.pause();
                    sound.currentTime = 0;
                }).catch(() => {});
            }
        }, {
            once: true
        });
    </script>

    <script>
        const csrf = '<?php echo $csrf; ?>';

        function addToCart(productId, quantity = 1) {
            // ✅ DEBUG: Log session status
            console.log('🔐 Session check:', {
                isLoggedIn: <?php echo isLoggedIn() ? 'true' : 'false'; ?>,
                userId: <?php echo $user_id > 0 ? $user_id : 'null'; ?>
            });

            // ✅ Kiểm tra đăng nhập
            <?php if (!isLoggedIn()): ?>
                console.error('❌ User not logged in - redirecting to login');
                showNotification('⚠️ Vui lòng đăng nhập để thêm vào giỏ hàng', 'error');
                setTimeout(() => {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                }, 1500);
                return;
            <?php endif; ?>

            console.log('🛒 Adding to cart:', {
                productId,
                quantity,
                csrfToken: csrf
            });

            showNotification('Đang thêm vào giỏ hàng...', 'info');

            fetch('./add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=${quantity}&csrf=${encodeURIComponent(csrf)}`
                })
                .then(response => {
                    console.log('📡 Response status:', response.status);
                    return response.text().then(text => {
                        console.log('📨 Response text:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('❌ JSON parse error:', e);
                            throw new Error('Invalid JSON response: ' + text);
                        }
                    });
                })
                .then(data => {
                    console.log('📨 Response data:', data);

                    if (data.ok) {
                        console.log('✅ Success!');

                        // 🔊 Phát âm thanh
                        playAddToCartSound();

                        showNotification('✅ Đã thêm vào giỏ hàng', 'success');

                        // Cập nhật số lượng giỏ hàng
                        const cartCountEl = document.querySelector('.cart-count');
                        if (cartCountEl) {
                            let currentCount = parseInt(cartCountEl.textContent) || 0;
                            cartCountEl.textContent = currentCount + parseInt(quantity);
                            cartCountEl.classList.add('cart-updated');
                            setTimeout(() => cartCountEl.classList.remove('cart-updated'), 500);
                        } else {
                            const cartLink = document.querySelector('.cart-link');
                            if (cartLink) {
                                const span = document.createElement('span');
                                span.className = 'cart-count';
                                span.textContent = quantity;
                                cartLink.appendChild(span);
                            }
                        }
                    } else {
                        console.log('❌ Error:', data.message);
                        showNotification('❌ ' + (data.message || 'Có lỗi xảy ra'), 'error');
                    }
                })
                .catch(error => {
                    console.error('❌ Fetch error:', error);
                    showNotification('❌ Lỗi: ' + error.message, 'error');
                });
        }

        // 🔊 FUNCTION PHÁT ÂM THANH
        function playAddToCartSound() {
            try {
                const sound = document.getElementById('tingSound');
                if (sound) {
                    sound.currentTime = 0;
                    sound.play().catch(() => {
                        console.log('⚠️ Không thể phát file âm thanh, dùng Web Audio API');
                        playWebAudioBeep();
                    });
                } else {
                    console.log('⚠️ Không tìm thấy element audio, dùng Web Audio API');
                    playWebAudioBeep();
                }
            } catch (e) {
                console.log('⚠️ Error:', e.message);
                playWebAudioBeep();
            }
        }

        // Web Audio API Beep
        function playWebAudioBeep() {
            try {
                const audioContext = new(window.AudioContext || window.webkitAudioContext)();
                const now = audioContext.currentTime;

                // Beep 1: 800Hz
                const osc1 = audioContext.createOscillator();
                const gain1 = audioContext.createGain();
                osc1.connect(gain1);
                gain1.connect(audioContext.destination);

                gain1.gain.setValueAtTime(0.3, now);
                osc1.frequency.setValueAtTime(800, now);
                gain1.gain.exponentialRampToValueAtTime(0.01, now + 0.08);

                osc1.start(now);
                osc1.stop(now + 0.08);

                // Beep 2: 1000Hz
                const osc2 = audioContext.createOscillator();
                const gain2 = audioContext.createGain();
                osc2.connect(gain2);
                gain2.connect(audioContext.destination);

                gain2.gain.setValueAtTime(0.2, now + 0.1);
                osc2.frequency.setValueAtTime(1000, now + 0.1);
                gain2.gain.exponentialRampToValueAtTime(0.01, now + 0.18);

                osc2.start(now + 0.1);
                osc2.stop(now + 0.18);

                console.log('🔊 Web Audio Beep phát thành công');
            } catch (e) {
                console.log('⚠️ Không thể phát Web Audio:', e.message);
            }
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>

</body>

</html>