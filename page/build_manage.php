<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
$pdo = getPDO();

$build_id = $_GET['id'] ?? 0;
if (!$build_id) die("Thiếu ID cấu hình");

// Lấy chi tiết cấu hình
$stmt = $pdo->prepare("
    SELECT b.*, u.full_name, u.username 
    FROM builds b 
    LEFT JOIN users u ON b.user_id = u.user_id 
    WHERE b.build_id = ?
");
$stmt->execute([$build_id]);
$build = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$build) die("Không tìm thấy cấu hình này");

// Lấy danh sách linh kiện trong cấu hình
$stmt = $pdo->prepare("
    SELECT 
        bi.build_item_id as item_id,
        p.product_id,
        p.name as product_name,
        p.price,
        p.main_image,
        p.description,
        c.category_id,
        c.name as category_name,
        b.name as brand_name
    FROM build_items bi
    JOIN products p ON bi.product_id = p.product_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE bi.build_id = ?
    ORDER BY c.name ASC
");
$stmt->execute([$build_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_price = array_sum(array_column($items, 'price'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quản lý cấu hình - <?= htmlspecialchars($build['name']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  padding: 20px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  overflow: hidden;
}

/* Header */
.header {
  background: linear-gradient(135deg, #1a73e8 0%, #1557b0 100%);
  color: white;
  padding: 30px 40px;
}

.header h1 {
  font-size: 28px;
  margin-bottom: 10px;
}

.header h1 input {
  background: rgba(255,255,255,0.2);
  border: 2px solid rgba(255,255,255,0.3);
  color: white;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 24px;
  font-weight: 700;
  width: 100%;
  max-width: 600px;
}

.header h1 input::placeholder {
  color: rgba(255,255,255,0.7);
}

.meta {
  display: flex;
  gap: 30px;
  font-size: 14px;
  opacity: 0.95;
  margin-top: 10px;
}

.meta span {
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Action Bar */
.action-bar {
  padding: 20px 40px;
  background: #f8f9fa;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e0e0e0;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
}

.btn-edit {
  background: #ff9800;
  color: white;
}

.btn-edit:hover {
  background: #f57c00;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.btn-save {
  background: #4caf50;
  color: white;
}

.btn-save:hover {
  background: #45a049;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.btn-back {
  background: #6c757d;
  color: white;
}

.btn-back:hover {
  background: #5a6268;
  transform: translateY(-2px);
}

.btn-add-cart {
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
}

.btn-add-cart:hover {
  background: linear-gradient(135deg, #20c997, #28a745);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

/* Items List */
.items-list {
  padding: 0;
}

.item-row {
  display: grid;
  grid-template-columns: 200px 1fr auto;
  gap: 20px;
  padding: 20px 40px;
  border-bottom: 1px solid #e0e0e0;
  transition: all 0.3s ease;
}

.item-row:hover {
  background: #f8f9fa;
}

/* Category Badge */
.category-badge {
  background: linear-gradient(135deg, #1a73e8, #1557b0);
  color: white;
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  align-self: flex-start;
  margin-top: 10px;
}

/* Product Info */
.product-info {
  display: flex;
  gap: 20px;
}

.product-image {
  width: 100px;
  height: 100px;
  border-radius: 12px;
  object-fit: cover;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  flex-shrink: 0;
}

.product-details {
  flex: 1;
}

.product-name {
  font-size: 16px;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 8px;
  line-height: 1.4;
}

.product-brand {
  display: inline-block;
  background: #e3f2fd;
  color: #1565c0;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 8px;
}

.product-description {
  color: #666;
  font-size: 13px;
  line-height: 1.6;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-price {
  color: #1a73e8;
  font-size: 18px;
  font-weight: 800;
  margin-top: 8px;
}

/* Actions */
.item-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: flex-end;
  justify-content: center;
}

.btn-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  font-size: 16px;
}

.btn-change {
  background: #fff3e0;
  color: #f57c00;
}

.btn-change:hover {
  background: #f57c00;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 124, 0, 0.3);
}

.btn-delete {
  background: #ffebee;
  color: #d32f2f;
}

.btn-delete:hover {
  background: #d32f2f;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3);
}

/* Footer */
.footer {
  padding: 30px 40px;
  background: #f8f9fa;
  border-top: 1px solid #e0e0e0;
}

.total-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #1a73e8, #1557b0);
  color: white;
  padding: 20px 30px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(26, 115, 232, 0.3);
}

.total-label {
  font-size: 18px;
  font-weight: 600;
}

.total-amount {
  font-size: 28px;
  font-weight: 800;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #999;
}

.empty-state i {
  font-size: 64px;
  margin-bottom: 20px;
  opacity: 0.5;
}

/* Modal */
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  z-index: 1000;
  align-items: center;
  justify-content: center;
}

.modal.active {
  display: flex;
}

.modal-content {
  background: white;
  padding: 30px;
  border-radius: 16px;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 20px;
  color: #1a1a1a;
}

.modal-actions {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}

.modal-actions .btn {
  flex: 1;
}

.hidden {
  display: none;
}

/* Responsive */
@media (max-width: 768px) {
  .header, .action-bar, .item-row, .footer {
    padding: 20px;
  }

  .item-row {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .product-info {
    flex-direction: column;
  }

  .item-actions {
    flex-direction: row;
    justify-content: flex-start;
  }

  .meta {
    flex-direction: column;
    gap: 10px;
  }

  .total-box {
    flex-direction: column;
    gap: 10px;
    text-align: center;
  }
}
</style>
</head>
<body>

<div class="container">
  <!-- Header -->
  <div class="header">
    <h1>
      <span id="build-name-display">🧩 <?= htmlspecialchars($build['name']) ?></span>
      <input type="text" id="build-name-input" class="hidden" value="<?= htmlspecialchars($build['name']) ?>">
    </h1>
    <div class="meta">
      <span><i class="fa fa-user"></i> <?= htmlspecialchars($build['full_name'] ?? $build['username'] ?? 'Không rõ') ?></span>
      <span><i class="fa fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($build['created_at'])) ?></span>
      <span><i class="fa fa-box"></i> <?= count($items) ?> linh kiện</span>
    </div>
  </div>

  <!-- Action Bar -->
  <div class="action-bar">
    <div>
      <button class="btn btn-edit" id="edit-btn">
        <i class="fa fa-pen"></i> Sửa tên cấu hình
      </button>
      <button class="btn btn-save hidden" id="save-name-btn">
        <i class="fa fa-save"></i> Lưu tên
      </button>
    </div>
    <div style="display: flex; gap: 10px;">
      <button class="btn btn-add-cart" id="add-cart-btn">
        <i class="fa fa-cart-plus"></i> Thêm vào giỏ hàng
      </button>
      <a href="builds.php" class="btn btn-back">
        <i class="fa fa-arrow-left"></i> Quay lại
      </a>
    </div>
  </div>

  <!-- Items List -->
  <div class="items-list">
    <?php if (empty($items)): ?>
      <div class="empty-state">
        <i class="fa fa-box-open"></i>
        <p>Chưa có linh kiện nào trong cấu hình</p>
      </div>
    <?php else: ?>
      <?php foreach ($items as $item): ?>
      <div class="item-row" data-item-id="<?= $item['item_id'] ?>" data-product-id="<?= $item['product_id'] ?>">
        <!-- Category Badge -->
        <div class="category-badge">
          <i class="fa fa-tag"></i>
          <?= htmlspecialchars($item['category_name']) ?>
        </div>

        <!-- Product Info -->
        <div class="product-info">
          <img src="../uploads/<?= htmlspecialchars($item['main_image'] ?? 'no-image.png') ?>" 
               alt="<?= htmlspecialchars($item['product_name']) ?>" 
               class="product-image">
          <div class="product-details">
            <div class="product-name"><?= htmlspecialchars($item['product_name']) ?></div>
            <?php if ($item['brand_name']): ?>
              <span class="product-brand"><?= htmlspecialchars($item['brand_name']) ?></span>
            <?php endif; ?>
            <?php if ($item['description']): ?>
              <div class="product-description">
                <?= htmlspecialchars($item['description']) ?>
              </div>
            <?php endif; ?>
            <div class="product-price"><?= number_format($item['price'], 0, ',', '.') ?> ₫</div>
          </div>
        </div>

        <!-- Actions -->
        <div class="item-actions">
          <button class="btn-icon btn-change" 
                  onclick="changeProduct(<?= $item['category_id'] ?>, <?= $item['item_id'] ?>)"
                  title="Đổi sản phẩm">
            <i class="fa fa-exchange-alt"></i>
          </button>
          <button class="btn-icon btn-delete" 
                  onclick="deleteItem(<?= $item['item_id'] ?>)"
                  title="Xóa linh kiện">
            <i class="fa fa-trash"></i>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Footer -->
  <div class="footer">
    <div class="total-box">
      <span class="total-label">Tổng giá trị cấu hình:</span>
      <span class="total-amount" id="total-price"><?= number_format($total_price, 0, ',', '.') ?> ₫</span>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="delete-modal">
  <div class="modal-content">
    <div class="modal-header">
      <i class="fa fa-exclamation-triangle" style="color: #f44336;"></i>
      Xác nhận xóa
    </div>
    <p>Bạn có chắc muốn xóa linh kiện này khỏi cấu hình?</p>
    <div class="modal-actions">
      <button class="btn btn-back" onclick="closeDeleteModal()">Hủy</button>
      <button class="btn btn-delete" id="confirm-delete-btn">Xóa</button>
    </div>
  </div>
</div>

<script>
const buildId = <?= (int)$build_id ?>;
let deleteItemId = null;

// Sửa tên cấu hình
document.getElementById('edit-btn').addEventListener('click', () => {
  document.getElementById('build-name-display').classList.add('hidden');
  document.getElementById('build-name-input').classList.remove('hidden');
  document.getElementById('edit-btn').classList.add('hidden');
  document.getElementById('save-name-btn').classList.remove('hidden');
  document.getElementById('build-name-input').focus();
});

document.getElementById('save-name-btn').addEventListener('click', async () => {
  const newName = document.getElementById('build-name-input').value.trim();
  if (!newName) {
    alert('⚠️ Tên cấu hình không được để trống!');
    return;
  }

  try {
    const res = await fetch('../api/update_build_name.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ build_id: buildId, name: newName })
    });
    const data = await res.json();
    
    if (data.success) {
      document.getElementById('build-name-display').textContent = '🧩 ' + newName;
      document.getElementById('build-name-display').classList.remove('hidden');
      document.getElementById('build-name-input').classList.add('hidden');
      document.getElementById('edit-btn').classList.remove('hidden');
      document.getElementById('save-name-btn').classList.add('hidden');
      alert('✅ Đã cập nhật tên cấu hình!');
    } else {
      alert('❌ ' + (data.error || 'Không thể cập nhật'));
    }
  } catch (e) {
    console.error(e);
    alert('❌ Lỗi kết nối server!');
  }
});

// Đổi sản phẩm (chuyển đến trang products với filter)
function changeProduct(categoryId, itemId) {
  window.location.href = `products.php?category_id=${categoryId}&build_id=${buildId}&item_id=${itemId}`;
}

// Xóa linh kiện
function deleteItem(itemId) {
  deleteItemId = itemId;
  document.getElementById('delete-modal').classList.add('active');
}

function closeDeleteModal() {
  document.getElementById('delete-modal').classList.remove('active');
  deleteItemId = null;
}

document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
  if (!deleteItemId) return;

  try {
    const res = await fetch('../api/delete_build.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ item_id: deleteItemId })
    });
    const data = await res.json();
    
    if (data.success) {
      const row = document.querySelector(`[data-item-id="${deleteItemId}"]`);
      if (row) row.remove();
      
      // Cập nhật tổng tiền
      updateTotalPrice();
      
      closeDeleteModal();
      alert('✅ Đã xóa linh kiện!');
      
      // Reload nếu không còn item nào
      if (document.querySelectorAll('.item-row').length === 0) {
        location.reload();
      }
    } else {
      alert('❌ ' + (data.error || 'Không thể xóa'));
    }
  } catch (e) {
    console.error(e);
    alert('❌ Lỗi kết nối server!');
  }
});

// Cập nhật tổng tiền
function updateTotalPrice() {
  let total = 0;
  document.querySelectorAll('.product-price').forEach(el => {
    const price = parseInt(el.textContent.replace(/[₫,\s]/g, ''));
    if (!isNaN(price)) total += price;
  });
  document.getElementById('total-price').textContent = total.toLocaleString() + ' ₫';
}

// Thêm vào giỏ hàng
document.getElementById('add-cart-btn').addEventListener('click', async () => {
  try {
    const res = await fetch('../api/add_build_to_cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ build_id: buildId })
    });
    const data = await res.json();
    
    if (data.success) {
      alert('🛒 Đã thêm cấu hình vào giỏ hàng!');
      window.location.href = 'cart.php';
    } else {
      alert('⚠️ ' + (data.error || 'Không thể thêm vào giỏ hàng'));
    }
  } catch (e) {
    console.error(e);
    alert('❌ Lỗi kết nối server!');
  }
});

// Close modal khi click outside
document.getElementById('delete-modal').addEventListener('click', (e) => {
  if (e.target === e.currentTarget) {
    closeDeleteModal();
  }
});
</script>

</body>