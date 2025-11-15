<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__FILE__) . '/../db.php';
require_once dirname(__FILE__) . '/../functions.php';

// Kiểm tra login
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: login.php');
    exit;
}

$product_id = intval($_GET['product_id'] ?? 0);
$user_id = $_SESSION['user']['user_id'];

if (!$product_id) {
    die('Sản phẩm không tồn tại');
}

// Lấy thông tin sản phẩm
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die('Sản phẩm không tồn tại');
}

// ✅ Kiểm tra user đã review rồi
$stmt = $pdo->prepare("SELECT review_id FROM reviews WHERE product_id = ? AND user_id = ?");
$stmt->execute([$product_id, $user_id]);
if ($stmt->fetch()) {
    die('Bạn đã viết đánh giá cho sản phẩm này rồi');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 5);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    
    // ✅ Validation
    if ($rating < 1 || $rating > 5) {
        $error = 'Đánh giá phải từ 1 đến 5 sao';
    } elseif (strlen($title) < 5 || strlen($title) > 200) {
        $error = 'Tiêu đề phải từ 5 đến 200 ký tự';
    } elseif (strlen($comment) < 10 || strlen($comment) > 2000) {
        $error = 'Nội dung phải từ 10 đến 2000 ký tự';
    } else {
        try {
            // ✅ Xử lý upload ảnh trước
            $image_name = null;
            if (!empty($_FILES['image']['name'])) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 5 * 1024 * 1024; // 5MB

                if (!in_array($_FILES['image']['type'], $allowed_types)) {
                    throw new Exception('Định dạng ảnh không hợp lệ');
                }

                if ($_FILES['image']['size'] > $max_size) {
                    throw new Exception('Kích thước ảnh không được vượt quá 5MB');
                }

                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('Lỗi upload ảnh');
                }

                $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = 'review_' . time() . '_' . uniqid() . '.' . strtolower($file_ext);
                $upload_dir = dirname(__FILE__) . '/../uploads/reviews/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $upload_path = $upload_dir . $image_name;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    throw new Exception('Không thể lưu ảnh');
                }
            }

            // ✅ Insert review vào database
            $stmt = $pdo->prepare('
                INSERT INTO reviews (product_id, user_id, rating, title, comment, image, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ');

            $stmt->execute([
                $product_id,
                $user_id,
                $rating,
                $title,
                $comment,
                $image_name,
                'pending'
            ]);

            $success = true;

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

include dirname(__FILE__) . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 30px auto; padding: 0 20px; flex: 1;">
    <a href="javascript:history.back()" style="color: #007bff; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; font-weight: 600;">
        ← Quay lại
    </a>

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <?php if ($success): ?>
            <div style="text-align: center; padding: 40px 20px;">
                <div style="font-size: 48px; color: #28a745; margin-bottom: 16px;">✓</div>
                <h2 style="color: #28a745; margin-bottom: 10px;">Cảm ơn bạn!</h2>
                <p style="color: #6c757d; margin-bottom: 20px;">
                    Đánh giá của bạn đã được gửi thành công và sẽ được kiểm duyệt trong 24 giờ.
                </p>
                <a href="product-reviews.php" style="color: #007bff; text-decoration: none; font-weight: 600;">
                    ← Quay lại trang đánh giá
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
                    <label style="display: block; margin-bottom: 12px; color: #333; font-weight: 600; font-size: 14px;">
                        Đánh giá <span style="color: #dc3545;">*</span>
                    </label>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="display: flex; gap: 8px; font-size: 32px;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" data-rating="<?= $i ?>" 
                                        onclick="setRating(<?= $i ?>); return false;"
                                        style="background: none; border: none; cursor: pointer; color: #ddd; transition: color 0.3s ease; padding: 0; line-height: 1;" 
                                        title="<?= $i ?> sao"
                                        onmouseover="hoverRating(<?= $i ?>)" 
                                        onmouseout="updateStars()">
                                    ⭐
                                </button>
                            <?php endfor; ?>
                        </div>
                        <div style="color: #667eea; font-weight: 700; font-size: 18px;">
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
                           onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
                           onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none'"
                           oninput="document.getElementById('titleCount').textContent = this.value.length">
                    <div style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                        <span id="titleCount">0</span>/200 ký tự
                    </div>
                </div>

                <!-- Comment -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px;">
                        Nội dung đánh giá <span style="color: #dc3545;">*</span>
                    </label>
                    <textarea name="comment" id="comment" 
                              placeholder="Hãy kể chi tiết về sản phẩm này..."
                              maxlength="2000" required
                              style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; min-height: 150px; resize: vertical; transition: border-color 0.3s ease;"
                              onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
                              onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none'"
                              oninput="document.getElementById('commentCount').textContent = this.value.length"></textarea>
                    <div style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                        <span id="commentCount">0</span>/2000 ký tự
                    </div>
                </div>

                <!-- Upload Image -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px;">
                        Thêm ảnh (tùy chọn)
                    </label>
                    <div onclick="document.getElementById('imageInput').click()" 
                         id="uploadArea"
                         style="border: 2px dashed #ddd; border-radius: 6px; padding: 30px 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: white;"
                         ondragover="this.style.borderColor='#667eea'; this.style.background='#f5f8ff'; return false"
                         ondragleave="this.style.borderColor='#ddd'; this.style.background='white'"
                         ondrop="handleDrop(event)">
                        <div style="font-size: 36px; margin-bottom: 10px;">🖼️</div>
                        <p style="color: #6c757d; margin: 0; font-weight: 600;">Kéo và thả ảnh hoặc click để chọn</p>
                        <small style="color: #6c757d;">JPG, PNG, GIF, WebP - tối đa 5MB</small>
                    </div>
                    <input type="file" id="imageInput" name="image" accept="image/*" style="display: none;" onchange="previewImage(this.files[0])">
                    <div id="imagePreview" style="display: none; margin-top: 15px;">
                        <div style="position: relative; width: 150px; height: 150px; border-radius: 6px; overflow: hidden;">
                            <img id="previewImg" src="" style="width: 100%; height: 100%; object-fit: cover;">
                            <button type="button" onclick="removeImage()" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.6); color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-weight: bold; font-size: 16px;">×</button>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <a href="product-reviews.php" 
                       style="flex: 1; padding: 12px 20px; background: #6c757d; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; display: flex; align-items: center; justify-content: center; transition: background 0.3s;"
                       onmouseover="this.style.background='#5a6268'"
                       onmouseout="this.style.background='#6c757d'">
                        Hủy
                    </a>
                    <button type="submit" style="flex: 1; padding: 12px 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s ease;" 
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(102, 126, 234, 0.3)'" 
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
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

function hoverRating(rating) {
    document.querySelectorAll('[data-rating]').forEach((btn, index) => {
        if (index + 1 <= rating) {
            btn.style.color = '#ffc107';
        } else {
            btn.style.color = '#ddd';
        }
    });
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
    e.stopPropagation();
    const uploadArea = document.getElementById('uploadArea');
    uploadArea.style.borderColor = '#ddd';
    uploadArea.style.background = 'white';
    
    if (e.dataTransfer.files.length > 0) {
        previewImage(e.dataTransfer.files[0]);
        document.getElementById('imageInput').files = e.dataTransfer.files;
    }
}

function previewImage(file) {
    if (!file) return;
    
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!validTypes.includes(file.type)) {
        alert('Vui lòng chọn ảnh JPG, PNG, GIF hoặc WebP');
        return;
    }
    
    if (file.size > maxSize) {
        alert('Kích thước ảnh không được vượt quá 5MB');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('imagePreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function removeImage() {
    document.getElementById('imageInput').value = '';
    document.getElementById('imagePreview').style.display = 'none';
}

// Initialize
updateStars();
</script>

<?php include dirname(__FILE__) . '/../includes/footer.php'; ?>