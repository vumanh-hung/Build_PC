<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Kiểm tra quyền admin
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo "<h3 style='color:red; text-align:center; margin-top:50px'>Bạn không có quyền truy cập trang này!</h3>";
    exit;
}

// CSRF Token
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$message = '';
$message_type = '';

// ===== XỬ LÝ THÊM/CẬP NHẬT/XÓA DANH MỤC =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Kiểm tra CSRF
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $csrf) {
        $message = 'Token không hợp lệ';
        $message_type = 'error';
    } else {
        $action = $_POST['action'];

        if ($action === 'add') {
            $name = trim($_POST['category_name'] ?? '');
            $description = trim($_POST['category_description'] ?? '');
            $file = $_FILES['category_image'] ?? null;

            if (empty($name)) {
                $message = 'Tên danh mục không được để trống';
                $message_type = 'error';
            } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $message = 'Vui lòng chọn hình ảnh';
                $message_type = 'error';
            } else {
                if ($file['size'] > 5 * 1024 * 1024) {
                    $message = 'Kích thước file không được vượt quá 5MB';
                    $message_type = 'error';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    
                    if (!in_array($mime, $allowed_mimes)) {
                        $message = 'Định dạng file không được hỗ trợ (JPG, PNG, WEBP, GIF)';
                        $message_type = 'error';
                    } else {
                        $upload_dir = __DIR__ . '/../uploads/categories/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $new_filename = uniqid('category_', true) . '.' . $extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            try {
                                $stmt = $pdo->prepare("
                                    INSERT INTO categories (name, description, slug, created_at) 
                                    VALUES (?, ?, ?, NOW())
                                ");
                                $stmt->execute([$name, $description, 'categories/' . $new_filename]);

                                $message = "Thêm danh mục '$name' thành công!";
                                $message_type = 'success';
                            } catch (PDOException $e) {
                                unlink($upload_path);
                                $message = 'Lỗi database: ' . $e->getMessage();
                                $message_type = 'error';
                            }
                        } else {
                            $message = 'Lỗi khi upload file';
                            $message_type = 'error';
                        }
                    }
                }
            }
        } 
        elseif ($action === 'edit') {
            $category_id = intval($_POST['category_id'] ?? 0);
            $name = trim($_POST['category_name'] ?? '');
            $description = trim($_POST['category_description'] ?? '');
            $file = $_FILES['category_image'] ?? null;

            if ($category_id <= 0) {
                $message = 'ID danh mục không hợp lệ';
                $message_type = 'error';
            } elseif (empty($name)) {
                $message = 'Tên danh mục không được để trống';
                $message_type = 'error';
            } else {
                try {
                    if ($file && $file['error'] === UPLOAD_ERR_OK) {
                        if ($file['size'] > 5 * 1024 * 1024) {
                            $message = 'Kích thước file không được vượt quá 5MB';
                            $message_type = 'error';
                        } else {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = finfo_file($finfo, $file['tmp_name']);
                            finfo_close($finfo);

                            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                            
                            if (!in_array($mime, $allowed_mimes)) {
                                $message = 'Định dạng file không được hỗ trợ (JPG, PNG, WEBP, GIF)';
                                $message_type = 'error';
                            } else {
                                $stmt = $pdo->prepare("SELECT slug FROM categories WHERE category_id = ?");
                                $stmt->execute([$category_id]);
                                $old_category = $stmt->fetch(PDO::FETCH_ASSOC);
                                $old_image = $old_category['slug'] ?? null;

                                $upload_dir = __DIR__ . '/../uploads/categories/';
                                if (!is_dir($upload_dir)) {
                                    mkdir($upload_dir, 0755, true);
                                }

                                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                $new_filename = uniqid('category_', true) . '.' . $extension;
                                $upload_path = $upload_dir . $new_filename;

                                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                                    if ($old_image) {
                                        $old_file = __DIR__ . '/../uploads/' . $old_image;
                                        if (file_exists($old_file)) {
                                            unlink($old_file);
                                        }
                                    }

                                    $stmt = $pdo->prepare("
                                        UPDATE categories 
                                        SET name = ?, description = ?, slug = ?, updated_at = NOW()
                                        WHERE category_id = ?
                                    ");
                                    $stmt->execute([$name, $description, 'categories/' . $new_filename, $category_id]);

                                    $message = "Cập nhật danh mục '$name' thành công!";
                                    $message_type = 'success';
                                } else {
                                    $message = 'Lỗi khi upload file';
                                    $message_type = 'error';
                                }
                            }
                        }
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE categories 
                            SET name = ?, description = ?, updated_at = NOW()
                            WHERE category_id = ?
                        ");
                        $stmt->execute([$name, $description, $category_id]);

                        $message = "Cập nhật danh mục '$name' thành công!";
                        $message_type = 'success';
                    }
                } catch (PDOException $e) {
                    $message = 'Lỗi khi cập nhật: ' . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
        elseif ($action === 'delete') {
            $category_id = intval($_POST['category_id'] ?? 0);

            if ($category_id <= 0) {
                $message = 'ID danh mục không hợp lệ';
                $message_type = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT slug, name FROM categories WHERE category_id = ?");
                    $stmt->execute([$category_id]);
                    $category = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$category) {
                        $message = 'Danh mục không tồn tại';
                        $message_type = 'error';
                    } else {
                        if ($category['slug']) {
                            $old_file = __DIR__ . '/../uploads/' . $category['slug'];
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }

                        $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
                        $stmt->execute([$category_id]);

                        $message = "Xóa danh mục '{$category['name']}' thành công!";
                        $message_type = 'success';
                    }
                } catch (PDOException $e) {
                    $message = 'Lỗi khi xóa: ' . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
    }
}

// Lấy danh sách danh mục
$stmt = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin danh mục để chỉnh sửa
$edit_category = null;
if (isset($_GET['edit'])) {
    $category_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Danh mục - BuildPC.vn</title>
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

        .category-count {
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

        /* ===== FORM SECTION ===== */
        .form-section {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .form-section h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 22px;
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

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
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

        .current-image {
            margin-top: 20px;
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
        }

        .current-image p {
            color: #666;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .image-preview {
            display: inline-block;
            position: relative;
        }

        .image-preview img {
            width: 250px;
            height: 250px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border: 3px solid white;
        }

        .file-name {
            margin-top: 15px;
            padding: 12px 15px;
            background: #e8f5e9;
            border-radius: 8px;
            color: #2e7d32;
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
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            flex: 1;
            min-width: 200px;
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

        .btn-cancel {
            background: #ff9800;
            color: white;
            text-decoration: none;
            min-width: 120px;
        }

        .btn-cancel:hover {
            background: #e68900;
        }

        /* ===== CATEGORIES TABLE ===== */
        .categories-section {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .categories-section h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 22px;
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

        .category-image-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .category-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #f0f0f0;
            transition: all 0.3s;
        }

        .category-image:hover {
            transform: scale(1.08);
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .no-image {
            width: 80px;
            height: 80px;
            background: #f0f0f0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
        }

        .category-name {
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }

        .category-description {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #666;
            font-size: 13px;
        }

        code {
            background: #f5f5f5;
            padding: 4px 8px;
            border-radius: 4px;
            color: #666;
            font-size: 12px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-edit {
            background: linear-gradient(135deg, #4caf50, #45a049);
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        .btn-delete {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #d32f2f, #b71c1c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
        }

        .no-categories {
            text-align: center;
            padding: 60px 20px;
        }

        .no-categories p {
            color: #999;
            font-size: 16px;
        }

        .no-categories a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .no-categories a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .header h1 { font-size: 24px; }
            .form-section { padding: 20px; }
            .upload-area { padding: 30px 15px; }
            .btn-group { flex-direction: column; }
            .btn { width: 100%; }
            table { font-size: 12px; }
            table th, table td { padding: 10px; }
            .category-image { width: 60px; height: 60px; }
            .image-preview img { width: 150px; height: 150px; }
            .actions { flex-direction: column; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-folder"></i> Quản lý Danh mục</h1>
            <div class="category-count"><i class="fas fa-database"></i> <?= count($categories) ?> danh mục</div>
        </div>

        <?php if ($message): ?>
        <div class="message show <?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="form-section">
            <h2>
                <i class="fas fa-<?php echo $edit_category ? 'edit' : 'plus-circle'; ?>"></i>
                <?php echo $edit_category ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới'; ?>
            </h2>

            <form method="POST" enctype="multipart/form-data" id="categoryForm">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="<?php echo $edit_category ? 'edit' : 'add'; ?>">
                <?php if ($edit_category): ?>
                <input type="hidden" name="category_id" value="<?= $edit_category['category_id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="category_name"><i class="fas fa-tag"></i> Tên Danh mục:</label>
                    <input type="text" id="category_name" name="category_name" 
                           placeholder="Ví dụ: PC Gaming, PC Văn Phòng, PC Thiết kế..." 
                           value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="category_description"><i class="fas fa-align-left"></i> Mô Tả:</label>
                    <textarea id="category_description" name="category_description" 
                              placeholder="Nhập mô tả chi tiết về danh mục này..."><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="category_image"><i class="fas fa-image"></i> Hình Ảnh Danh mục:</label>
                    <div class="upload-area" id="uploadArea">
                        <input type="file" id="category_image" name="category_image" 
                               accept="image/*" <?php echo !$edit_category ? 'required' : ''; ?>>
                        <div class="upload-icon">📤</div>
                        <p>Kéo thả hình ảnh hoặc click để chọn</p>
                        <small>Định dạng: JPG, PNG, WEBP, GIF • Tối đa: 5MB</small>
                    </div>
                    <div class="file-name" id="fileName"></div>
                    
                    <?php if ($edit_category && $edit_category['slug'] && file_exists(__DIR__ . '/../uploads/' . $edit_category['slug'])): ?>
                    <div class="current-image">
                        <p>Hình ảnh hiện tại:</p>
                        <div class="image-preview">
                            <img src="../uploads/<?= htmlspecialchars($edit_category['slug']) ?>?v=<?= time() ?>" 
                                 alt="<?= htmlspecialchars($edit_category['name']) ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-check"></i>
                        <?php echo $edit_category ? 'Cập nhật danh mục' : 'Thêm danh mục'; ?>
                    </button>
                    <button type="reset" class="btn btn-reset">
                        <i class="fas fa-redo"></i> Xóa
                    </button>
                    <?php if ($edit_category): ?>
                    <a href="categories_manage.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="categories-section">
            <h2><i class="fas fa-list"></i> Danh Sách Danh mục</h2>

            <?php if (count($categories) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 8%">ID</th>
                            <th style="width: 12%">Hình ảnh</th>
                            <th style="width: 25%">Tên Danh mục</th>
                            <th style="width: 30%">Mô Tả</th>
                            <th style="width: 15%">Ngày Tạo</th>
                            <th style="width: 10%">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): ?>
                        <tr>
                            <td>#<?= $c['category_id'] ?></td>
                            <td>
                                <div class="category-image-container">
                                    <?php if ($c['slug'] && file_exists(__DIR__ . '/../uploads/' . $c['slug'])): ?>
                                        <img src="../uploads/<?= htmlspecialchars($c['slug']) ?>?v=<?= time() ?>" 
                                             alt="<?= htmlspecialchars($c['name']) ?>" 
                                             class="category-image"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class="category-name"><?= htmlspecialchars($c['name']) ?></span></td>
                            <td>
                                <span class="category-description" title="<?= htmlspecialchars($c['description'] ?? '') ?>">
                                    <?= htmlspecialchars(substr($c['description'] ?? '', 0, 50)) ?>
                                    <?php if (strlen($c['description'] ?? '') > 50): ?>...<?php endif; ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="?edit=<?= $c['category_id'] ?>" class="btn-edit" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này? Hành động này không thể hoàn tác!');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?= $c['category_id'] ?>">
                                        <button type="submit" class="btn-delete" title="Xóa">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-categories">
                <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px; display: block;"></i>
                <p>Chưa có danh mục nào. <a href="categories_manage.php">Thêm ngay</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('category_image');
        const fileName = document.getElementById('fileName');
        const categoryForm = document.getElementById('categoryForm');

        // Click upload
        uploadArea.addEventListener('click', () => fileInput.click());

        // Drag over
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        // Drag leave
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        // Drop
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateFileName();
            }
        });

        // File change
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

        // Form validation
        categoryForm.addEventListener('submit', function(e) {
            const nameInput = document.getElementById('category_name').value.trim();
            if (!nameInput) {
                e.preventDefault();
                alert('Vui lòng nhập tên danh mục!');
                return;
            }
        });

        // Auto hide message
        const message = document.querySelector('.message');
        if (message && message.classList.contains('show')) {
            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => message.style.display = 'none', 300);
            }, 5000);
        }
    </script>
</body>
</html>