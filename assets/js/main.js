const API_URL = "http://localhost:9000/qlmt/api/products.php";

async function loadProducts() {
  const res = await fetch(API_URL, { credentials: 'include' });
  const data = await res.json();

  const list = document.getElementById("product-list");
  list.innerHTML = "";

  data.forEach(p => {
    const div = document.createElement("div");
    div.className = "product-card";
    div.innerHTML = `
      <h3>${p.name}</h3>
      <p>💰 ${p.price} VND</p>
      <p>📦 Còn lại: ${p.stock}</p>
      <form class="add-to-cart-form" data-product-id="${p.product_id}">
        <input type="hidden" name="quantity" value="1">
        <button type="button" class="add-to-cart-btn">🛒 Thêm vào giỏ</button>
      </form>
    `;
    list.appendChild(div);
  });

  bindAddToCart();
}

async function deleteProduct(id) {
  if (!confirm("Bạn có chắc muốn xóa sản phẩm này?")) return;
  await fetch(`${API_URL}?id=${id}`, { method: "DELETE", credentials: 'include' });
  loadProducts();
}

document.getElementById("reload-btn")?.addEventListener("click", loadProducts);

// ========================
// Giỏ hàng AJAX
// ========================
function bindAddToCart() {
  document.querySelectorAll('.add-to-cart-form').forEach(f => {
    const btn = f.querySelector('.add-to-cart-btn');
    btn.addEventListener('click', async () => {
      const pid = f.getAttribute('data-product-id');
      const qty = f.querySelector('input[name="quantity"]')?.value || 1;
      const params = new URLSearchParams();
      params.append('action', 'add');
      params.append('id', pid);
      params.append('ajax', '1');

      try {
        const CART_API = "http://localhost:9000/qlmt/api/cart_api.php";

        const res = await fetch(CART_API + '?' + params.toString(), {
          method: 'GET',
          credentials: 'include'
});

        const data = await res.json();
        if (data.ok) {
          const badge = document.getElementById('cart-badge') || document.querySelector('.cart-count');
          if (badge) badge.textContent = data.cart_count;
          alert('✅ Đã thêm vào giỏ hàng!');
        } else {
          alert(data.msg || 'Thêm thất bại!');
        }
      } catch (err) {
        console.error(err);
        alert('Lỗi kết nối!');
      }
    });
  });
}

loadProducts();
