<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Nhận dữ liệu từ form hoặc AJAX
$product_id = $_POST['product_id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;

if (!$product_id || $quantity <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

// Kiểm tra sản phẩm có tồn tại
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['ok' => false, 'message' => 'Sản phẩm không tồn tại.']);
    exit;
}

// Kiểm tra giỏ hàng của user
$stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cart) {
    // Tạo giỏ hàng mới nếu chưa có
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, created_at) VALUES (?, NOW())");
    $stmt->execute([$user_id]);
    $cart_id = $pdo->lastInsertId();
} else {
    $cart_id = $cart['id'];
}

// Kiểm tra sản phẩm đã có trong giỏ hàng chưa
$stmt = $pdo->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?");
$stmt->execute([$cart_id, $product_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // Cập nhật số lượng
    $new_qty = $existing['quantity'] + $quantity;
    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $stmt->execute([$new_qty, $existing['id']]);
} else {
    // Thêm sản phẩm mới vào giỏ hàng
    $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$cart_id, $product_id, $quantity]);
}

echo json_encode(['ok' => true, 'message' => 'Đã thêm sản phẩm vào giỏ hàng!']);
exit;
?>
