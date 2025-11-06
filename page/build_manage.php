<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
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
        c.slug as category_slug,
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

// Lấy tất cả danh mục
$categories = $pdo->query("
    SELECT category_id, name, slug
    FROM categories 
    ORDER BY category_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Tạo mảng category_ids đã có sản phẩm
$used_categories = array_map(function($item) { 
    return $item['category_id'] ?? null; 
}, $items);

// Tìm các category chưa sử dụng
$available_categories = array_filter($categories, function($cat) use ($used_categories) {
    return !in_array($cat['category_id'], array_filter($used_categories));
});

$total_price = (float)array_sum(array_column($items, 'price'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quản lý cấu hình - <?= htmlspecialchars($build['name']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
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
.header {
  background: linear-gradient(135deg, #1a73e8 0%, #1557b0 100%);
  color: white;
  padding: 30px 40px;
}
.header h1 { font-size: 28px; margin-bottom: 10px; }
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
.meta {
  display: flex;
  gap: 30px;
  font-size: 14px;
  opacity: 0.95;
  margin-top: 10px;
}
.meta span { display: flex; align-items: center; gap: 6px; }
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
.btn-edit { background: #ff9800; color: white; }
.btn-edit:hover { background: #f57c00; transform: translateY(-2px); }
.btn-save { background: #4caf50; color: white; }
.btn-save:hover { background: #45a049; transform: translateY(-2px); }
.btn-back { background: #6c757d; color: white; }
.btn-back:hover { background: #5a6268; transform: translateY(-2px); }
.btn-add-cart { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
.btn-add-cart:hover { background: linear-gradient(135deg, #20c997, #28a745); transform: translateY(-2px); }
.items-list { padding: 0; }
.item-row {
  display: grid;
  grid-template-columns: 200px 1fr auto;
  gap: 20px;
  padding: 20px 40px;
  border-bottom: 1px solid #e0e0e0;
  transition: all 0.3s ease;
}
.item-row:hover { background: #f8f9fa; }
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
.product-info { display: flex; gap: 20px; }
.product-image {
  width: 100px;
  height: 100px;
  border-radius: 12px;
  object-fit: cover;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  flex-shrink: 0;
}
.product-details { flex: 1; }
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
.btn-change { background: #fff3e0; color: #f57c00; }
.btn-change:hover { background: #f57c00; color: white; transform: translateY(-2px); }
.btn-delete { background: #ffebee; color: #d32f2f; }
.btn-delete:hover { background: #d32f2f; color: white; transform: translateY(-2px); }
.add-component-section {
  padding: 30px 40px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-top: 2px dashed #dee2e6;
}
.add-component-header {
  color: #1a73e8;
  margin-bottom: 20px;
  font-size: 18px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 10px;
}
.category-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
}
.btn-add-component {
  background: white;
  border: 2px solid #e0e0e0;
  padding: 16px 20px;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #495057;
}
.btn-add-component:hover {
  border-color: #1a73e8;
  color: white;
  background: linear-gradient(135deg, #1a73e8, #1557b0);
  transform: translateY(-3px);
}
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
}
.total-amount { font-size: 28px; font-weight: 800; }
.empty-state { text-align: center; padding: 60px 20px; color: #999; }
.empty-state i { font-size: 64px; margin-bottom: 20px; opacity: 0.5; }
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
.modal.active { display: flex; }
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
  display: flex;
  align-items: center;
  gap: 10px;
}
.modal-actions { display: flex; gap: 10px; margin-top: 20px; }
.modal-actions .btn { flex: 1; }
.hidden { display: none; }
</style>
</head>
<body>

<div class="container">
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

  <div class="action-bar">
    <div>
      <button class="btn btn-edit" id="edit-btn"><i class="fa fa-pen"></i> Sửa tên</button>
      <button class="btn btn-save hidden" id="save-name-btn"><i class="fa fa-save"></i> Lưu tên</button>
    </div>
    <div style="display: flex; gap: 10px;">
      <button class="btn btn-add-cart" id="add-cart-btn"><i class="fa fa-cart-plus"></i> Thêm vào giỏ</button>
      <a href="builds.php" class="btn btn-back"><i class="fa fa-arrow-left"></i> Quay lại</a>
    </div>
  </div>

  <div class="items-list">
    <?php if (empty($items)): ?>
      <div class="empty-state">
        <i class="fa fa-box-open"></i>
        <p>Chưa có linh kiện nào</p>
      </div>
    <?php else: ?>
      <?php foreach ($items as $item): ?>
      <div class="item-row" data-item-id="<?= $item['item_id'] ?>" data-product-id="<?= $item['product_id'] ?>">
        <div class="category-badge">
          <i class="fa fa-tag"></i>
          <?= htmlspecialchars($item['category_name']) ?>
        </div>

        <div class="product-info">
          <img src="../uploads/<?= htmlspecialchars($item['main_image'] ?? 'no-image.png') ?>" 
               alt="<?= htmlspecialchars($item['product_name']) ?>" 
               class="product-image"
               onerror="this.src='../uploads/img/no-image.png'">
          <div class="product-details">
            <div class="product-name"><?= htmlspecialchars($item['product_name']) ?></div>
            <?php if ($item['brand_name']): ?>
              <span class="product-brand"><?= htmlspecialchars($item['brand_name']) ?></span>
            <?php endif; ?>
            <?php if ($item['description']): ?>
              <div class="product-description"><?= htmlspecialchars($item['description']) ?></div>
            <?php endif; ?>
            <div class="product-price"><?= formatPriceVND($item['price']) ?></div>
          </div>
        </div>

        <div class="item-actions">
          <button class="btn-icon btn-change" 
                  onclick="changeProduct(<?= $item['category_id'] ?>, <?= $item['item_id'] ?>, '<?= htmlspecialchars($item['category_name']) ?>')"
                  title="Đổi sản phẩm">
            <i class="fa fa-exchange-alt"></i>
          </button>
          <button class="btn-icon btn-delete" 
                  onclick="deleteItem(<?= $item['item_id'] ?>)"
                  title="Xóa">
            <i class="fa fa-trash"></i>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (!empty($available_categories)): ?>
  <div class="add-component-section">
    <div class="add-component-header">
      <i class="fa fa-plus-circle"></i>
      <span>Thêm Linh Kiện Khác</span>
    </div>
    <div class="category-grid">
      <?php foreach ($available_categories as $cat): ?>
      <button class="btn-add-component" 
              onclick="addProductToCategory(<?= $cat['category_id'] ?>, '<?= htmlspecialchars($cat['name']) ?>')">
        <i class="fa fa-plus"></i>
        <span><?= htmlspecialchars($cat['name']) ?></span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="footer">
    <div class="total-box">
      <span>💰 Tổng giá trị:</span>
      <span class="total-amount" id="total-price"><?= formatPriceVND($total_price) ?></span>
    </div>
  </div>
</div>

<div class="modal" id="delete-modal">
  <div class="modal-content">
    <div class="modal-header">
      <i class="fa fa-exclamation-triangle" style="color: #f44336;"></i>
      <span>Xác nhận xóa</span>
    </div>
    <p>Bạn có chắc muốn xóa linh kiện này?</p>
    <div class="modal-actions">
      <button class="btn btn-back" onclick="closeDeleteModal()">Hủy</button>
      <button class="btn btn-delete" id="confirm-delete-btn">
        <i class="fa fa-trash"></i> Xóa
      </button>
    </div>
  </div>
</div>

<script>
const buildId = <?= (int)$build_id ?>;
let deleteItemId = null;

function changeProduct(categoryId, itemId, categoryName) {
  console.log('🔄 Change:', {categoryId, itemId, categoryName});
  sessionStorage.setItem('build_mode', 'replace');
  sessionStorage.setItem('replacing_item_id', itemId);
  sessionStorage.setItem('replacing_build_id', buildId);
  sessionStorage.setItem('replacing_category', categoryName);
  window.location.href = `products.php?category_id=${categoryId}&build_id=${buildId}&mode=replace&item_id=${itemId}`;
}

function addProductToCategory(categoryId, categoryName) {
  console.log('➕ Add:', {categoryId, categoryName});
  sessionStorage.setItem('build_mode', 'add');
  sessionStorage.setItem('adding_build_id', buildId);
  sessionStorage.setItem('adding_category', categoryName);
  window.location.href = `products.php?category_id=${categoryId}&build_id=${buildId}&mode=add`;
}

function deleteItem(itemId) {
  console.log('🗑️ Delete requested:', itemId, typeof itemId);
  deleteItemId = itemId;
  document.getElementById('delete-modal').classList.add('active');
}

function closeDeleteModal() {
  document.getElementById('delete-modal').classList.remove('active');
  // KHÔNG reset deleteItemId ở đây, để confirm button xử lý
}

document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
  // Lưu giá trị ngay lập tức vào biến local
  const itemIdToDelete = deleteItemId;
  const currentBuildId = buildId;
  
  console.log('🗑️ Confirm delete:', itemIdToDelete, 'build:', currentBuildId);
  
  if (!itemIdToDelete) {
    console.error('❌ itemIdToDelete is NULL');
    alert('❌ Lỗi: Không xác định được item cần xóa');
    return;
  }

  closeDeleteModal();

  const requestData = { 
    item_id: parseInt(itemIdToDelete),
    build_id: parseInt(currentBuildId)
  };
  
  console.log('📤 Request:', requestData);
  console.log('   item_id value:', requestData.item_id, 'type:', typeof requestData.item_id);
  console.log('   build_id value:', requestData.build_id, 'type:', typeof requestData.build_id);

  try {
    const res = await fetch('../api/delete_build_item.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(requestData)
    });
    
    console.log('📨 Status:', res.status);
    const data = await res.json();
    console.log('📨 Data:', data);
    
    if (data.success) {
      const row = document.querySelector(`[data-item-id="${itemIdToDelete}"]`);
      if (row) {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-50px)';
        setTimeout(() => {
          row.remove();
          updateTotalPrice();
          if (document.querySelectorAll('.item-row').length === 0) {
            location.reload();
          }
        }, 300);
      }
      alert('✅ Đã xóa linh kiện!');
    } else {
      console.error('❌ Failed:', data);
      alert('❌ ' + (data.error || 'Không thể xóa'));
    }
  } catch (e) {
    console.error('❌ Error:', e);
    alert('❌ Lỗi kết nối!');
  }
});

function formatPriceJS(num) {
  return new Intl.NumberFormat('vi-VN').format(num);
}

function updateTotalPrice() {
  let total = 0;
  document.querySelectorAll('.product-price').forEach(el => {
    const priceText = el.textContent.replace(/[^0-9]/g, '');
    total += parseInt(priceText, 10) || 0;
  });
  document.getElementById('total-price').textContent = formatPriceJS(total) + '₫';
}

document.getElementById('edit-btn').addEventListener('click', () => {
  document.getElementById('build-name-display').classList.add('hidden');
  document.getElementById('build-name-input').classList.remove('hidden');
  document.getElementById('edit-btn').classList.add('hidden');
  document.getElementById('save-name-btn').classList.remove('hidden');
  document.getElementById('build-name-input').focus();
  document.getElementById('build-name-input').select();
});

document.getElementById('save-name-btn').addEventListener('click', async () => {
  const newName = document.getElementById('build-name-input').value.trim();
  if (!newName) {
    alert('⚠️ Tên không được để trống!');
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
      alert('✅ Đã cập nhật tên!');
    } else {
      alert('❌ ' + (data.error || 'Không thể cập nhật'));
    }
  } catch (e) {
    console.error(e);
    alert('❌ Lỗi kết nối!');
  }
});

document.getElementById('build-name-input').addEventListener('keypress', (e) => {
  if (e.key === 'Enter') document.getElementById('save-name-btn').click();
});

document.getElementById('add-cart-btn').addEventListener('click', async () => {
  if (document.querySelectorAll('.item-row').length === 0) {
    alert('⚠️ Chưa có linh kiện nào!');
    return;
  }

  try {
    const res = await fetch('../api/add_build_to_cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ build_id: buildId }),
      credentials: 'include'
    });
    const data = await res.json();
    
    if (data.success) {
      alert('🛒 Đã thêm vào giỏ!');
      window.location.href = 'cart.php';
    } else {
      alert('⚠️ ' + (data.error || 'Không thể thêm'));
    }
  } catch (e) {
    console.error(e);
    alert('❌ Lỗi kết nối!');
  }
});

document.addEventListener('DOMContentLoaded', () => {
  console.log('✅ Page loaded, buildId:', buildId);
  updateTotalPrice();
  
  const urlParams = new URLSearchParams(window.location.search);
  const success = urlParams.get('success');
  
  if (success === 'replaced') {
    alert('✅ Đã thay thế sản phẩm!');
    window.history.replaceState({}, '', window.location.pathname + '?id=' + buildId);
  } else if (success === 'added') {
    alert('✅ Đã thêm sản phẩm!');
    window.history.replaceState({}, '', window.location.pathname + '?id=' + buildId);
  }
});
</script>

</body>
</html>