<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../page/login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$return_to = $_GET['return_to'] ?? 'products_manage.php';
$allowed_return_paths = ['products_manage.php', 'products.php'];
if (!in_array($return_to, $allowed_return_paths, true)) {
    $return_to = 'products_manage.php';
}

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}
$search = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');

if ($id <= 0) {
    header('Location: products_manage.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products_manage.php?msg=not_found');
    exit;
}

// ============ HÀM CRAWL ẢNH ============
function fetchHtml($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);
    $html = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    return $html ?: false;
}

function getProductImage($html, $baseUrl) {
    if (preg_match('/<img[^>]*class="[^"]*product[^"]*"[^>]*src="([^"]+)"/', $html, $m) ||
        preg_match('/<img[^>]*src="([^"]+)"[^>]*class="[^"]*product[^"]*"/', $html, $m)) {
        $imgUrl = $m[1];
    }
    elseif (preg_match('/<img[^>]*class="[^"]*product[^"]*"[^>]*data-src="([^"]+)"/', $html, $m)) {
        $imgUrl = $m[1];
    }
    elseif (preg_match('/<img[^>]*src="([^"]+(?:product|item|goods)[^"]*)"/', $html, $m)) {
        $imgUrl = $m[1];
    }
    elseif (preg_match_all('/<img[^>]*src="([^"]+\.(?:jpg|jpeg|png|webp))"[^>]*alt="([^"]*)"?/i', $html, $matches)) {
        foreach ($matches[1] as $url) {
            if (strpos($url, 'media') !== false && strpos($url, 'category') === false) {
                $imgUrl = $url;
                break;
            }
        }
        if (!isset($imgUrl)) $imgUrl = $matches[1][0] ?? false;
    }
    else {
        return false;
    }

    if (empty($imgUrl)) return false;

    if (strpos($imgUrl, 'http') !== 0) {
        $parsedUrl = parse_url($baseUrl);
        $domain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $imgUrl = rtrim($domain, '/') . '/' . ltrim($imgUrl, '/');
    }

    return $imgUrl;
}

function downloadImage($imgUrl, $filePath) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $imgUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => "Mozilla/5.0",
        CURLOPT_TIMEOUT => 10
    ]);
    
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return false;
    return file_put_contents($filePath, $imageData) !== false;
}

$crawl_message = '';
$crawl_status = '';

// ============ XỬ LÝ CRAWL ẢNH ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crawl') {
    $crawl_url = $_POST['crawl_url'] ?? '';
    
    if (empty($crawl_url)) {
        $crawl_message = 'Vui lòng nhập URL!';
        $crawl_status = 'error';
    } else {
        try {
            $html = fetchHtml($crawl_url);
            if (!$html) {
                throw new Exception("Không thể fetch trang web");
            }

            $imgUrl = getProductImage($html, $crawl_url);
            if (!$imgUrl) {
                throw new Exception("Không tìm thấy ảnh sản phẩm");
            }

            $uploadDir = "../uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileExt = pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            $fileExt = $fileExt ?: 'jpg';
            $fileName = time() . "_crawl." . $fileExt;
            $filePath = $uploadDir . $fileName;

            if (!downloadImage($imgUrl, $filePath)) {
                throw new Exception("Lỗi download ảnh");
            }

            $_SESSION['crawled_image'] = $fileName;
            $crawl_message = '✅ Crawl ảnh thành công! Ảnh sẽ được cập nhật khi lưu.';
            $crawl_status = 'success';

        } catch (Exception $e) {
            $crawl_message = '❌ Lỗi: ' . $e->getMessage();
            $crawl_status = 'error';
        }
    }
}

// ============ CẬP NHẬT SẢN PHẨM ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $brand_id = $_POST['brand_id'] ?: null;
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];
    $main_image = $product['main_image'];

    // Ưu tiên: Upload file > Crawl ảnh > Ảnh cũ
    if (!empty($_FILES['main_image']['name'])) {
        $main_image = time() . "_" . basename($_FILES['main_image']['name']);
        move_uploaded_file($_FILES['main_image']['tmp_name'], "../uploads/$main_image");
    } elseif (isset($_SESSION['crawled_image'])) {
        $main_image = $_SESSION['crawled_image'];
        unset($_SESSION['crawled_image']);
    }

    $stmt = $pdo->prepare("
        UPDATE products
        SET name=?, category_id=?, brand_id=?, price=?, stock=?, description=?, main_image=?, updated_at=NOW()
        WHERE product_id=?
    ");
    $stmt->execute([$name, $category_id, $brand_id, $price, $stock, $description, $main_image, $id]);

    $redirect_query = [];
    if ($return_to === 'products_manage.php') {
        $redirect_query[] = 'msg=updated';
        $redirect_query[] = 'page=' . $page;
        if ($search !== '') {
            $redirect_query[] = 'search=' . urlencode($search);
        }
        if ($category_filter !== '') {
            $redirect_query[] = 'category=' . urlencode($category_filter);
        }
    } else {
        $redirect_query[] = 'success=1';
    }

    $redirect_url = $return_to . (!empty($redirect_query) ? '?' . implode('&', $redirect_query) : '');
    header("Location: $redirect_url");
    exit;
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$brands = $pdo->query("SELECT * FROM brands")->fetchAll(PDO::FETCH_ASSOC);
$crawled_image = $_SESSION['crawled_image'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Sửa sản phẩm</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI'; background:#f0f6ff; padding:30px 20px; }
.container { max-width:600px; margin:0 auto; }
.form-card { background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1); margin-bottom:20px; }
h2 { color:#007bff; margin-bottom:25px; }
h3 { color:#333; font-size:16px; margin-top:20px; margin-bottom:12px; }
.form-group { margin-bottom:15px; }
label { display:block; margin-bottom:5px; color:#555; font-weight:500; }
input, select, textarea { width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-family:'Segoe UI'; }
textarea { resize:vertical; min-height:100px; }
input:focus, select:focus, textarea:focus { outline:none; border-color:#007bff; box-shadow:0 0 0 3px rgba(0,123,255,0.1); }
button { padding:12px 24px; border:none; border-radius:6px; cursor:pointer; font-weight:600; transition:0.3s; }
.btn-crawl { background:#28a745; color:white; width:100%; }
.btn-crawl:hover { background:#218838; }
.btn-update { background:#007bff; color:white; width:100%; }
.btn-update:hover { background:#0056b3; }
.message { padding:12px; border-radius:6px; margin-bottom:15px; }
.message.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.message.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
.image-preview { margin:15px 0; }
.image-preview img { max-width:150px; border-radius:6px; border:2px solid #ddd; }
.crawl-section { background:#f8f9fa; padding:15px; border-radius:6px; margin-bottom:20px; }
.crawl-section h3 { margin-top:0; }
input[type="file"] { padding:8px; }
.badge { display:inline-block; background:#28a745; color:white; padding:4px 8px; border-radius:4px; font-size:12px; margin-top:8px; }
</style>
</head>
<body>
<div class="container">
    <!-- CRAWL ẢNH SECTION -->
    <div class="form-card">
        <form method="POST" id="crawlForm">
            <input type="hidden" name="action" value="crawl">
            <div class="crawl-section">
                <h3>🔍 Crawl ảnh từ URL</h3>
                <div class="form-group">
                    <label>Nhập URL sản phẩm:</label>
                    <input type="url" name="crawl_url" placeholder="https://example.com/product" required>
                </div>
                <button type="submit" class="btn-crawl">🌐 Crawl ảnh</button>
            </div>
        </form>

        <?php if ($crawl_message): ?>
            <div class="message <?= $crawl_status ?>">
                <?= $crawl_message ?>
            </div>
        <?php endif; ?>

        <?php if ($crawled_image): ?>
            <div class="image-preview">
                <p><strong>✅ Ảnh đã crawl:</strong></p>
                <img src="../uploads/<?= htmlspecialchars($crawled_image) ?>" alt="Crawled image">
                <span class="badge">Sẵn sàng lưu</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- EDIT FORM SECTION -->
    <div class="form-card">
        <form method="POST" enctype="multipart/form-data" id="editForm">
            <input type="hidden" name="action" value="update">
            <h2>✏️ Sửa sản phẩm</h2>

            <div class="form-group">
                <label>Tên sản phẩm:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Danh mục:</label>
                <select name="category_id" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['category_id'] ?>" <?= $c['category_id']==$product['category_id']?'selected':'' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Thương hiệu:</label>
                <select name="brand_id">
                    <option value="">-- Chọn thương hiệu --</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['brand_id'] ?>" <?= $b['brand_id']==$product['brand_id']?'selected':'' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Giá:</label>
                <input type="number" name="price" value="<?= $product['price'] ?>" required>
            </div>

            <div class="form-group">
                <label>Số lượng:</label>
                <input type="number" name="stock" value="<?= $product['stock'] ?>" required>
            </div>

            <div class="form-group">
                <label>Mô tả:</label>
                <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <h3>📷 Ảnh sản phẩm</h3>
            
            <?php if ($product['main_image'] && !$crawled_image): ?>
                <div class="image-preview">
                    <p><strong>Ảnh hiện tại:</strong></p>
                    <img src="../uploads/<?= htmlspecialchars($product['main_image']) ?>" alt="Current image">
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Upload ảnh mới (ưu tiên hơn crawl):</label>
                <input type="file" name="main_image" accept="image/*">
            </div>

            <?php if ($crawled_image): ?>
                <div class="message success">
                    💡 Ảnh crawl sẽ được sử dụng nếu không upload file mới
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-update">💾 Cập nhật sản phẩm</button>
        </form>
    </div>
</div>
</body>
</html>