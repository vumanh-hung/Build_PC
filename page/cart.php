<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db.php';
include '../includes/header.php';

// Kiểm tra đăng nhập
$user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['user_id'] ?? 0);
if (!$user_id) {
    echo "<p class='empty'>Vui lòng <a href='../page/login.php'>đăng nhập</a> để xem giỏ hàng.</p>";
    include '../includes/footer.php';
    exit;
}

// Lấy cart_id từ DB
$stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch(PDO::FETCH_ASSOC);

$items = [];
$total = 0;
if ($cart) {
    $stmt = $pdo->prepare("
        SELECT 
            p.product_id AS id, 
            p.name, 
            p.price, 
            p.main_image, 
            ci.quantity 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$cart['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Giỏ hàng - BuildPC.vn</title>
<style>
body { font-family: "Segoe UI", sans-serif; background: #f7faff; margin: 0; padding: 0; }
.container { max-width: 1100px; margin: 40px auto; background: white; border-radius: 16px; box-shadow: 0 6px 16px rgba(0,0,0,0.08); padding: 40px; }
h1 { color: #007bff; text-align: center; margin-bottom: 30px; font-size: 28px; }
table { width: 100%; border-collapse: collapse; border-radius: 8px; overflow: hidden; }
th, td { padding: 14px; border-bottom: 1px solid #eee; text-align: center; vertical-align: middle; }
th { background: #007bff; color: white; font-size: 16px; }
td img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
td input[type="number"] { width: 60px; text-align: center; border: 1px solid #ccc; border-radius: 4px; padding: 6px; }
.total { text-align: right; font-size: 20px; font-weight: bold; color: #007bff; margin-top: 25px; }
.btn { padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s; }
.btn-update { background: #007bff; color: white; }
.btn-update:hover { background: #0056d2; }
.btn-clear { background: #ff4d4d; color: white; }
.btn-clear:hover { background: #e33; }
.btn-checkout { background: #28a745; color: white; }
.btn-checkout:hover { background: #1e7e34; }
.remove-item { color: #ff4d4d; text-decoration: none; font-weight: bold; }
.remove-item:hover { color: #d00; text-decoration: underline; }
.empty { text-align: center; color: #666; font-size: 18px; padding: 50px 0; }
.action-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; }

td input[type="number"] {
    width: 70px;
    height: 38px;
    text-align: center;
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 5px;
    display: inline-block;
    vertical-align: middle;
    box-sizing: border-box;
    margin: 0 auto;
}

/* ✅ Đảm bảo các ô trong cùng hàng đều căn giữa hoàn toàn */
td {
    vertical-align: middle !important;
}

</style>
</head>
<body>
<div class="container">
  <h1>🛒 Giỏ hàng của bạn</h1>

  <?php if (empty($items)): ?>
    <p class="empty">Giỏ hàng trống. <a href="../index.php">Mua sắm ngay!</a></p>
  <?php else: ?>
    <form id="cart-form">
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
          <?php foreach ($items as $item): 
            $subtotal = $item['price'] * $item['quantity'];
          ?>
          <tr>
            <td><img src="../uploads/<?php echo htmlspecialchars($item['main_image']); ?>" alt=""></td>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td><?php echo number_format($item['price']); ?>₫</td>
            <td><input type="number" name="qty[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1"></td>
            <td style="color:#007bff;font-weight:bold;"><?php echo number_format($subtotal); ?>₫</td>
            <td><a href="#" class="remove-item" data-id="<?php echo $item['id']; ?>">🗑️ Xóa</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="total">Tổng cộng: <span id="total-amount"><?php echo number_format($total); ?></span>₫</div>
      <div class="action-bar">
        <button type="submit" class="btn btn-update">🔄 Cập nhật giỏ hàng</button>
        <div>
          <button id="clear-cart" type="button" class="btn btn-clear">🧹 Xóa tất cả</button>
          <a href="checkout.php" class="btn btn-checkout">💳 Thanh toán</a>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>

<script>
document.getElementById('cart-form')?.addEventListener('submit', async function(e){
  e.preventDefault();
  const form = new FormData();
  const qtyInputs = document.querySelectorAll('input[type="number"]');
  qtyInputs.forEach(inp => form.append(`items[${inp.name.match(/\d+/)[0]}]`, inp.value));
  form.append('action', 'update');

  const res = await fetch('../api/cart_api.php', {
    method: 'POST',
    body: form,
    credentials: 'include'
  });
  const data = await res.json();
  if (data.ok) location.reload();
});

document.querySelectorAll('.remove-item').forEach(btn => {
  btn.addEventListener('click', async e => {
    e.preventDefault();
    const id = btn.dataset.id;
    const res = await fetch(`../api/cart_api.php?action=remove&id=${id}`, { credentials: 'include' });
    const data = await res.json();
    if (data.ok) location.reload();
  });
});

document.getElementById('clear-cart')?.addEventListener('click', async e => {
  e.preventDefault();
  if (!confirm('Xóa toàn bộ giỏ hàng?')) return;
  const res = await fetch('../api/cart_api.php?action=clear', { credentials: 'include' });
  const data = await res.json();
  if (data.ok) location.reload();
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
