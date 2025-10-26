<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
$pdo = getPDO();

$build_id = $_GET['id'] ?? 0;
if (!$build_id) die("Thiếu ID cấu hình");

// Lấy chi tiết cấu hình qua API
$apiUrl = SITE_URL . '/api/build_detail.php?id=' . urlencode($build_id);
$data = json_decode(@file_get_contents($apiUrl), true);
$build = $data['build'] ?? null;
$items = $data['items'] ?? [];

if (!$build) die("Không tìm thấy cấu hình này");

// Lấy tất cả sản phẩm để dùng khi sửa
$products = $pdo->query("SELECT product_id, name, price, category_id FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản lý cấu hình - <?= htmlspecialchars($build['name']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body{font-family:sans-serif;background:#f5f7fa;margin:0;}
.container{max-width:1000px;margin:40px auto;padding:30px;background:white;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);}
h1{color:#1a73e8;margin-bottom:10px;}
p{margin:4px 0;}
table{width:100%;border-collapse:collapse;margin-top:15px;}
th,td{padding:10px;border-bottom:1px solid #ddd;text-align:center;}
th{background:#1a73e8;color:white;}
img{width:70px;height:70px;object-fit:cover;border-radius:8px;}
.btn{display:inline-block;padding:10px 18px;border:none;border-radius:6px;cursor:pointer;font-weight:600;}
.btn-edit{background:#ff9800;color:white;}
.btn-save{background:#1a73e8;color:white;}
.btn-back{background:#999;color:white;text-decoration:none;padding:10px 18px;border-radius:6px;}
.total{margin-top:20px;text-align:right;font-weight:bold;font-size:18px;color:#ff9800;}
select,input[type=text]{padding:8px;width:100%;border:1px solid #ccc;border-radius:6px;}
.hidden{display:none;}
</style>
</head>
<body>
<div class="container">
  <h1>🧩 Cấu hình: <span id="build-name-display"><?= htmlspecialchars($build['name']) ?></span></h1>
  <input type="text" id="build-name-input" class="hidden" value="<?= htmlspecialchars($build['name']) ?>">

  <p><strong>Người tạo:</strong> <?= htmlspecialchars($build['full_name'] ?? 'Không rõ') ?></p>
  <p><strong>Ngày tạo:</strong> <?= htmlspecialchars($build['created_at']) ?></p>

  <div style="margin:15px 0;">
    <button class="btn btn-edit" id="edit-btn"><i class="fa fa-pen"></i> Chỉnh sửa</button>
    <button class="btn btn-save hidden" id="save-btn"><i class="fa fa-save"></i> Lưu thay đổi</button>
    <a href="builds.php" class="btn-back"><i class="fa fa-arrow-left"></i> Quay lại</a>
  </div>

  <table id="build-table">
    <thead>
      <tr><th>Ảnh</th><th>Danh mục</th><th>Sản phẩm</th><th>Giá</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <?php
        $image = $pdo->prepare("SELECT main_image FROM products WHERE name = ?");
        $image->execute([$it['product_name']]);
        $imageUrl = $image->fetchColumn() ? SITE_URL.'/uploads/'.$image->fetchColumn() : SITE_URL.'/uploads/no-image.png';
      ?>
      <tr data-category="<?= $it['category_name'] ?>" data-productid="<?= $it['product_id'] ?>">
        <td><img src="<?= htmlspecialchars($imageUrl) ?>"></td>
        <td><?= htmlspecialchars($it['category_name']) ?></td>
        <td class="view-mode"><?= htmlspecialchars($it['product_name']) ?></td>
        <td class="price"><?= number_format($it['price'], 0, ',', '.') ?> ₫</td>
        <td class="edit-mode hidden">
          <select class="product-select">
            <option value="">-- Chọn sản phẩm --</option>
            <?php foreach ($products as $p): 
              if ($p['category_id'] == $it['category_id']): ?>
              <option value="<?= $p['product_id'] ?>"
                <?= $p['name'] === $it['product_name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?> - <?= number_format($p['price'],0,',','.') ?> ₫
              </option>
            <?php endif; endforeach; ?>
          </select>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p class="total">Tổng cộng: <span id="total-price"><?= number_format($build['total_price'], 0, ',', '.') ?> ₫</span></p>
</div>

<script>
const editBtn=document.getElementById("edit-btn");
const saveBtn=document.getElementById("save-btn");
const buildNameDisplay=document.getElementById("build-name-display");
const buildNameInput=document.getElementById("build-name-input");
const container=document.querySelector(".container");

editBtn.addEventListener("click",()=>{
  document.querySelectorAll(".edit-mode").forEach(e=>e.classList.remove("hidden"));
  document.querySelectorAll(".view-mode").forEach(e=>e.classList.add("hidden"));
  buildNameDisplay.classList.add("hidden");
  buildNameInput.classList.remove("hidden");
  editBtn.classList.add("hidden");
  saveBtn.classList.remove("hidden");
});

saveBtn.addEventListener("click",async()=>{
  const buildId=<?= (int)$build['build_id'] ?>;
  const name=buildNameInput.value.trim();
  const parts=[];
  document.querySelectorAll(".product-select").forEach(sel=>{
    if(sel.value) parts.push(parseInt(sel.value));
  });
  if(parts.length===0){alert("⚠️ Vui lòng chọn ít nhất 1 linh kiện!");return;}
  try{
    const res=await fetch("../api/update_build.php",{
      method:"POST",
      headers:{"Content-Type":"application/json"},
      body:JSON.stringify({build_id:buildId,name,parts})
    });
    const data=await res.json();
    if(data.success){
      alert(data.message);

      // ✅ Tạo nút thêm giỏ hàng sau khi lưu thành công
      if(!document.getElementById("add-to-cart-btn")){
        const addBtn=document.createElement("button");
        addBtn.id="add-to-cart-btn";
        addBtn.className="btn";
        addBtn.style="background:#28a745;color:white;margin-top:15px;";
        addBtn.innerHTML='<i class="fa fa-cart-plus"></i> Thêm vào giỏ hàng';
        container.appendChild(addBtn);

        addBtn.addEventListener("click",()=>addBuildToCart(buildId));
      }
    }else{
      alert("❌ Lỗi: "+(data.error||"Không xác định"));
    }
  }catch(e){
    alert("Lỗi kết nối server!");
  }
});

async function addBuildToCart(buildId){
  try{
    const res=await fetch("../api/add_build_to_cart.php",{
      method:"POST",
      headers:{"Content-Type":"application/json"},
      body:JSON.stringify({build_id:buildId})
    });
    const data=await res.json();
    if(data.success){
      alert("🛒 Đã thêm cấu hình vào giỏ hàng!");
      window.location.href="../cart.php";
    }else{
      alert("⚠️ "+(data.error||"Không thể thêm vào giỏ hàng"));
    }
  }catch(e){
    alert("❌ Lỗi kết nối máy chủ khi thêm giỏ hàng!");
  }
}
</script>
</body>
</html>
