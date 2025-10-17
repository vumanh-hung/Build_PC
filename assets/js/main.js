const API_URL = "http://localhost:9000/buildpc_purephp/api/products.php";

async function loadProducts() {
  const res = await fetch(API_URL);
  const data = await res.json();

  const list = document.getElementById("product-list");
  list.innerHTML = "";

  data.forEach(p => {
    const div = document.createElement("div");
    div.className = "product-card";
    div.innerHTML = `
      <h3>${p.TenSP}</h3>
      <p>💰 ${p.GiaBan} VND</p>
      <p>📦 Còn lại: ${p.SoLuong}</p>
      <button onclick="deleteProduct(${p.MaSP})">Xóa</button>
    `;
    list.appendChild(div);
  });
}

async function deleteProduct(id) {
  if (!confirm("Bạn có chắc muốn xóa sản phẩm này?")) return;
  await fetch(`${API_URL}?id=${id}`, { method: "DELETE" });
  loadProducts();
}

document.getElementById("reload-btn").addEventListener("click", loadProducts);

// Load lần đầu
loadProducts();
let slides = document.querySelectorAll(".banner .slide");
let dots = document.querySelectorAll(".banner-dots .dot");
let index = 0;

function showSlide(i) {
  slides.forEach(slide => slide.classList.remove("active"));
  dots.forEach(dot => dot.classList.remove("active"));
  slides[i].classList.add("active");
  dots[i].classList.add("active");
}

setInterval(() => {
  index = (index + 1) % slides.length;
  showSlide(index);
}, 4000);

dots.forEach((dot, i) => {
  dot.addEventListener("click", () => {
    index = i;
    showSlide(i);
  });
});
// MAIN.JS
document.getElementById('reload-btn')?.addEventListener('click', () => {
  location.reload();
});

document.getElementById('search-btn')?.addEventListener('click', () => {
  const q = document.getElementById('search-input').value;
  alert(`Tìm kiếm: ${q}`);
});
