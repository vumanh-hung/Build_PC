<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db.php';
include '../includes/header.php';

// ====== Khởi tạo giỏ hàng ======
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ====== Xử lý thêm sản phẩm vào giỏ ======
if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $found = false;

        // Nếu sản phẩm đã có trong giỏ thì tăng số lượng
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                $item['quantity']++;
                $found = true;
                break;
            }
        }

        // Nếu chưa có thì thêm mới
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $product['product_id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => 1
            ];
        }
    }

    header("Location: cart.php");
    exit;
}

// ====== Xóa sản phẩm khỏi giỏ ======
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $_SESSION['cart'] = array_filter($_SESSION['cart'], fn($item) => $item['id'] != $id);
    header("Location: cart.php");
    exit;
}

// ====== Cập nhật số lượng ======
if (isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $id => $qty) {
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                $item['quantity'] = max(1, intval($qty)); // số lượng tối thiểu = 1
                break;
            }
        }
    }
    header("Location: cart.php");
    exit;
}

// ====== Xóa toàn bộ giỏ hàng ======
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    unset($_SESSION['cart']);
    header("Location: cart.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Giỏ hàng - BuildPC.vn</title>
<style>
body {
  font-family: "Segoe UI", sans-serif;
  background: #f7faff;
  margin: 0;
  padding: 0;
}
.container {
  max-width: 1100px;
  margin: 40px auto;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  padding: 30px;
}
h1 {
  color: #007bff;
  text-align: center;
  margin-bottom: 30px;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th, td {
  padding: 14px;
  border-bottom: 1px solid #ddd;
  text-align: center;
}
th {
  background: #007bff;
  color: white;
}
td img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 6px;
}
td input[type="number"] {
  width: 60px;
  text-align: center;
  border: 1px solid #ccc;
  border-radius: 4px;
  padding: 5px;
}
.total {
  text-align: right;
  font-size: 18px;
  font-weight: bold;
  color: #007bff;
  margin-top: 20px;
}
.btn {
  padding: 10px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}
.btn-update {
  background: #007bff;
  color: white;
}
.btn-update:hover {
  background: #005fd1;
}
.btn-clear {
  background: #ff4d4d;
  color: white;
}
.btn-clear:hover {
  background: #d32f2f;
}
.btn-checkout {
  background: #28a745;
  color: white;
  float: right;
}
.btn-checkout:hover {
  background: #1e7e34;
}
.empty {
  text-align: center;
  color: #666;
  font-size: 18px;
  padding: 50px 0;
}

</style>
</head>

<body>
<div class="container">
  <h1>🛒 Giỏ hàng của bạn</h1>

  <?php if (empty($_SESSION['cart'])): ?>
    <p class="empty">Giỏ hàng trống. <a href="../index.php">Mua sắm ngay!</a></p>
  <?php else: ?>
    <form method="post">
      <table>
        <thead>
          <tr>
            <th>Hình ảnh</th>
            <th>Tên sản phẩm</th>
            <th>Giá</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $total = 0;
          foreach ($_SESSION['cart'] as $item):
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
          ?>
          <tr>
            <td><img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" alt=""></td>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td><?php echo number_format($item['price']); ?>₫</td>
            <td>
              <input type="number" name="qty[<?php echo $item['id']; ?>]" 
                     value="<?php echo $item['quantity']; ?>" min="1">
            </td>
            <td><?php echo number_format($subtotal); ?>₫</td>
            <td>
              <a href="cart.php?action=remove&id=<?php echo $item['id']; ?>" 
                 style="color:red; text-decoration:none;">🗑️ Xóa</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="total">
        Tổng cộng: <?php echo number_format($total); ?>₫
      </div>

      <div style="margin-top:20px; display:flex; justify-content:space-between;">
        <button type="submit" name="update_cart" class="btn btn-update">🔄 Cập nhật giỏ hàng</button>
        <div>
          <a href="cart.php?action=clear" class="btn btn-clear">🧹 Xóa tất cả</a>
          <a href="checkout.php" class="btn btn-checkout">💳 Thanh toán</a>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
