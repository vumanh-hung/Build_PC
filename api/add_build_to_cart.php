<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user']['user_id'] ?? ($_SESSION['user_id'] ?? 0);
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập để thêm vào giỏ hàng']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Không nhận được dữ liệu JSON']);
    exit;
}

$build_id = (int)($data['build_id'] ?? 0);
if (!$build_id) {
    echo json_encode(['success' => false, 'error' => 'Thiếu ID cấu hình']);
    exit;
}

try {
    // Lấy danh sách sản phẩm trong cấu hình
    $stmt = $pdo->prepare("
        SELECT p.product_id, p.name, p.price
        FROM build_items bi
        JOIN products p ON bi.product_id = p.product_id
        WHERE bi.build_id = ?
    ");
    $stmt->execute([$build_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($products)) {
        echo json_encode(['success' => false, 'error' => 'Cấu hình này không có sản phẩm']);
        exit;
    }

    $pdo->beginTransaction();

    // Kiểm tra giỏ hàng
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_id = $stmt->fetchColumn();

    if (!$cart_id) {
        $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)")->execute([$user_id]);
        $cart_id = $pdo->lastInsertId();
    }

    // Thêm sản phẩm
    $insert = $pdo->prepare("
        INSERT INTO cart_items (cart_id, product_id, quantity)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE quantity = quantity + 1
    ");
    foreach ($products as $p) {
        $insert->execute([$cart_id, $p['product_id']]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '🛒 Đã thêm toàn bộ linh kiện trong cấu hình vào giỏ hàng của bạn!'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
