<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__FILE__) . '/../db.php';

// ✅ Kiểm tra admin
if (!isset($_SESSION['user']['user_id']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../page/login.php');
    exit;
}

// ✅ Xử lý hành động
$action_message = '';
$action_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $review_id = intval($_POST['review_id'] ?? 0);
    
    if ($action === 'approve' && $review_id) {
        try {
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE review_id = ?");
            $stmt->execute([$review_id]);
            $action_message = 'Duyệt đánh giá thành công';
            $action_type = 'success';
        } catch (Exception $e) {
            $action_message = 'Có lỗi xảy ra';
            $action_type = 'error';
        }
    } elseif ($action === 'reject' && $review_id) {
        try {
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE review_id = ?");
            $stmt->execute([$review_id]);
            $action_message = 'Từ chối đánh giá thành công';
            $action_type = 'success';
        } catch (Exception $e) {
            $action_message = 'Có lỗi xảy ra';
            $action_type = 'error';
        }
    } elseif ($action === 'delete' && $review_id) {
        try {
            $pdo->beginTransaction();
            // Xóa ảnh từ file system
            $stmt = $pdo->prepare("SELECT image_path FROM review_images WHERE review_id = ?");
            $stmt->execute([$review_id]);
            $images = $stmt->fetchAll();
            foreach ($images as $image) {
                $filepath = dirname(__FILE__) . '/../' . $image['image_path'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
            // Xóa record
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
            $stmt->execute([$review_id]);
            $pdo->commit();
            $action_message = 'Xóa đánh giá thành công';
            $action_type = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $action_message = 'Có lỗi xảy ra';
            $action_type = 'error';
        }
    }
}

// ✅ Lấy dữ liệu lọc
$filter_status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$page = intval($_GET['page'] ?? 1);
$per_page = 20;

// ✅ Xây dựng query
$where = [];
$params = [];

if ($filter_status !== 'all') {
    $where[] = "r.status = ?";
    $params[] = $filter_status;
}

if (!empty($search)) {
    $where[] = "(r.title LIKE ? OR r.content LIKE ? OR u.full_name LIKE ? OR p.name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$order_by = match($sort) {
    'oldest' => 'r.created_at ASC',
    'rating_high' => 'r.rating DESC',
    'rating_low' => 'r.rating ASC',
    default => 'r.created_at DESC'
};

// Đếm tổng
$count_query = "SELECT COUNT(*) as total FROM reviews r $where_clause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Lấy dữ liệu
$offset = ($page - 1) * $per_page;
$query = "
    SELECT r.*, u.full_name, p.name as product_name,
           (SELECT COUNT(*) FROM review_images WHERE review_id = r.review_id) as image_count
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN products p ON r.product_id = p.product_id
    $where_clause
    ORDER BY $order_by
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($query);
$stmt->execute(array_merge($params, [$per_page, $offset]));
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stat_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
FROM reviews";
$stmt = $pdo->prepare($stat_query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

include dirname(__FILE__) . '/../includes/header.php';
?>

<div style="max-width: 1400px; margin: 30px auto; padding: 0 20px;">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="font-size: 28px; color: #333; display: flex; align-items: center; gap: 10px;">
            ⭐ Quản lý Đánh giá
        </h1>
        <a href="../index.php" style="color: #007bff; text-decoration: none; font-weight: 600;">
            ← Quay lại
        </a>
    </div>

    <!-- Alert -->
    <?php if (!empty($action_message)): ?>
        <div style="padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; background: <?= $action_type === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $action_type === 'success' ? '#155724' : '#721c24' ?>; border: 1px solid <?= $action_type === 'success' ? '#c3e6cb' : '#f5c6cb' ?>;">
            <?= $action_type === 'success' ? '✓' : '⚠️' ?> <?= htmlspecialchars($action_message) ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; border-top: 4px solid #007bff;">
            <div style="font-size: 32px; font-weight: 800; color: #007bff; margin-bottom: 8px;">
                <?= $stats['total'] ?? 0 ?>
            </div>
            <div style="color: #6c757d; font-size: 13px;">Tổng đánh giá</div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; border-top: 4px solid #ffc107;">
            <div style="font-size: 32px; font-weight: 800; color: #ffc107; margin-bottom: 8px;">
                <?= $stats['pending'] ?? 0 ?>
            </div>
            <div style="color: #6c757d; font-size: 13px;">Chờ duyệt</div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; border-top: 4px solid #28a745;">
            <div style="font-size: 32px; font-weight: 800; color: #28a745; margin-bottom: 8px;">
                <?= $stats['approved'] ?? 0 ?>
            </div>
            <div style="color: #6c757d; font-size: 13px;">Đã duyệt</div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; border-top: 4px solid #dc3545;">
            <div style="font-size: 32px; font-weight: 800; color: #dc3545; margin-bottom: 8px;">
                <?= $stats['rejected'] ?? 0 ?>
            </div>
            <div style="color: #6c757d; font-size: 13px;">Từ chối</div>
        </div>
    </div>

    <!-- Filters -->
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 6px; color: #333; font-weight: 600; font-size: 13px;">Tìm kiếm</label>
                <input type="text" name="search" placeholder="Tiêu đề, sản phẩm, tên..." value="<?= htmlspecialchars($search) ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 6px; color: #333; font-weight: 600; font-size: 13px;">Trạng thái</label>
                <select name="status" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Tất cả</option>
                    <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                    <option value="approved" <?= $filter_status === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                    <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Từ chối</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 6px; color: #333; font-weight: 600; font-size: 13px;">Sắp xếp</label>
                <select name="sort" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
                    <option value="rating_high" <?= $sort === 'rating_high' ? 'selected' : '' ?>>Đánh giá cao</option>
                    <option value="rating_low" <?= $sort === 'rating_low' ? 'selected' : '' ?>>Đánh giá thấp</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">🔍 Tìm</button>
                <a href="reviews.php" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; text-decoration: none; display: flex; align-items: center;">↻ Xóa</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <?php if (empty($reviews)): ?>
            <div style="text-align: center; padding: 40px; color: #6c757d;">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">📭</div>
                <p>Không có đánh giá nào</p>
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                    <tr>
                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #333; font-size: 13px; text-transform: uppercase;">Sản phẩm</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #333; font-size: 13px; text-transform: uppercase;">Khách hàng</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #333; font-size: 13px; text-transform: uppercase;">Đánh giá</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #333; font-size: 13px; text-transform: uppercase;">Tiêu đề</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #333; font-size: 13px; text-transform: uppercase;">Trạng thái</th>
                        <th style="padding: 15px; text-align: center; font-weight: 600; color: #333; font-size: 13px; text-transform: uppercase;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 15px; font-size: 13px;">
                                <?= htmlspecialchars(substr($review['product_name'], 0, 30)) ?>
                            </td>
                            <td style="padding: 15px; font-size: 13px;">
                                <?= htmlspecialchars($review['full_name']) ?>
                            </td>
                            <td style="padding: 15px; font-size: 13px;">
                                <div style="color: #ffc107;">
                                    <?= renderStars($review['rating'], 'sm') ?>
                                </div>
                            </td>
                            <td style="padding: 15px; font-size: 13px;">
                                <?= htmlspecialchars(substr($review['title'], 0, 40)) ?>
                            </td>
                            <td style="padding: 15px; font-size: 13px;">
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-weight: 600; background: <?= match($review['status']) {
                                    'pending' => '#fff3cd',
                                    'approved' => '#d4edda',
                                    'rejected' => '#f8d7da',
                                    default => '#e9ecef'
                                } ?>; color: <?= match($review['status']) {
                                    'pending' => '#856404',
                                    'approved' => '#155724',
                                    'rejected' => '#721c24',
                                    default => '#333'
                                } ?>;">
                                    <?= match($review['status']) {
                                        'pending' => 'Chờ duyệt',
                                        'approved' => 'Đã duyệt',
                                        'rejected' => 'Từ chối',
                                        default => $review['status']
                                    } ?>
                                </span>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <div style="display: flex; gap: 5px; justify-content: center;">
                                    <button type="button" onclick="viewReview(<?= $review['review_id'] ?>, '<?= htmlspecialchars($review['full_name']) ?>', '<?= htmlspecialchars($review['title']) ?>', '<?= htmlspecialchars($review['content']) ?>', <?= $review['rating'] ?>, '<?= htmlspecialchars($review['product_name']) ?>')" style="width: 32px; height: 32px; border-radius: 4px; border: none; cursor: pointer; background: #17a2b8; color: white; display: flex; align-items: center; justify-content: center; font-size: 14px;" title="Xem">
                                        👁️
                                    </button>
                                    <?php if ($review['status'] !== 'approved'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                            <button type="submit" style="width: 32px; height: 32px; border-radius: 4px; border: none; cursor: pointer; background: #28a745; color: white; display: flex; align-items: center; justify-content: center; font-size: 14px;" title="Duyệt">
                                                ✓
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($review['status'] !== 'rejected'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                            <button type="submit" style="width: 32px; height: 32px; border-radius: 4px; border: none; cursor: pointer; background: #ffc107; color: white; display: flex; align-items: center; justify-content: center; font-size: 14px;" title="Từ chối">
                                                ✕
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Xác nhận xóa?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                        <button type="submit" style="width: 32px; height: 32px; border-radius: 4px; border: none; cursor: pointer; background: #dc3545; color: white; display: flex; align-items: center; justify-content: center; font-size: 14px;" title="Xóa">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 8px; padding: 20px;">
                    <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                        <a href="?status=<?= $filter_status ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&page=<?= $i ?>" 
                           style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #007bff; background: <?= $page === $i ? '#007bff' : 'white' ?>; color: <?= $page === $i ? 'white' : '#007bff' ?>;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal View -->
<div id="reviewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; max-width: 600px; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 id="modalTitle" style="font-size: 20px; font-weight: 800; color: #333;"></h2>
            <button onclick="closeReviewModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">✕</button>
        </div>
        <div id="modalContent" style="line-height: 1.8;"></div>
    </div>
</div>

<script>
function viewReview(reviewId, fullName, title, content, rating, productName) {
    document.getElementById('modalTitle').textContent = fullName;
    document.getElementById('modalContent').innerHTML = `
        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
            <div style="font-weight: 600; color: #333; margin-bottom: 5px;">Sản phẩm</div>
            <div style="color: #555; font-size: 14px;">${productName}</div>
        </div>
        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
            <div style="font-weight: 600; color: #333; margin-bottom: 5px;">Đánh giá</div>
            <div style="color: #ffc107; font-size: 18px;">
                ${'⭐'.repeat(rating)}
            </div>
        </div>
        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
            <div style="font-weight: 600; color: #333; margin-bottom: 5px;">Tiêu đề</div>
            <div style="color: #555; font-size: 14px;">${title}</div>
        </div>
        <div style="margin-bottom: 20px;">
            <div style="font-weight: 600; color: #333; margin-bottom: 5px;">Nội dung</div>
            <div style="color: #555; font-size: 14px; line-height: 1.6;">${content}</div>
        </div>
    `;
    document.getElementById('reviewModal').style.display = 'flex';
}

function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

document.getElementById('reviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeReviewModal();
});
</script>

<?php include dirname(__FILE__) . '/../includes/footer.php'; ?>
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

// Kiểm tra user đã login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $user_id = $_SESSION['user']['user_id'];
    $product_id = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    // Validation
    if (!$product_id || !$rating || !$title || !$content) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Đánh giá không hợp lệ']);
        exit;
    }
    
    if (strlen($title) > 200) {
        echo json_encode(['success' => false, 'message' => 'Tiêu đề quá dài']);
        exit;
    }
    
    if (strlen($content) > 2000) {
        echo json_encode(['success' => false, 'message' => 'Nội dung quá dài']);
        exit;
    }
    
    // Kiểm tra sản phẩm có tồn tại
    $stmt = $pdo->prepare('SELECT product_id FROM products WHERE product_id = ?');
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }
    
    // Tạo thư mục upload nếu chưa có
    $upload_dir = __DIR__ . '/../uploads/reviews/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Xử lý hình ảnh
    $image_paths = [];
    if (isset($_FILES['images'])) {
        $files = $_FILES['images'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $max_files = 5;
        
        $file_count = count(array_filter($files['name']));
        if ($file_count > $max_files) {
            echo json_encode(['success' => false, 'message' => 'Tối đa ' . $max_files . ' ảnh']);
            exit;
        }
        
        for ($i = 0; $i < $file_count; $i++) {
            if (empty($files['name'][$i])) continue;
            
            $file = [
                'name' => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'size' => $files['size'][$i],
                'type' => $files['type'][$i],
                'error' => $files['error'][$i]
            ];
            
            // Kiểm tra lỗi upload
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Lỗi upload hình ảnh']);
                exit;
            }
            
            // Kiểm tra loại file
            if (!in_array($file['type'], $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'Chỉ hỗ trợ file ảnh (JPG, PNG, GIF, WebP)']);
                exit;
            }
            
            // Kiểm tra kích thước
            if ($file['size'] > $max_size) {
                echo json_encode(['success' => false, 'message' => 'Kích thước ảnh tối đa 5MB']);
                exit;
            }
            
            // Tạo tên file an toàn
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'review_' . time() . '_' . uniqid() . '.' . strtolower($ext);
            $filepath = $upload_dir . $filename;
            
            // Upload file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu hình ảnh']);
                exit;
            }
            
            $image_paths[] = 'uploads/reviews/' . $filename;
        }
    }
    
    // Lưu vào database (tự động duyệt - status = 'approved')
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare('
        INSERT INTO reviews (product_id, user_id, rating, title, content, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ');
    
    $stmt->execute([
        $product_id,
        $user_id,
        $rating,
        $title,
        $content,
        'approved'  // ✅ Tự động duyệt
    ]);
    
    $review_id = $pdo->lastInsertId();
    
    // Lưu hình ảnh
    if (!empty($image_paths)) {
        $stmt = $pdo->prepare('INSERT INTO review_images (review_id, image_path) VALUES (?, ?)');
        foreach ($image_paths as $image_path) {
            $stmt->execute([$review_id, $image_path]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đánh giá đã được gửi thành công',
        'review_id' => $review_id
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
