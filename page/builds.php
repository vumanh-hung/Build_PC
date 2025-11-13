<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ✅ Lấy TẤT CẢ danh mục sản phẩm (giống products.php)
$pdo = getPDO();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Lấy danh sách cấu hình của user hiện tại
$user_id = getCurrentUserId();
$builds = [];
if ($user_id) {
    $builds = getUserBuilds($user_id);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Xây dựng cấu hình - BuildPC.vn</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body {
  font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;
  background: linear-gradient(135deg,#f5f7fa 0%,#c3cfe2 100%);
  color:#333;
  min-height:100vh;
}

/* ===== HEADER ===== */
header {
  background: linear-gradient(90deg,#007bff 0%,#00aaff 50%,#007bff 100%);
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:12px 40px;
  box-shadow:0 8px 24px rgba(0,107,255,0.15);
  position:sticky;
  top:0;
  z-index:999;
  gap:20px;
}

.header-left{display:flex;align-items:center;gap:40px;}
.logo span{color:white;font-weight:800;font-size:20px;letter-spacing:.5px;}
.nav {display:flex;gap:28px;}
.nav a{color:white;text-decoration:none;font-weight:500;font-size:13px;transition:.3s;}
.nav a:hover,.nav a.active{color:#ffeb3b;}

.header-right{display:flex;align-items:center;gap:16px;}
.cart-link{
  position:relative;
  background:rgba(255,255,255,0.95);
  color:#007bff;
  padding:8px 16px;
  border-radius:20px;
  text-decoration:none;
  font-weight:600;
  font-size:12px;
  transition:.3s;
  display:inline-flex;align-items:center;gap:8px;
}
.cart-link:hover{background:white;box-shadow:0 6px 20px rgba(0,107,255,0.3);transform:translateY(-3px);}
.cart-count{
  position:absolute;
  top:-8px;right:-8px;
  background:linear-gradient(135deg,#ffeb3b,#ff9800);
  color:#111;
  font-size:10px;
  font-weight:900;
  border-radius:50%;
  width:22px;height:22px;
  display:flex;align-items:center;justify-content:center;
}
.login-btn,.logout-btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 16px;
  border-radius:20px;font-weight:600;font-size:12px;
  text-decoration:none;transition:.3s;cursor:pointer;
}
.login-btn{background:rgba(255,255,255,0.2);color:#fff;border:2px solid rgba(255,255,255,0.5);}
.logout-btn{background:linear-gradient(135deg,#ff5252,#ff1744);color:white;border:none;}
.logout-btn:hover{background:linear-gradient(135deg,#ff1744,#d50000);}
.welcome{color:#fff;font-size:12px;font-weight:600;}

/* ===== BANNER ===== */
.banner{
  background:linear-gradient(135deg,#1a73e8 0%,#1e88e5 50%,#1565c0 100%);
  color:white;
  text-align:center;
  padding:50px 20px;
  border-radius:12px;
  margin:40px auto;
  max-width:1200px;
}
.banner h1{font-size:32px;font-weight:900;margin-bottom:10px;}
.banner p{font-size:14px;opacity:.95;}

/* ===== MAIN CONTAINER ===== */
.container {
  max-width: 1100px;
  margin: 40px auto;
  padding: 20px;
}

/* ===== INSTRUCTION BOX ===== */
.instruction-box {
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  padding: 25px;
  border-radius: 12px;
  margin-bottom: 30px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(26, 115, 232, 0.15);
}

.instruction-box h3 {
  color: #1a73e8;
  margin-bottom: 12px;
  font-size: 20px;
}

.instruction-box p {
  color: #555;
  margin-bottom: 20px;
  font-size: 14px;
}

.btn-new-build {
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
  border: none;
  padding: 14px 28px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-new-build:hover {
  background: linear-gradient(135deg, #20c997, #28a745);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
}

.btn-new-build i {
  font-size: 18px;
}

/* ===== GRID ===== */
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.item {
  background: white;
  border-radius: 12px;
  padding: 25px;
  text-align: center;
  box-shadow: 0 4px 10px rgba(0,0,0,.08);
  transition: all .3s ease;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  border: 2px solid transparent;
}

.item::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(26,115,232,0.1), transparent);
  transition: left 0.5s ease;
}

.item:hover::before {
  left: 100%;
}

.item:hover {
  transform: translateY(-8px);
  box-shadow: 0 8px 24px rgba(26,115,232,.25);
  border-color: #1a73e8;
}

.item h3 {
  margin-bottom: 15px;
  color: #1a73e8;
  font-size: 18px;
  font-weight: 700;
}

.item img {
  width: 120px;
  height: 120px;
  object-fit: contain;
  margin: 20px 0;
  transition: transform 0.3s ease;
}

.item:hover img {
  transform: scale(1.15);
}

.item p {
  color: #666;
  font-size: 14px;
  font-weight: 600;
}

/* ===== BUILD MODE INDICATOR ===== */
.build-mode-indicator {
  background: linear-gradient(135deg, #1a73e8, #1557b0);
  color: white;
  padding: 16px 24px;
  border-radius: 12px;
  margin-bottom: 20px;
  display: none;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 4px 12px rgba(26, 115, 232, 0.3);
}

.build-mode-indicator.active {
  display: flex;
}

.build-mode-indicator .text {
  flex: 1;
}

.build-mode-indicator h4 {
  margin-bottom: 5px;
  font-size: 16px;
}

.build-mode-indicator p {
  font-size: 13px;
  opacity: 0.9;
}

.build-mode-indicator .btn-group {
  display: flex;
  gap: 12px;
  align-items: center;
}

.btn-save-build {
  background: linear-gradient(135deg, #28a745, #20c997);
  border: none;
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.btn-save-build:hover {
  background: linear-gradient(135deg, #20c997, #28a745);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
}

.btn-cancel-build {
  background: rgba(255, 255, 255, 0.2);
  border: 2px solid rgba(255, 255, 255, 0.3);
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-cancel-build:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* ===== SAVED BUILDS ===== */
.section-title {
  margin-top: 60px;
  margin-bottom: 30px;
  color: #1a73e8;
  text-align: center;
  font-size: 28px;
  font-weight: 800;
}

.saved-builds {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
  margin-top: 20px;
}

.build-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 3px 10px rgba(0,0,0,.1);
  text-align: center;
  transition: all .3s ease;
  border: 2px solid transparent;
}

.build-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(26, 115, 232, 0.2);
  border-color: #1a73e8;
}

.build-card h3 {
  color: #1a73e8;
  margin-bottom: 12px;
  font-size: 18px;
}

.build-card p {
  margin: 8px 0;
  color: #444;
  font-size: 14px;
}

.btn-group {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-top: 15px;
  flex-wrap: wrap;
}

.btn {
  padding: 10px 16px;
  border-radius: 8px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-view {
  background: #1a73e8;
  color: white;
}

.btn-view:hover {
  background: #1557b0;
  transform: translateY(-2px);
}

.btn-cart {
  background: #28a745;
  color: white;
  border: none;
  cursor: pointer;
}

.btn-cart:hover {
  background: #218838;
  transform: translateY(-2px);
}

.btn-del {
  background: #dc3545;
  color: white;
  border: none;
  cursor: pointer;
}

.btn-del:hover {
  background: #c82333;
  transform: translateY(-2px);
}

/* ===== ANIMATIONS ===== */
@keyframes cartShake {
  0% { transform: rotate(0deg); }
  25% { transform: rotate(-15deg); }
  50% { transform: rotate(15deg); }
  75% { transform: rotate(-10deg); }
  100% { transform: rotate(0deg); }
}

.cart-shake {
  animation: cartShake 0.6s ease;
}

/* ===== POPUP ===== */
.cart-popup {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: #28a745;
  color: white;
  padding: 14px 22px;
  border-radius: 8px;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  opacity: 0;
  transform: translateY(30px);
  transition: all 0.4s ease;
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 8px;
}

.cart-popup.show {
  opacity: 1;
  transform: translateY(0);
}
</style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header>
  <div class="header-left">
    <div class="logo">
      <a href="../index.php" style="text-decoration: none;">
        <span>🖥️ BuildPC.vn</span>
      </a>
    </div>
    <nav class="nav">
      <a href="../index.php">Trang chủ</a>
      <a href="products.php">Sản phẩm</a>
      <a href="brands.php">Thương hiệu</a>
      <a href="builds.php" class="active">Xây dựng cấu hình</a>
      <a href="about.php">Giới thiệu</a>
      <a href="contact.php">Liên hệ</a>
    </nav>
  </div>

  <div class="header-right">
    <a href="cart.php" class="cart-link">
      <i class="fa-solid fa-cart-shopping"></i> Giỏ hàng
      <?php 
      $cart_count = getCartCount($user_id);
      if ($cart_count > 0): 
      ?>
        <span class="cart-count"><?= $cart_count ?></span>
      <?php endif; ?>
    </a>

    <?php if (isLoggedIn()): ?>
      <span class="welcome">👋 <?= escape($_SESSION['user']['username'] ?? $_SESSION['user']['full_name']) ?></span>
      <a href="logout.php" class="logout-btn">Đăng xuất</a>
    <?php else: ?>
      <a href="login.php" class="login-btn"><i class="fa-solid fa-user"></i> Đăng nhập</a>
    <?php endif; ?>
  </div>
</header>

<!-- ===== BANNER ===== -->
<div class="banner">
  <h1>🧩 Xây dựng cấu hình PC của bạn</h1>
  <p>Chọn linh kiện phù hợp để tạo nên bộ máy mạnh mẽ nhất 💪</p>
</div>

<div class="container">
  <!-- ===== BUILD MODE INDICATOR ===== -->
  <div class="build-mode-indicator" id="buildModeIndicator">
    <div class="text">
      <h4>🔧 Đang tạo cấu hình mới</h4>
      <p>Click vào linh kiện để chọn sản phẩm</p>
    </div>
    <div class="btn-group">
      <button class="btn-save-build" onclick="finishBuild()">
        <i class="fa fa-check-circle"></i> Hoàn thành & Lưu
      </button>
      <button class="btn-cancel-build" onclick="cancelNewBuild()">
        <i class="fa fa-times"></i> Hủy
      </button>
    </div>
  </div>

  <!-- ===== INSTRUCTION BOX ===== -->
  <div class="instruction-box" id="instructionBox">
    <h3>📝 Cách tạo cấu hình mới</h3>
    <p>Nhấn nút bên dưới, sau đó click vào các linh kiện để chọn sản phẩm cho cấu hình của bạn</p>
    <button class="btn-new-build" onclick="startNewBuild()">
      <i class="fa fa-plus-circle"></i>
      <span>Bắt đầu tạo cấu hình</span>
    </button>
  </div>

  <!-- ===== CATEGORIES GRID ===== -->
  <div class="grid" id="categoriesGrid">
    <?php foreach ($categories as $cat): ?>
      <div class="item category-item" 
           data-category-id="<?= $cat['category_id'] ?>"
           data-category-name="<?= escape($cat['name']) ?>">
        <h3><?= escape($cat['name']) ?></h3>
        <img src="../assets/img/<?= strtolower($cat['name']) ?>.png"
             onerror="this.src='../uploads/img/pc-part.png'"
             alt="<?= escape($cat['name']) ?>">
        <p>Chọn <?= escape($cat['name']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- ===== SAVED BUILDS ===== -->
  <h2 class="section-title">🧩 Cấu hình đã lưu</h2>
  <?php if (!$user_id): ?>
    <p style="text-align:center;color:#777;font-size:15px;">⚠️ Vui lòng đăng nhập để xem các cấu hình đã lưu.</p>
  <?php elseif (empty($builds)): ?>
    <p style="text-align:center;color:#777;font-size:15px;">📭 Bạn chưa có cấu hình nào được lưu.</p>
  <?php else: ?>
    <div class="saved-builds">
      <?php foreach ($builds as $b): ?>
        <div class="build-card">
          <h3><?= escape($b['name']) ?></h3>
          <p><strong><?= formatPriceVND($b['total_price']) ?></strong></p>
          <p><small>Ngày tạo: <?= formatDate($b['created_at']) ?></small></p>
          <div class="btn-group">
            <a href="build_manage.php?id=<?= $b['build_id'] ?>" class="btn btn-view">
              <i class="fa fa-edit"></i> Quản lý
            </a>
            <button class="btn btn-cart" onclick="addBuildToCart(<?= $b['build_id'] ?>)">
              <i class="fa fa-cart-plus"></i>
            </button>
            <button class="btn btn-del" onclick="deleteBuild(<?= $b['build_id'] ?>)">
              <i class="fa fa-trash"></i>
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ===== POPUP ===== -->
<div id="cart-popup" class="cart-popup">
  <i class="fa fa-check-circle"></i>
  <span>🛒 Đã thêm vào giỏ hàng!</span>
</div>

<!-- ===== AUDIO ===== -->
<audio id="tingSound" preload="auto">
  <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<script>
// ===== AUDIO INIT =====
document.addEventListener("click", () => {
  const sound = document.getElementById("tingSound");
  if (sound && sound.paused) {
    sound.play().then(() => { sound.pause(); sound.currentTime = 0; }).catch(()=>{});
  }
}, { once: true });

// ===== BUILD MODE STATE =====
let isBuildMode = false;
let currentBuildId = null;

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
  console.log('✅ Builds page loaded');
  
  // Check if returning from products page
  checkBuildModeState();
  
  // Attach click handlers to category items
  attachCategoryClickHandlers();
});

function checkBuildModeState() {
  const buildId = sessionStorage.getItem('current_build_id');
  const buildMode = sessionStorage.getItem('build_creation_mode');
  
  if (buildMode === 'creating' && buildId) {
    isBuildMode = true;
    currentBuildId = buildId;
    enterBuildMode();
  }
}

function attachCategoryClickHandlers() {
  document.querySelectorAll('.category-item').forEach(item => {
    item.addEventListener('click', function() {
      const categoryId = this.dataset.categoryId;
      const categoryName = this.dataset.categoryName;
      
      if (isBuildMode && currentBuildId) {
        // Redirect to products page in ADD mode
        goToProductsPage(categoryId, categoryName);
      } else {
        // Show instruction
        alert('⚠️ Vui lòng nhấn "Bắt đầu tạo cấu hình" trước khi chọn linh kiện!');
      }
    });
  });
}

// ===== START NEW BUILD =====
async function startNewBuild() {
  console.log('🔧 Starting new build...');
  
  if (!<?= $user_id ? 'true' : 'false' ?>) {
    alert('⚠️ Vui lòng đăng nhập để tạo cấu hình!');
    window.location.href = 'login.php';
    return;
  }
  
  try {
    // Create empty build
    const res = await fetch('../api/create_empty_build.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: 'Cấu hình mới' }),
      credentials: 'include'
    });
    
    const data = await res.json();
    
    if (data.success && data.build_id) {
      console.log('✅ Build created:', data.build_id);
      
      // Store in sessionStorage
      isBuildMode = true;
      currentBuildId = data.build_id;
      sessionStorage.setItem('current_build_id', currentBuildId);
      sessionStorage.setItem('build_creation_mode', 'creating');
      
      // Enter build mode
      enterBuildMode();
    } else {
      alert('❌ ' + (data.error || 'Không thể tạo cấu hình'));
    }
  } catch (e) {
    console.error('❌ Error:', e);
    alert('❌ Lỗi kết nối máy chủ!');
  }
}

function enterBuildMode() {
  console.log('🔧 Entering build mode');
  
  // Hide instruction, show indicator
  document.getElementById('instructionBox').style.display = 'none';
  document.getElementById('buildModeIndicator').classList.add('active');
  
  // Highlight categories
  document.querySelectorAll('.category-item').forEach(item => {
    item.style.borderColor = '#28a745';
    item.style.boxShadow = '0 0 0 2px rgba(40, 167, 69, 0.2)';
  });
}

function cancelNewBuild() {
  console.log('🚫 Canceling new build (keeping build in database)');
  
  if (!confirm('Bạn có chắc muốn hủy? Cấu hình sẽ được giữ lại để bạn có thể chỉnh sửa sau.')) {
    return;
  }
  
  // Clear state without deleting build
  sessionStorage.removeItem('current_build_id');
  sessionStorage.removeItem('build_creation_mode');
  location.reload();
}

async function finishBuild() {
  console.log('✅ Starting finishBuild()');
  console.log('   currentBuildId:', currentBuildId);
  
  if (!currentBuildId) {
    alert('⚠️ Không tìm thấy cấu hình!');
    return;
  }
  
  try {
    // Step 1: Get build items from database
    const apiUrl = `../api/get_build_items.php?build_id=${currentBuildId}`;
    console.log('📡 Fetching:', apiUrl);
    
    const buildRes = await fetch(apiUrl);
    const buildData = await buildRes.json();
    
    console.log('📨 Response from get_build_items.php:', buildData);
    
    // Step 2: Validate response
    if (!buildData.success) {
      console.error('❌ API error:', buildData.error);
      alert('❌ Lỗi: ' + (buildData.error || 'Không thể lấy danh sách sản phẩm'));
      return;
    }
    
    console.log('✅ API success');
    console.log('   Items count:', buildData.items ? buildData.items.length : 0);
    console.log('   Items:', buildData.items);
    
    // Step 3: Check if has items
    if (!buildData.items || buildData.items.length === 0) {
      console.warn('⚠️ No items in build');
      alert('⚠️ Bạn chưa chọn linh kiện nào! Vui lòng chọn ít nhất 1 sản phẩm.');
      return;
    }
    
    // Step 4: Extract product IDs
    const productIds = buildData.items.map(item => item.product_id);
    console.log('📦 Product IDs:', productIds);
    
    // Step 5: Prompt for name
    const name = prompt('Đặt tên cho cấu hình:', 'Cấu hình của tôi');
    if (!name || name.trim() === '') {
      console.log('❌ User cancelled or empty name');
      alert('⚠️ Bạn cần đặt tên cho cấu hình!');
      return;
    }
    
    console.log('📝 Build name:', name);
    
    // Step 6: Update build
    const updatePayload = {
      build_id: currentBuildId,
      name: name.trim(),
      parts: productIds
    };
    
    console.log('📤 Sending to update_build.php:', updatePayload);
    
    const res = await fetch('../api/update_build.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(updatePayload)
    });
    
    const data = await res.json();
    console.log('📨 Response from update_build.php:', data);
    
    if (data.success) {
      console.log('✅ Build saved successfully!');
      alert('✅ Đã lưu cấu hình: ' + name);
      
      // Clear state
      sessionStorage.removeItem('current_build_id');
      sessionStorage.removeItem('build_creation_mode');
      
      // Redirect to manage page
      console.log('🔀 Redirecting to build_manage.php?id=' + currentBuildId);
      window.location.href = 'build_manage.php?id=' + currentBuildId;
    } else {
      console.error('❌ Update failed:', data.error);
      alert('❌ ' + (data.error || 'Không thể lưu'));
    }
  } catch (e) {
    console.error('❌ Exception in finishBuild:', e);
    alert('❌ Lỗi kết nối: ' + e.message);
  }
}

function goToProductsPage(categoryId, categoryName) {
  console.log('➡️ Going to products page:', {categoryId, categoryName, currentBuildId});
  
  // Store category name for banner
  sessionStorage.setItem('adding_category', categoryName);
  sessionStorage.setItem('adding_build_id', currentBuildId);
  
  // Redirect to products with ADD mode
  window.location.href = `products.php?category_id=${categoryId}&build_id=${currentBuildId}&mode=add`;
}

// ===== BUILD MANAGEMENT =====
async function addBuildToCart(id) {
  try {
    const res = await fetch("../api/add_build_to_cart.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ build_id: id }),
      credentials: "include"
    });
    const data = await res.json();
    
    if (data.success) {
      playTingSound();
      showCartPopup();
      refreshCartCount();
      shakeCartIcon();
    } else {
      alert("❌ " + (data.error || "Không thể thêm vào giỏ hàng"));
    }
  } catch(e) {
    console.error(e);
    alert("❌ Lỗi máy chủ!");
  }
}

async function deleteBuild(id) {
  if (!confirm("Bạn có chắc muốn xóa cấu hình này không?")) return;
  
  try {
    const res = await fetch("../api/delete_build.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ build_id: id })
    });
    const data = await res.json();
    
    if (data.success) {
      alert("✅ Đã xóa cấu hình!");
      location.reload();
    } else {
      alert("❌ " + (data.error || "Không thể xóa"));
    }
  } catch(e) {
    alert("❌ Lỗi kết nối máy chủ!");
  }
}

// ===== UI HELPERS =====
function refreshCartCount() {
  fetch("../api/cart_api.php", { credentials: "include" })
  .then(r => r.json())
  .then(d => {
    if (d.ok) {
      const el = document.querySelector(".cart-count");
      if (d.cart_count > 0) {
        if (el) el.innerText = d.cart_count;
        else {
          const link = document.querySelector(".cart-link");
          const span = document.createElement('span');
          span.className = 'cart-count';
          span.textContent = d.cart_count;
          link.appendChild(span);
        }
      } else if (el) el.remove();
    }
  });
}

function shakeCartIcon() {
  const cartIcon = document.querySelector(".fa-cart-shopping");
  if (cartIcon) {
    cartIcon.classList.add("cart-shake");
    setTimeout(() => cartIcon.classList.remove("cart-shake"), 700);
  }
}

function showCartPopup() {
  const popup = document.getElementById("cart-popup");
  popup.classList.add("show");
  setTimeout(() => popup.classList.remove("show"), 3000);
}

function playTingSound() {
  const sound = document.getElementById("tingSound");
  if (sound) sound.play().catch(()=>{});
}
</script>
</body>
</html>