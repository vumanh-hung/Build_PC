<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db.php';
require_once '../functions.php';
include '../includes/header.php';

// ✅ Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = getCurrentUserId();
$pdo = getPDO();

// ✅ Lấy giỏ hàng của user - DÙNG product_id
$stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch(PDO::FETCH_ASSOC);

$items = [];
$total = 0;
if ($cart) {
    $stmt = $pdo->prepare("
        SELECT 
            ci.id AS item_id,
            p.product_id,              -- ← product_id để xóa/update
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Giỏ hàng - BuildPC.vn</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  color: #333;
  min-height: 100vh;
  padding-bottom: 40px;
}

.container {
  max-width: 1200px;
  margin: 40px auto;
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  padding: 40px;
}

.page-header {
  text-align: center;
  margin-bottom: 40px;
  padding-bottom: 20px;
  border-bottom: 2px solid #f0f0f0;
}

.page-header h1 {
  color: #007bff;
  font-size: 32px;
  font-weight: 700;
  margin-bottom: 8px;
}

.page-header p {
  color: #666;
  font-size: 14px;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 80px 20px;
}

.empty-state i {
  font-size: 80px;
  color: #ddd;
  margin-bottom: 20px;
}

.empty-state h2 {
  color: #666;
  font-size: 24px;
  margin-bottom: 12px;
}

.empty-state p {
  color: #999;
  margin-bottom: 24px;
}

.empty-state .btn-primary {
  display: inline-block;
  padding: 12px 32px;
  background: linear-gradient(135deg, #007bff, #0056d2);
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s;
}

.empty-state .btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,123,255,0.4);
}

/* Cart Table */
.cart-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 30px;
}

.cart-table thead {
  background: linear-gradient(135deg, #007bff, #0056d2);
  color: white;
}

.cart-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.cart-table th:first-child { width: 100px; }
.cart-table th:nth-child(3), 
.cart-table th:nth-child(4), 
.cart-table th:nth-child(5) { text-align: center; }
.cart-table th:last-child { text-align: center; width: 80px; }

.cart-table tbody tr {
  border-bottom: 1px solid #f0f0f0;
  transition: background 0.2s;
}

.cart-table tbody tr:hover {
  background: #f8f9fa;
}

.cart-table td {
  padding: 20px 16px;
  vertical-align: middle;
}

/* Product Image */
.product-image {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transition: transform 0.3s;
}

.product-image:hover {
  transform: scale(1.05);
}

/* Product Info */
.product-name {
  font-weight: 600;
  color: #333;
  font-size: 15px;
  line-height: 1.4;
}

.product-name a {
  color: #333;
  text-decoration: none;
  transition: color 0.2s;
}

.product-name a:hover {
  color: #007bff;
}

/* Price */
.product-price {
  text-align: center;
  color: #007bff;
  font-weight: 600;
  font-size: 16px;
}

/* Quantity Input */
.quantity-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
}

.quantity-input {
  width: 70px;
  padding: 8px;
  text-align: center;
  border: 2px solid #e0e0e0;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 600;
  transition: all 0.2s;
}

.quantity-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

/* Subtotal */
.subtotal {
  text-align: center;
  color: #28a745;
  font-weight: 700;
  font-size: 16px;
}

/* Remove Button */
.btn-remove {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: #fff;
  color: #dc3545;
  border: 2px solid #dc3545;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.3s;
  text-decoration: none;
}

.btn-remove:hover {
  background: #dc3545;
  color: white;
  transform: rotate(90deg);
}

/* Cart Summary */
.cart-summary {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  padding: 24px;
  border-radius: 12px;
  margin-bottom: 24px;
}

.cart-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 24px;
  font-weight: 700;
  color: #007bff;
}

.cart-total .label {
  color: #666;
}

.cart-total .amount {
  color: #007bff;
}

/* Action Buttons */
.cart-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s;
  text-decoration: none;
}

.btn-update {
  background: #007bff;
  color: white;
}

.btn-update:hover {
  background: #0056d2;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,123,255,0.3);
}

.btn-clear {
  background: white;
  color: #dc3545;
  border: 2px solid #dc3545;
}

.btn-clear:hover {
  background: #dc3545;
  color: white;
  transform: translateY(-2px);
}

.btn-checkout {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  color: white;
  font-size: 16px;
  padding: 14px 32px;
}

.btn-checkout:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(40,167,69,0.4);
}

.btn-group {
  display: flex;
  gap: 12px;
}

/* Loading Overlay */
.loading-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  z-index: 9999;
  align-items: center;
  justify-content: center;
}

.loading-overlay.active {
  display: flex;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
  .container {
    padding: 20px;
    margin: 20px;
  }
  
  .page-header h1 {
    font-size: 24px;
  }
  
  .cart-table {
    display: block;
    overflow-x: auto;
  }
  
  .cart-actions {
    flex-direction: column;
  }
  
  .btn-group {
    width: 100%;
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
</head>
<body>

<div class="loading-overlay" id="loading">
  <div class="spinner"></div>
</div>

<div class="container">
  <div class="page-header">
    <h1><i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn</h1>
    <p>Quản lý các sản phẩm trong giỏ hàng của bạn</p>
  </div>

  <?php if (empty($items)): ?>
    <!-- Empty State -->
    <div class="empty-state">
      <i class="fas fa-shopping-cart"></i>
      <h2>Giỏ hàng trống</h2>
      <p>Bạn chưa có sản phẩm nào trong giỏ hàng</p>
      <a href="../index.php" class="btn-primary">
        <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
      </a>
    </div>
  <?php else: ?>
    <!-- Cart Table -->
    <form id="cart-form">
      <table class="cart-table">
        <thead>
          <tr>
            <th>Hình ảnh</th>
            <th>Sản phẩm</th>
            <th>Đơn giá</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
            <th>Xóa</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): 
            $subtotal = $item['price'] * $item['quantity'];
            $image_path = getProductImagePath($item['main_image']);
          ?>
          <tr data-product-id="<?= $item['product_id'] ?>" data-price="<?= $item['price'] ?>">
            <td>
              <img src="../<?= escape($image_path) ?>" 
                   alt="<?= escape($item['name']) ?>" 
                   class="product-image">
            </td>
            <td>
              <div class="product-name">
                <a href="product_detail.php?id=<?= $item['product_id'] ?>">
                  <?= escape($item['name']) ?>
                </a>
              </div>
            </td>
            <td class="product-price">
              <?= formatPriceVND($item['price']) ?>
            </td>
            <td>
              <div class="quantity-wrapper">
                <input type="number" 
                       class="quantity-input" 
                       value="<?= $item['quantity'] ?>" 
                       min="1" 
                       max="99"
                       data-product-id="<?= $item['product_id'] ?>"
                       onchange="updateSubtotal(this)">
              </div>
            </td>
            <td class="subtotal">
              <?= formatPriceVND($subtotal) ?>
            </td>
            <td style="text-align: center;">
              <a href="#" 
                 class="btn-remove" 
                 data-product-id="<?= $item['product_id'] ?>"
                 title="Xóa sản phẩm">
                <i class="fas fa-trash"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Cart Summary -->
      <div class="cart-summary">
        <div class="cart-total">
          <span class="label">Tổng cộng:</span>
          <span class="amount" id="total-amount"><?= formatPriceVND($total) ?></span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="cart-actions">
        <button type="submit" class="btn btn-update">
          <i class="fas fa-sync-alt"></i> Cập nhật giỏ hàng
        </button>
        
        <div class="btn-group">
          <button type="button" class="btn btn-clear" id="clear-cart">
            <i class="fas fa-trash-alt"></i> Xóa tất cả
          </button>
          <a href="checkout.php" class="btn btn-checkout">
            <i class="fas fa-credit-card"></i> Thanh toán
          </a>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>

<script>
// ===== UTILITY FUNCTIONS =====

function showLoading() {
  document.getElementById('loading').classList.add('active');
}

function hideLoading() {
  document.getElementById('loading').classList.remove('active');
}

function showMessage(message, type = 'success') {
  const icon = type === 'success' ? '✅' : '❌';
  alert(`${icon} ${message}`);
}

function formatCurrency(amount) {
  return amount.toLocaleString('vi-VN') + '₫';
}

function parseCurrency(text) {
  return parseInt(text.replace(/[₫,.\s]/g, ''));
}

// ===== CART FUNCTIONS =====

function updateSubtotal(input) {
  const row = input.closest('tr');
  const price = parseInt(row.dataset.price);
  const quantity = parseInt(input.value);
  const subtotal = price * quantity;
  
  row.querySelector('.subtotal').textContent = formatCurrency(subtotal);
  updateTotal();
}

function updateTotal() {
  let total = 0;
  document.querySelectorAll('tr[data-product-id]').forEach(row => {
    const price = parseInt(row.dataset.price);
    const quantity = parseInt(row.querySelector('.quantity-input').value);
    total += price * quantity;
  });
  
  document.getElementById('total-amount').textContent = formatCurrency(total);
}

async function refreshCartCount() {
  try {
    const res = await fetch('../api/cart_api.php');
    const data = await res.json();
    
    if (data.ok && data.cart_count !== undefined) {
      const badge = document.querySelector('.cart-count');
      
      if (data.cart_count > 0) {
        if (badge) {
          badge.textContent = data.cart_count;
        } else {
          const link = document.querySelector('.cart-link');
          if (link) {
            const span = document.createElement('span');
            span.className = 'cart-count';
            span.textContent = data.cart_count;
            link.appendChild(span);
          }
        }
      } else if (badge) {
        badge.remove();
      }
    }
  } catch (error) {
    console.error('Failed to refresh cart count:', error);
  }
}

// ===== EVENT HANDLERS =====

// Cập nhật giỏ hàng - DÙNG product_id
document.getElementById('cart-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const items = {};
  document.querySelectorAll('tr[data-product-id]').forEach(row => {
    const product_id = row.dataset.productId; // ← Dùng product_id
    const quantity = row.querySelector('.quantity-input').value;
    items[product_id] = quantity;
  });

  showLoading();
  
  try {
    const res = await fetch('../api/cart_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'update', items })
    });
    
    const data = await res.json();
    
    if (data.ok) {
      showMessage('Cập nhật giỏ hàng thành công!');
      await refreshCartCount();
      location.reload();
    } else {
      showMessage(data.message || 'Không thể cập nhật giỏ hàng', 'error');
    }
  } catch (error) {
    console.error('Update cart error:', error);
    showMessage('Lỗi kết nối máy chủ', 'error');
  } finally {
    hideLoading();
  }
});

// Xóa sản phẩm - DÙNG product_id
document.querySelectorAll('.btn-remove').forEach(btn => {
  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
    
    const product_id = btn.dataset.productId; // ← Dùng product_id
    showLoading();
    
    try {
      const res = await fetch(`../api/cart_api.php?action=remove&id=${product_id}`);
      const data = await res.json();
      
      if (data.ok) {
        showMessage('Đã xóa sản phẩm!');
        await refreshCartCount();
        location.reload();
      } else {
        showMessage(data.message || 'Không thể xóa sản phẩm', 'error');
      }
    } catch (error) {
      console.error('Remove item error:', error);
      showMessage('Lỗi kết nối máy chủ', 'error');
    } finally {
      hideLoading();
    }
  });
});

// Xóa toàn bộ giỏ hàng
document.getElementById('clear-cart')?.addEventListener('click', async () => {
  if (!confirm('⚠️ Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) return;
  
  showLoading();
  
  try {
    const res = await fetch('../api/cart_api.php?action=clear');
    const data = await res.json();
    
    if (data.ok) {
      showMessage('Đã xóá toàn bộ giỏ hàng!');
      await refreshCartCount();
      location.reload();
    } else {
      showMessage(data.message || 'Không thể xóa giỏ hàng', 'error');
    }
  } catch (error) {
    console.error('Clear cart error:', error);
    showMessage('Lỗi kết nối máy chủ', 'error');
  } finally {
    hideLoading();
  }
});

// Khởi tạo
document.addEventListener('DOMContentLoaded', () => {
  console.log('Cart page loaded successfully');
  updateTotal();
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>