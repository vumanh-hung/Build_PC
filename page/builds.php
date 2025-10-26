<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

// ✅ Lấy danh mục build
$categories = $pdo->query("
    SELECT category_id, name 
    FROM categories 
    WHERE category_id IN (1,2,3,4,5,21,23)
    ORDER BY category_id
")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Lấy danh sách cấu hình của user hiện tại
$user_id = $_SESSION['user']['user_id'] ?? 0;
$builds = [];
if ($user_id) {
    $stmt = $pdo->prepare("
        SELECT build_id, name, total_price, created_at 
        FROM builds 
        WHERE user_id = ?
        ORDER BY build_id DESC
    ");
    $stmt->execute([$user_id]);
    $builds = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Xây dựng cấu hình - BuildPC.vn</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background: #f5f7fa;
  margin: 0;
}
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
}
.item:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 20px rgba(26,115,232,.25);
}
.item h3 {
  margin-bottom: 10px;
  color: #1a73e8;
}
.item button {
  background: #1a73e8;
  color: #fff;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
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
.btn-save:hover { opacity: .9; transform: scale(1.05); }

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

/* 💫 Hiệu ứng rung icon giỏ hàng */
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

/* 🪄 Hiệu ứng popup "đã thêm vào giỏ hàng" */
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
.cart-popup.show {
  opacity: 1;
  transform: translateY(0);
}
</style>
</head>
<body>
<div class="container">
  <h2>🛠️ Xây dựng cấu hình của bạn</h2>
  <p>Nhấn vào từng ô linh kiện để chọn sản phẩm chi tiết.</p>

  <!-- Khu chọn linh kiện -->
  <div class="grid">
    <?php foreach ($categories as $cat): ?>
      <div class="item" onclick="window.location.href='component_select.php?category_id=<?= $cat['category_id'] ?>'">
        <h3><?= htmlspecialchars($cat['name']) ?></h3>
        <img src="../assets/img/<?= strtolower($cat['name']) ?>.png"
             onerror="this.src='../uploads/img/pc-part.png'"
             style="width:100px;height:100px;object-fit:contain;">
        <p>Chọn <?= htmlspecialchars($cat['name']) ?></p>
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
          <h3><?= htmlspecialchars($b['name']) ?></h3>
          <p><strong><?= number_format($b['total_price'],0,',','.') ?> ₫</strong></p>
          <p><small>Ngày tạo: <?= htmlspecialchars($b['created_at']) ?></small></p>
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

<!-- 🔔 Popup thông báo -->
<div id="cart-popup" class="cart-popup">🛒 Đã thêm vào giỏ hàng!</div>

<!-- 🔊 Âm thanh ting -->
<audio id="tingSound" preload="auto">
  <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
<script>
// 🎵 Cho phép phát âm thanh ngay từ click đầu tiên (fix autoplay policy)
document.addEventListener("click", () => {
  const sound = document.getElementById("tingSound");
  if (sound && sound.paused) {
    sound.play().then(() => {
      sound.pause();
      sound.currentTime = 0;
    }).catch(()=>{});
  }
}, { once: true });
</script>
<script>
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

// ✅ Cập nhật số lượng giỏ hàng
function refreshCartCount(){
  fetch("../api/cart_api.php", { credentials: "include" })
  .then(r => r.json())
  .then(d => {
    if(d.ok){
      const el = document.querySelector(".cart-count");
      if(el) el.innerText = d.cart_count;
    }
  });
}

// 💫 Rung icon giỏ hàng
function shakeCartIcon(){
  const cartIcon = document.querySelector(".fa-cart-shopping") || document.querySelector(".cart-link i");
  if(cartIcon){
    cartIcon.classList.add("cart-shake");
    setTimeout(() => cartIcon.classList.remove("cart-shake"), 700);
  }
}

// 🪄 Popup thông báo
function showCartPopup(){
  const popup = document.getElementById("cart-popup");
  popup.classList.add("show");
  setTimeout(() => popup.classList.remove("show"), 3000);
}

// 🔊 Phát âm thanh ting
function playTingSound(){
  const sound = document.getElementById("tingSound");
  if(sound) sound.play().catch(()=>{});
}
</script>
</body>
</html>
