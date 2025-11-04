<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__FILE__) . '/../db.php';

if (!isset($_SESSION['user']['user_id'])) {
    header('Location: login.php');
    exit;
}

$product_id = intval($_GET['product_id'] ?? 0);
$user_id = $_SESSION['user']['user_id'];

if (!$product_id) {
    die('Sản phẩm không tồn tại');
}

// ✅ Lấy thông tin sản phẩm
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die('Sản phẩm không tồn tại');
}

// ✅ Kiểm tra user đã mua sản phẩm
if (!hasUserPurchasedProduct($pdo, $product_id, $user_id)) {
    die('Bạn cần mua sản phẩm này trước khi viết đánh giá');
}

// ✅ Kiểm tra user đã review
if (hasUserReviewedProduct($pdo, $product_id, $user_id)) {
    die('Bạn đã viết đánh giá cho sản phẩm này');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 5);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    // Validation
    if ($rating < 1 || $rating > 5) {
        $error = 'Rating không hợp lệ';
    } elseif (empty($title) || strlen($title) < 5) {
        $error = 'Tiêu đề phải có ít nhất 5 ký tự';
    } elseif (empty($content) || strlen($content) < 20) {
        $error = 'Nội dung phải có ít nhất 20 ký tự';
    } else {
        // Tạo đánh giá
        $result = createReview($pdo, $product_id, $user_id, $title, $content, $rating);
        
        if ($result['success']) {
            $review_id = $result['review_id'];
            
            // ✅ Xử lý upload ảnh
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = dirname(__FILE__) . '/../uploads/reviews/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if (!empty($tmp_name) && $_FILES['images']['error'][$key] === 0) {
                        $file_ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                        
                        if (in_array($file_ext, $allowed) && $_FILES['images']['size'][$key] <= 5000000) {
                            $filename = 'review_' . $review_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                            $filepath = $upload_dir . $filename;
                            
                            if (move_uploaded_file($tmp_name, $filepath)) {
                                addReviewImage($pdo, $review_id, 'uploads/reviews/' . $filename);
                            }
                        }
                    }
                }
            }
            
            $success = true;
        } else {
            $error = $result['message'] ?? 'Có lỗi xảy ra';
        }
    }
}

include dirname(__FILE__) . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 30px auto; padding: 0 20px; flex: 1;">
    <a href="javascript:history.back()" style="color: #007bff; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; font-weight: 600;">
        ← Quay lại danh sách đánh giá
    </a>

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <?php if ($success): ?>
            <div style="text-align: center; padding: 40px 20px;">
                <div style="font-size: 48px; color: #28a745; margin-bottom: 16px;">✓</div>
                <h2 style="color: #28a745; margin-bottom: 10px;">Cảm ơn bạn!</h2>
                <p style="color: #6c757d; margin-bottom: 20px;">
                    Đánh giá của bạn đã được gửi thành công và sẽ được kiểm duyệt trong 24 giờ.
                </p>
                <a href="product-reviews.php?product_id=<?= $product_id ?>" style="color: #007bff; text-decoration: none;">
                    ← Quay lại đánh giá
                </a>
            </div>
        <?php else: ?>
            <h1 style="font-size: 24px; color: #333; margin-bottom: 8px;">
                Viết đánh giá cho <?= htmlspecialchars(substr($product['name'], 0, 50)) ?>
            </h1>
            <p style="color: #6c757d; font-size: 14px; margin-bottom: 30px;">
                Chia sẻ trải nghiệm của bạn với sản phẩm này
            </p>

            <?php if (!empty($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 18px;">⚠️</span>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- Rating -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px;">
                        Đánh giá <span style="color: #dc3545;">*</span>
                    </label>
                    <div style="display: flex; gap: 15px; font-size: 32px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" data-rating="<?= $i ?>" 
                                    onclick="setRating(<?= $i ?>)" 
                                    style="background: none; border: none; cursor: pointer; color: #ddd; transition: color 0.3s ease; padding: 0;" 
                                    title="<?= $i ?> sao"
                                    onmouseover="this.style.color='#ffc107'" 
                                    onmouseout="updateStars()">
                                ⭐
                            </button>
                        <?php endfor; ?>
                        <div style="color: #007bff; font-weight: 700; font-size: 18px; margin-left: 10px; align-self: center;">
                            <span id="ratingValue">5</span>/5
                        </div>
                    </div>
                    <input type="hidden" id="ratingInput" name="rating" value="5">
                </div>

                <!-- Title -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px;">
                        Tiêu đề <span style="color: #dc3545;">*</span>
                    </label>
                    <input type="text" name="title" id="title" 
                           placeholder="Ví dụ: Sản phẩm rất tốt, giao hàng nhanh"
                           maxlength="200" required
                           style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; transition: border-color 0.3s ease;"
                           onfocus="this.style.borderColor='#007bff'; this.style.boxShadow='0 0 0 3px rgba(0, 123, 255, 0.1)'"
                           onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none'"
                           oninput="document.getElementById('titleCount').textContent = this.value.length">
                    <div style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                        <span id="titleCount">0</span>/200
                    </div>
                </div>

                <!-- Content -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px;">
                        Nội dung <span style="color: #dc3545;">*</span>
                    </label>
                    <textarea name="content" id="content" 
                              placeholder="Hãy kể chi tiết về sản phẩm này..."
                              maxlength="2000" required
                              style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; min-height: 150px; resize: vertical; transition: border-color 0.3s ease;"
                              onfocus="this.style.borderColor='#007bff'; this.style.boxShadow='0 0 0 3px rgba(0, 123, 255, 0.1)'"
                              onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none'"
                              oninput="document.getElementById('contentCount').textContent = this.value.length"></textarea>
                    <div style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                        <span id="contentCount">0</span>/2000
                    </div>
                </div>

                <!-- Upload Images -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px;">
                        Thêm ảnh (tùy chọn)
                    </label>
                    <div onclick="document.getElementById('imageInput').click()" 
                         id="uploadArea"
                         style="border: 2px dashed #ddd; border-radius: 6px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: white;"
                         ondragover="this.style.borderColor='#007bff'; this.style.background='#f0f8ff'"
                         ondragleave="this.style.borderColor='#ddd'; this.style.background='white'"
                         ondrop="handleDrop(event)">
                        <div style="font-size: 36px; color: #007bff; margin-bottom: 10px;">🖼️</div>
                        <p style="color: #6c757d; margin: 0;">Kéo và thả ảnh hoặc click để chọn</p>
                        <small style="color: #6c757d;">Tối đa 5 ảnh, mỗi ảnh dưới 5MB (JPG, PNG, WebP)</small>
                    </div>
                    <input type="file" id="imageInput" name="images[]" multiple accept="image/*" style="display: none;" onchange="previewImages(this.files)">
                    <div id="previewImages" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 15px;"></div>
                </div>

                <!-- Actions -->
                <div style="display: flex; gap: 10px;">
                    <a href="product-reviews.php?product_id=<?= $product_id ?>" 
                       style="flex: 1; padding: 12px 20px; background: #6c757d; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        Hủy
                    </a>
                    <button type="submit" style="flex: 1; padding: 12px 20px; background: #28a745; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; transition: background 0.3s ease;" 
                            onmouseover="this.style.background='#218838'" 
                            onmouseout="this.style.background='#28a745'">
                        ✓ Gửi đánh giá
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
let currentRating = 5;

function setRating(rating) {
    currentRating = rating;
    document.getElementById('ratingInput').value = rating;
    document.getElementById('ratingValue').textContent = rating;
    updateStars();
}

function updateStars() {
    document.querySelectorAll('[data-rating]').forEach((btn, index) => {
        if (index + 1 <= currentRating) {
            btn.style.color = '#ffc107';
        } else {
            btn.style.color = '#ddd';
        }
    });
}

function handleDrop(e) {
    e.preventDefault();
    e.target.style.borderColor = '#ddd';
    e.target.style.background = 'white';
    previewImages(e.dataTransfer.files);
}

function previewImages(files) {
    const container = document.getElementById('previewImages');
    const input = document.getElementById('imageInput');
    
    // Giới hạn 5 ảnh
    const fileArray = Array.from(files).slice(0, 5);
    
    // Cập nhật input
    const dataTransfer = new DataTransfer();
    fileArray.forEach(file => {
        dataTransfer.items.add(file);
    });
    input.files = dataTransfer.files;
    
    container.innerHTML = '';
    
    fileArray.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.style.cssText = 'position: relative; width: 100px; height: 100px; border-radius: 6px; overflow: hidden;';
            preview.innerHTML = `
                <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" onclick="removeImage(${index})" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.6); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-weight: bold; font-size: 14px;">×</button>
            `;
            container.appendChild(preview);
        };
        reader.readAsDataURL(file);
    });
}

function removeImage(index) {
    const input = document.getElementById('imageInput');
    const dataTransfer = new DataTransfer();
    
    Array.from(input.files).forEach((file, i) => {
        if (i !== index) {
            dataTransfer.items.add(file);
        }
    });
    
    input.files = dataTransfer.files;
    previewImages(input.files);
}

// Initialize
updateStars();
</script>

<?php include dirname(__FILE__) . '/../includes/footer.php'; ?>