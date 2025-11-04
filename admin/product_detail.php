<?php
session_start();
require_once __DIR__ . '/../db.php';

$product_id = intval($_GET['id'] ?? 0);
if (!$product_id) {
    header('Location: ../index.php');
    exit;
}

// Lấy thông tin sản phẩm
$stmt = $pdo->prepare('
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.product_id = ?
');
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: ../index.php');
    exit;
}

// Lấy đánh giá đã duyệt
$stmt = $pdo->prepare('
    SELECT r.*, u.full_name, 
           (SELECT COUNT(*) FROM review_images WHERE review_id = r.review_id) as image_count
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.product_id = ? AND r.status = "approved"
    ORDER BY r.created_at DESC
');
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính điểm đánh giá trung bình
$stmt = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ? AND status = "approved"');
$stmt->execute([$product_id]);
$rating_info = $stmt->fetch(PDO::FETCH_ASSOC);
$avg_rating = round($rating_info['avg_rating'] ?? 0, 1);
$total_reviews = $rating_info['total_reviews'] ?? 0;

// Lấy hình ảnh đánh giá
function getReviewImages($pdo, $review_id) {
    $stmt = $pdo->prepare('SELECT image_path FROM review_images WHERE review_id = ? ORDER BY image_id DESC');
    $stmt->execute([$review_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function renderStars($rating, $size = 'md') {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '⭐' : '☆';
    }
    return $stars;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Chi tiết sản phẩm</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .product-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }

        .product-image {
            width: 100%;
            aspect-ratio: 1;
            background: #f0f0f0;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info h1 {
            font-size: 28px;
            margin-bottom: 15px;
            color: #2d3436;
        }

        .rating-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .rating-stars {
            font-size: 24px;
            color: #ffc107;
        }

        .rating-text {
            font-size: 14px;
            color: #6c757d;
        }

        .price {
            font-size: 32px;
            color: #ff6b6b;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .category-badge {
            display: inline-block;
            background: #e7f3ff;
            color: #007bff;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .description {
            font-size: 15px;
            line-height: 1.6;
            color: #555;
            margin-bottom: 20px;
        }

        .stock-info {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .stock-info.available {
            color: #28a745;
        }

        .stock-info.limited {
            color: #ffc107;
        }

        .stock-info.out {
            color: #dc3545;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-add-cart:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .review-form-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .review-form-section h2 {
            font-size: 22px;
            margin-bottom: 20px;
            color: #2d3436;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .rating-input {
            display: flex;
            gap: 10px;
            font-size: 32px;
        }

        .rating-input span {
            cursor: pointer;
            transition: all 0.2s;
            opacity: 0.5;
        }

        .rating-input span:hover,
        .rating-input span.selected {
            opacity: 1;
            transform: scale(1.1);
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            background: #f9f9f9;
        }

        .file-input-label:hover {
            border-color: #667eea;
            background: #f0f3ff;
        }

        .file-input-label input {
            display: none;
        }

        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .image-item {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            background: #f0f0f0;
        }

        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.8);
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .image-remove:hover {
            background: rgba(255, 0, 0, 1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .reviews-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .reviews-section h2 {
            font-size: 22px;
            margin-bottom: 20px;
            color: #2d3436;
        }

        .review-item {
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .review-author {
            font-weight: 600;
            color: #333;
        }

        .review-date {
            font-size: 13px;
            color: #6c757d;
        }

        .review-rating {
            font-size: 18px;
            color: #ffc107;
            margin-bottom: 10px;
        }

        .review-title {
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .review-content {
            font-size: 14px;
            line-height: 1.6;
            color: #555;
            margin-bottom: 12px;
        }

        .review-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .review-image {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            background: #f0f0f0;
            cursor: pointer;
        }

        .review-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .review-image:hover img {
            transform: scale(1.05);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .product-section,
            .review-form-section,
            .reviews-section {
                padding: 20px;
            }

            .rating-input {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="../index.php" class="back-link">← Quay lại trang chủ</a>

    <!-- Product Section -->
    <div class="product-section">
        <div class="product-grid">
            <div>
                <div class="product-image">
                    <?php if ($product['image']): ?>
                        <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <span style="font-size: 80px; color: #ddd;">📦</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="product-info">
                <h1><?= htmlspecialchars($product['name']) ?></h1>
                
                <div class="rating-info">
                    <div class="rating-stars"><?= renderStars(floor($avg_rating)) ?></div>
                    <div class="rating-text"><?= $avg_rating ?>/5 (<?= $total_reviews ?> đánh giá)</div>
                </div>

                <div class="category-badge"><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></div>

                <div class="price"><?= number_format($product['price']) ?>₫</div>

                <div class="description"><?= htmlspecialchars($product['description']) ?></div>

                <div class="stock-info <?= $product['stock'] > 10 ? 'available' : ($product['stock'] > 0 ? 'limited' : 'out') ?>">
                    <?php if ($product['stock'] > 0): ?>
                        ✓ Còn hàng (<?= $product['stock'] ?> sp)
                    <?php else: ?>
                        ✗ Hết hàng
                    <?php endif; ?>
                </div>

                <button class="btn-add-cart" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                    <?= $product['stock'] > 0 ? '🛒 Thêm vào giỏ hàng' : 'Hết hàng' ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Review Form Section -->
    <?php if (isset($_SESSION['user'])): ?>
    <div class="review-form-section">
        <h2>✍️ Viết đánh giá sản phẩm</h2>
        
        <div id="reviewMessage"></div>

        <form id="reviewForm" enctype="multipart/form-data">
            <div class="form-group">
                <label>Đánh giá (⭐)</label>
                <div class="rating-input">
                    <span class="star" data-rating="1">☆</span>
                    <span class="star" data-rating="2">☆</span>
                    <span class="star" data-rating="3">☆</span>
                    <span class="star" data-rating="4">☆</span>
                    <span class="star" data-rating="5">☆</span>
                </div>
                <input type="hidden" id="rating" name="rating" value="0">
            </div>

            <div class="form-group">
                <label for="title">Tiêu đề</label>
                <input type="text" id="title" name="title" placeholder="Tóm tắt về trải nghiệm của bạn..." required>
            </div>

            <div class="form-group">
                <label for="content">Nội dung đánh giá</label>
                <textarea id="content" name="content" placeholder="Chia sẻ chi tiết về sản phẩm..." required></textarea>
            </div>

            <div class="form-group">
                <label>Hình ảnh (tối đa 5 ảnh)</label>
                <div class="file-input-wrapper">
                    <label class="file-input-label">
                        <span>📷 Chọn hình ảnh</span>
                        <input type="file" id="images" name="images[]" accept="image/*" multiple>
                    </label>
                </div>
                <div class="image-preview" id="imagePreview"></div>
            </div>

            <button type="submit" class="btn-submit">Gửi đánh giá</button>
        </form>
    </div>
    <?php else: ?>
    <div class="review-form-section">
        <div class="alert alert-info">
            <span>ℹ️ Vui lòng <a href="../page/login.php" style="color: inherit; font-weight: 600;">đăng nhập</a> để viết đánh giá</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reviews Section -->
    <div class="reviews-section">
        <h2>💬 Đánh giá từ khách hàng (<?= count($reviews) ?>)</h2>
        
        <?php if (empty($reviews)): ?>
            <p style="text-align: center; color: #6c757d; padding: 40px 0;">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
        <?php else: ?>
            <?php foreach ($reviews as $review): 
                $images = getReviewImages($pdo, $review['review_id']);
            ?>
            <div class="review-item">
                <div class="review-header">
                    <div>
                        <div class="review-author"><?= htmlspecialchars($review['full_name']) ?></div>
                        <div class="review-date"><?= date('d/m/Y H:i', strtotime($review['created_at'])) ?></div>
                    </div>
                </div>
                <div class="review-rating"><?= renderStars($review['rating']) ?></div>
                <div class="review-title"><?= htmlspecialchars($review['title']) ?></div>
                <div class="review-content"><?= htmlspecialchars($review['content']) ?></div>
                
                <?php if (!empty($images)): ?>
                <div class="review-images">
                    <?php foreach ($images as $img): ?>
                    <div class="review-image">
                        <img src="../<?= htmlspecialchars($img['image_path']) ?>" alt="Review image">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
const productId = <?= $product_id ?>;
const stars = document.querySelectorAll('.star');
const ratingInput = document.getElementById('rating');
const imagesInput = document.getElementById('images');
const imagePreview = document.getElementById('imagePreview');
const reviewForm = document.getElementById('reviewForm');
const reviewMessage = document.getElementById('reviewMessage');

// Rating selection
stars.forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.dataset.rating;
        ratingInput.value = rating;
        
        stars.forEach((s, i) => {
            if (i < rating) {
                s.textContent = '⭐';
                s.classList.add('selected');
            } else {
                s.textContent = '☆';
                s.classList.remove('selected');
            }
        });
    });
});

// Image preview
imagesInput.addEventListener('change', function() {
    imagePreview.innerHTML = '';
    const files = Array.from(this.files);
    
    if (files.length > 5) {
        alert('Tối đa 5 ảnh');
        this.value = '';
        return;
    }
    
    files.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'image-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <button type="button" class="image-remove" onclick="removeImage(${index})">✕</button>
            `;
            imagePreview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});

function removeImage(index) {
    const files = Array.from(imagesInput.files);
    files.splice(index, 1);
    const dt = new DataTransfer();
    files.forEach(f => dt.items.add(f));
    imagesInput.files = dt.files;
    
    const event = new Event('change', { bubbles: true });
    imagesInput.dispatchEvent(event);
}

// Submit form
reviewForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!ratingInput.value) {
        showMessage('Vui lòng chọn đánh giá', 'error');
        return;
    }
    
    const formData = new FormData(this);
    formData.append('product_id', productId);
    
    try {
        const response = await fetch('../api/reviews.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Đánh giá của bạn đã được gửi thành công! ✓', 'success');
            reviewForm.reset();
            imagePreview.innerHTML = '';
            stars.forEach(s => {
                s.textContent = '☆';
                s.classList.remove('selected');
            });
            
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showMessage(data.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        showMessage('Lỗi: ' + error.message, 'error');
    }
});

function showMessage(msg, type) {
    reviewMessage.innerHTML = `<div class="alert alert-${type}"><span>${msg}</span></div>`;
    
    if (type === 'success') {
        setTimeout(() => {
            reviewMessage.innerHTML = '';
        }, 5000);
    }
}
</script>

</body>
</html>