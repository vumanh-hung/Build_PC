<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// ✅ Lấy danh mục build
$categories = getBuildCategories();

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
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}
.item {
  background: white;
  border-radius: 12px;
  padding: 20px;
  text-align: center;
  box-shadow: 0 4px 10px rgba(0,0,0,.08);
  transition: .3s;
  cursor: pointer;
  position: relative;
  overflow: hidden;
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
  transform: translateY(-5px);
  box-shadow: 0 6px 20px rgba(26,115,232,.25);
}
.item h3 {
  margin-bottom: 10px;
  color: #1a73e8;
  font-size: 18px;
}
.item img {
  width: 100px;
  height: 100px;
  object-fit: contain;
  margin: 15px 0;
  transition: transform 0.3s ease;
}
.item:hover img {
  transform: scale(1.1);
}
.item p {
  color: #666;
  font-size: 14px;
  font-weight: 500;
}
.total {
  margin-top: 20px;
  background: #1a73e8;
  color: #fff;
  padding: 16px;
  border-radius: 8px;
  text-align: center;
  font-weight: 700;
  font-size: 18px;
}
.btn-save {
  margin: 20px auto;
  display: block;
  background: #ff9800;
  color: white;
  border: none;
  padding: 12px 28px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: .3s;
}
.btn-save:hover {opacity: .9; transform: scale(1.05);}
.section-title {
  margin-top: 60px;
  color: #1a73e8;
  text-align: center;
}
.saved-builds {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  justify-content: center;
  margin-top: 20px;
}
.build-card {
  background: white;
  width: 260px;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 3px 10px rgba(0,0,0,.1);
  text-align: center;
  transition: .3s;
}
.build-card:hover { transform: translateY(-5px); }
.build-card h3 { color: #1a73e8; margin-bottom: 8px; }
.build-card p { margin: 5px 0; color: #444; }
.btn-group {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 10px;
  flex-wrap: wrap;
}
.btn {
  padding: 8px 12px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 14px;
}
.btn-view { background: #1a73e8; color: white; }
.btn-cart { background: #28a745; color: white; border: none; cursor: pointer; }
.btn-del { background: #dc3545; color: white; border: none; cursor: pointer; }

/* 💫 Rung icon giỏ hàng */
@keyframes cartShake {
  0% { transform: rotate(0deg); }
  25% { transform: rotate(-15deg); }
  50% { transform: rotate(15deg); }
  75% { transform: rotate(-10deg); }
  100% { transform: rotate(0deg); }
}
.cart-shake { animation: cartShake 0.6s ease; }

/* 🪄 Popup "đã thêm vào giỏ hàng" */
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
}
.cart-popup.show {opacity:1;transform:translateY(0);}
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
  <div class="grid">
    <?php foreach ($categories as $cat): ?>
      <div class="item" onclick="window.location.href='products.php?category_id=<?= $cat['category_id'] ?>'">
        <h3><?= escape($cat['name']) ?></h3>
        <img src="../assets/img/<?= strtolower($cat['name']) ?>.png"
             onerror="this.src='../uploads/img/pc-part.png'">
        <p>Chọn <?= escape($cat['name']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="total">Tổng giá tạm tính: <span id="total-price">0 ₫</span></div>
  <button class="btn-save" onclick="saveBuild()">💾 Lưu cấu hình</button>

  <h2 class="section-title">🧩 Cấu hình của tôi</h2>
  <?php if (!$user_id): ?>
    <p style="text-align:center;color:#777;">⚠️ Vui lòng đăng nhập để xem các cấu hình đã lưu.</p>
  <?php elseif (empty($builds)): ?>
    <p style="text-align:center;color:#777;">📭 Bạn chưa có cấu hình nào được lưu.</p>
  <?php else: ?>
    <div class="saved-builds">
      <?php foreach ($builds as $b): ?>
        <div class="build-card">
          <h3><?= escape($b['name']) ?></h3>
          <p><strong><?= formatPriceVND($b['total_price']) ?></strong></p>
          <p><small>Ngày tạo: <?= formatDate($b['created_at']) ?></small></p>
          <div class="btn-group">
            <a href="build_manage.php?id=<?= $b['build_id'] ?>" class="btn btn-view"><i class="fa fa-edit"></i> Xem/Sửa</a>
            <button class="btn btn-cart" onclick="addBuildToCart(<?= $b['build_id'] ?>)"><i class="fa fa-cart-plus"></i></button>
            <button class="btn btn-del" onclick="deleteBuild(<?= $b['build_id'] ?>)"><i class="fa fa-trash"></i></button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div id="cart-popup" class="cart-popup">🛒 Đã thêm vào giỏ hàng!</div>

<audio id="tingSound" preload="auto">
  <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<script>
document.addEventListener("click", () => {
  const sound = document.getElementById("tingSound");
  if (sound && sound.paused) {
    sound.play().then(() => { sound.pause(); sound.currentTime = 0; }).catch(()=>{});
  }
}, { once: true });

let selectedParts = JSON.parse(sessionStorage.getItem("selectedParts") || "{}");

function updateTotal(){
  let total = 0;
  Object.values(selectedParts).forEach(p => total += Number(p.price || 0));
  document.getElementById("total-price").innerText = total.toLocaleString() + " ₫";
}
updateTotal();

function saveBuild(){
  if(Object.keys(selectedParts).length === 0){
    alert("⚠️ Chưa chọn linh kiện nào!");
    return;
  }
  const name = prompt("Nhập tên cấu hình:", "Cấu hình của tôi");
  if(!name) return;

  fetch("../api/save_build.php",{
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ name, items: Object.values(selectedParts) })
  })
  .then(r => r.json())
  .then(d => {
    alert(d.message || "Đã lưu cấu hình!");
    if(d.status === "success"){
      sessionStorage.removeItem("selectedParts");
      window.location.href = "builds.php";
    }
  })
  .catch(() => alert("Lỗi kết nối máy chủ!"));
}

async function addBuildToCart(id){
  try{
    const res = await fetch("../api/add_build_to_cart.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ build_id: id }),
      credentials: "include"
    });
    const data = await res.json();
    if(data.success){
      playTingSound();
      showCartPopup();
      refreshCartCount();
      shakeCartIcon();
    } else {
      alert("❌ " + (data.error || "Không thể thêm vào giỏ hàng"));
    }
  } catch(e){
    console.error(e);
    alert("Lỗi máy chủ!");
  }
}

async function deleteBuild(id){
  if(!confirm("Bạn có chắc muốn xóa cấu hình này không?")) return;
  try{
    const res = await fetch("../api/delete_build.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ build_id: id })
    });
    const data = await res.json();
    if(data.success){
      alert("✅ Đã xóa cấu hình!");
      location.reload();
    } else alert("❌ " + (data.error || "Không thể xóa"));
  } catch(e){
    alert("❌ Lỗi kết nối máy chủ!");
  }
}

function refreshCartCount(){
  fetch("../api/cart_api.php", { credentials: "include" })
  .then(r => r.json())
  .then(d => {
    if(d.ok){
      const el = document.querySelector(".cart-count");
      if(d.cart_count > 0){
        if(el) el.innerText = d.cart_count;
        else {
          const link = document.querySelector(".cart-link");
          const span = document.createElement('span');
          span.className = 'cart-count';
          span.textContent = d.cart_count;
          link.appendChild(span);
        }
      } else if(el) el.remove();
    }
  });
}

function shakeCartIcon(){
  const cartIcon = document.querySelector(".fa-cart-shopping") || document.querySelector(".cart-link i");
  if(cartIcon){
    cartIcon.classList.add("cart-shake");
    setTimeout(() => cartIcon.classList.remove("cart-shake"), 700);
  }
}

function showCartPopup(){
  const popup = document.getElementById("cart-popup");
  popup.classList.add("show");
  setTimeout(() => popup.classList.remove("show"), 3000);
}

function playTingSound(){
  const sound = document.getElementById("tingSound");
  if(sound) sound.play().catch(()=>{});
}
</script>
</body>
</html>