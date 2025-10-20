<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';

// ✅ Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// ✅ Kiểm tra quyền admin
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo "<h3 style='color:red; text-align:center; margin-top:50px'>🚫 Bạn không có quyền truy cập trang này!</h3>";
    exit;
}

// ✅ CSRF Token
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$message = '';
$message_type = '';

// ==========================================
// XỬ LÝ UPLOAD THƯƠNG HIỆU
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Kiểm tra CSRF
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $csrf) {
        $message = '❌ Token không hợp lệ';
        $message_type = 'error';
    } else {
        $action = $_POST['action'];

        // =====================================
        // THÊM MỚI THƯƠNG HIỆU
        // =====================================
        if ($action === 'add') {
            $name = trim($_POST['brand_name'] ?? '');
            $file = $_FILES['brand_image'] ?? null;

            if (empty($name)) {
                $message = '❌ Tên thương hiệu không được để trống';
                $message_type = 'error';
            } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $message = '❌ Vui lòng chọn hình ảnh';
                $message_type = 'error';
            } else {
                // Kiểm tra kích thước file
                if ($file['size'] > 5 * 1024 * 1024) {
                    $message = '❌ Kích thước file không được vượt quá 5MB';
                    $message_type = 'error';
                } else {
                    // Kiểm tra loại file
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    if (!in_array($mime, $allowed_mimes)) {
                        $message = '❌ Định dạng file không được hỗ trợ (JPG, PNG, WEBP, GIF)';
                        $message_type = 'error';
                    } else {
                        // Tạo thư mục nếu chưa có
                        $upload_dir = __DIR__ . '/../uploads/brands/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        // Tạo tên file duy nhất
                        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $new_filename = uniqid('brand_', true) . '.' . $extension;
                        $upload_path = $upload_dir . $new_filename;

                        // Di chuyển file
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            try {
                                // Lưu vào DB
                                $stmt = $pdo->prepare("
                                    INSERT INTO brands (name, slug, created_at)
                                    VALUES (?, ?, NOW())
                                ");
                                $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $name)));
                                $stmt->execute([$name, 'brands/' . $new_filename]);

                                $message = "✅ Thêm thương hiệu '$name' thành công!";
                                $message_type = 'success';
                            } catch (PDOException $e) {
                                unlink($upload_path);
                                $message = '❌ Lỗi database: ' . $e->getMessage();
                                $message_type = 'error';
                            }
                        } else {
                            $message = '❌ Lỗi khi upload file';
                            $message_type = 'error';
                        }
                    }
                }
            }
        }

        // =====================================
        // XÓA THƯƠNG HIỆU
        // =====================================
        elseif ($action === 'delete') {
            $brand_id = intval($_POST['brand_id'] ?? 0);

            if ($brand_id <= 0) {
                $message = '❌ ID thương hiệu không hợp lệ';
                $message_type = 'error';
            } else {
                try {
                    // Lấy thông tin file cũ
                    $stmt = $pdo->prepare("SELECT slug, name FROM brands WHERE brand_id = ?");
                    $stmt->execute([$brand_id]);
                    $brand = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$brand) {
                        $message = '❌ Thương hiệu không tồn tại';
                        $message_type = 'error';
                    } else {
                        // Xóa file cũ
                        if ($brand['slug']) {
                            $old_file = __DIR__ . '/../uploads/' . $brand['slug'];
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }

                        // Xóa DB
                        $stmt = $pdo->prepare("DELETE FROM brands WHERE brand_id = ?");
                        $stmt->execute([$brand_id]);

                        $message = "✅ Xóa thương hiệu '{$brand['name']}' thành công!";
                        $message_type = 'success';
                    }
                } catch (PDOException $e) {
                    $message = '❌ Lỗi khi xóa: ' . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
    }
}

// ✅ Lấy danh sách thương hiệu
$stmt = $pdo->query("SELECT * FROM brands ORDER BY created_at DESC");
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Thương hiệu - BuildPC.vn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .header h1 {
            color: #667eea;
            font-size: 32px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-count {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
        }

        .message {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: none;
            animation: slideDown 0.3s ease-out;
            border-left: 5px solid;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .message.show { display: block; }

        .message.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left-color: #28a745;
        }

        .message.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left-color: #f44336;
        }

        @keyframes slideDown {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .upload-section {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .upload-section h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 12px;
            padding: 50px 30px;
            text-align: center;
            cursor: pointer;
            background: linear-gradient(135deg, #f5f7ff 0%, #f0f3ff 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .upload-area:hover {
            background: linear-gradient(135deg, #eef1ff 0%, #e8ecff 100%);
            border-color: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
        }

        .upload-area.dragover {
            background: linear-gradient(135deg, #dce7ff 0%, #d0dcff 100%);
            border-color: #764ba2;
            transform: translateY(-5px);
        }

        .upload-area input {
            display: none;
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .upload-area p {
            color: #667eea;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .upload-area small {
            color: #999;
            display: block;
            font-size: 13px;
        }

        .file-name {
            margin-top: 15px;
            padding: 12px 15px;
            background: white;
            border-radius: 8px;
            color: #667eea;
            font-weight: 600;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .file-name.show { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            flex: 1;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-reset {
            background: #e0e0e0;
            color: #333;
            min-width: 120px;
        }

        .btn-reset:hover {
            background: #d0d0d0;
        }

        .brands-section {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .brands-section h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }

        table tbody tr:hover {
            background: #f9f9f9;
        }

        table td {
            padding: 16px;
            font-size: 14px;
        }

        .brand-image {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #f0f0f0;
            transition: all 0.3s;
        }

        .brand-image:hover {
            transform: scale(1.05);
            border-color: #667eea;
        }

        .brand-name {
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }

        code {
            background: #f5f5f5;
            padding: 4px 8px;
            border-radius: 4px;
            color: #666;
            font-size: 12px;
        }

        .btn-delete {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #d32f2f, #b71c1c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
        }

        .no-brands {
            text-align: center;
            padding: 60px 20px;
        }

        .no-brands p {
            color: #999;
            font-size: 16px;
        }

        .no-brands a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .no-brands a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-layer-group"></i> Quản lý Thương hiệu</h1>
        <div class="brand-count"><i class="fas fa-database"></i> <?= count($brands) ?> thương hiệu</div>
    </div>

    <?php if ($message): ?>
        <div class="message show <?= $message_type ?>"><?= $message ?></div>
    <?php endif; ?>

    <div class="upload-section">
        <h2><i class="fas fa-plus-circle"></i> Thêm Thương hiệu Mới</h2>
        <form method="POST" enctype="multipart/form-data" id="brandForm">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label for="brand_name"><i class="fas fa-tag"></i> Tên Thương hiệu:</label>
                <input type="text" id="brand_name" name="brand_name" placeholder="VD: Intel, AMD, ASUS, MSI..." required>
            </div>

            <div class="form-group">
                <label for="brand_image"><i class="fas fa-image"></i> Hình Ảnh Thương hiệu:</label>
                <div class="upload-area" id="uploadArea">
                    <input type="file" id="brand_image" name="brand_image" accept="image/*" required>
                    <div class="upload-icon">📤</div>
                    <p>Kéo thả hình ảnh hoặc click để chọn</p>
                    <small>Định dạng: JPG, PNG, WEBP, GIF • Tối đa: 5MB</small>
                </div>
                <div class="file-name" id="fileName"></div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-check"></i> Thêm Thương hiệu
                </button>
                <button type="reset" class="btn btn-reset">
                    <i class="fas fa-redo"></i> Xóa
                </button>
            </div>
        </form>
    </div>

    <div class="brands-section">
        <h2><i class="fas fa-boxes"></i> Danh Sách Thương hiệu</h2>
        <?php if (count($brands) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                    <tr>
                        <th style="width: 10%">ID</th>
                        <th style="width: 15%">Hình ảnh</th>
                        <th style="width: 30%">Tên Thương hiệu</th>
                        <th style="width: 25%">Slug</th>
                        <th style="width: 15%">Ngày Tạo</th>
                        <th style="width: 5%">Hành động</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($brands as $b): ?>
                        <tr>
                            <td>#<?= $b['brand_id'] ?></td>
                            <td>
                                <?php if ($b['slug'] && file_exists(__DIR__ . '/../uploads/' . $b['slug'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($b['slug']) ?>?v=<?= time() ?>" alt="<?= htmlspecialchars($b['name']) ?>" class="brand-image">
                                <?php else: ?>
                                    <div style="width: 70px; height: 70px; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ccc;">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="brand-name"><?= htmlspecialchars($b['name']) ?></span></td>
                            <td><code><?= htmlspecialchars(basename($b['slug'])) ?></code></td>
                            <td><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa thương hiệu này?');">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="brand_id" value="<?= $b['brand_id'] ?>">
                                    <button type="submit" class="btn-delete">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-brands">
                <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px; display: block;"></i>
                <p>📭 Chưa có thương hiệu nào. <a onclick="document.querySelector('input[name=brand_name]').focus(); window.scrollTo({top: 0, behavior: 'smooth'});">Thêm ngay</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('brand_image');
    const fileName = document.getElementById('fileName');
    const brandForm = document.getElementById('brandForm');

    // Xử lý upload file
    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            updateFileName();
        }
    });

    fileInput.addEventListener('change', updateFileName);

    function updateFileName() {
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            fileName.textContent = `✓ ${file.name} (${sizeMB}MB)`;
            fileName.classList.add('show');
        } else {
            fileName.classList.remove('show');
        }
    }

    // Xác nhận form
    brandForm.addEventListener('submit', function(e) {
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Vui lòng chọn hình ảnh!');
            fileInput.click();
        }
    });
</script>
</body>
</html>