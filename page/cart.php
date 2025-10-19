<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db.php';
include '../includes/header.php';

$cart = $_SESSION['cart'] ?? [];
$total = array_reduce($cart, fn($sum, $i) => $sum + $i['price'] * $i['quantity'], 0);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Giỏ hàng - BuildPC.vn</title>
<style>
/* giữ nguyên CSS cũ */
body { font-family: "Segoe UI", sans-serif; background: #f7faff; margin: 0; padding: 0; }
.container { max-width: 1100px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 30px; }
h1 { color: #007bff; text-align: center; margin-bottom: 30px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 14px; border-bottom: 1px solid #ddd; text-align: center; }
th { background: #007bff; color: white; }
td img { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; }
td input[type="number"] { width: 60px; text-align: center; border: 1px solid #ccc; border-radius: 4px; padding: 5px; }
.total { text-align: right; font-size: 18px; font-weight: bold; color: #007bff; margin-top: 20px; }
.btn { padding: 10px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
.btn-update { background: #007bff; color: white; }
.btn-clear { background: #ff4d4d; color: white; }
.btn-checkout { background: #28a745; color: white; float: right; }
.empty { text-align: center; color: #666; font-size: 18px; padding: 50px 0; }
</style>
</head>

<body>
<div class="container">
  <h1>🛒 Giỏ hàng của bạn</h1>

  <?php if (empty($cart)): ?>
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
          <?php foreach ($cart as $item): ?>
            <tr>
              <td><img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" alt=""></td>
              <td><?php echo htmlspecialchars($item['name']); ?></td>
              <td><?php echo number_format($item['price']); ?>₫</td>
              <td>
                <input type="number" name="qty[<?php echo $item['id']; ?>]" 
                       value="<?php echo $item['quantity']; ?>" min="1" class="qty-input">
              </td>
              <td><?php echo number_format($item['price'] * $item['quantity']); ?>₫</td>
              <td>
                <button class="btn-remove" data-id="<?php echo $item['id']; ?>">🗑️ Xóa</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="total">
        Tổng cộng: <span id="total"><?php echo number_format($total); ?></span>₫
      </div>

      <div style="margin-top:20px; display:flex; justify-content:space-between;">
        <button type="submit" class="btn btn-update">🔄 Cập nhật giỏ hàng</button>
        <div>
          <button id="btn-clear" class="btn btn-clear">🧹 Xóa tất cả</button>
          <a href="checkout.php" class="btn btn-checkout">💳 Thanh toán</a>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>

<script>
const API = "../api/cart_api.php";

// cập nhật giỏ hàng
document.getElementById('cart-form')?.addEventListener('submit', async e => {
  e.preventDefault();
  const form = new FormData(e.target);
  form.append('action', 'update');
  const res = await fetch(API, { method: 'POST', body: form });
  const data = await res.json();
  if (data.ok) location.reload();
});

// xóa 1 sản phẩm
document.querySelectorAll('.btn-remove').forEach(btn => {
  btn.addEventListener('click', async e => {
    e.preventDefault();
    if (!confirm('Xóa sản phẩm này?')) return;
    const res = await fetch(`${API}?action=remove&id=${btn.dataset.id}`);
    const data = await res.json();
    if (data.ok) location.reload();
  });
});

// xóa toàn bộ
document.getElementById('btn-clear')?.addEventListener('click', async e => {
  e.preventDefault();
  if (!confirm('Xóa toàn bộ giỏ hàng?')) return;
  const res = await fetch(`${API}?action=clear`);
  const data = await res.json();
  if (data.ok) location.reload();
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
