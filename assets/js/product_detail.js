// ===== CONFIGURATION =====
let CONFIG = {};

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
  CONFIG = window.PRODUCT_CONFIG || {};
  console.log('🎯 Product Detail loaded', CONFIG);

  initFlashSaleTimer();
  initAudio();
  
  console.log('✅ Product Detail initialized');
});

// ===== AUDIO =====
function initAudio() {
  document.addEventListener("click", () => {
    const sound = document.getElementById("tingSound");
    if (sound && sound.paused) {
      sound.play().then(() => {
        sound.pause();
        sound.currentTime = 0;
      }).catch(() => {});
    }
  }, { once: true });
}

function playSound() {
  const sound = document.getElementById("tingSound");
  if (sound) {
    sound.play().catch(() => {});
  }
}

// ===== IMAGE GALLERY =====
function changeMainImage(imagePath, thumbnail) {
  const mainImage = document.getElementById('mainImage');
  mainImage.src = '../' + imagePath;
  
  // Update active thumbnail
  document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
  thumbnail.classList.add('active');
}

function openImageModal(src) {
  const modal = document.getElementById('image-modal');
  const modalImg = document.getElementById('modalImage');
  modal.classList.add('show');
  modalImg.src = src;
}

function closeImageModal() {
  document.getElementById('image-modal').classList.remove('show');
}

// ===== QUANTITY CONTROLS =====
function changeQuantity(delta) {
  const input = document.getElementById('quantity');
  let value = parseInt(input.value) || 1;
  value += delta;
  
  if (value < 1) value = 1;
  if (value > CONFIG.MAX_STOCK) value = CONFIG.MAX_STOCK;
  
  input.value = value;
}

// ===== FLASH SALE TIMER =====
function initFlashSaleTimer() {
  if (!CONFIG.IS_FLASH_SALE || !CONFIG.FLASH_SALE_END) return;
  
  const endTime = new Date(CONFIG.FLASH_SALE_END).getTime();
  
  const updateTimer = () => {
    const now = new Date().getTime();
    const distance = endTime - now;
    
    if (distance < 0) {
      document.querySelector('.flash-sale-timer').innerHTML = 
        '<span style="color: red; font-weight: 700;">⚠️ Flash Sale đã kết thúc!</span>';
      return;
    }
    
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    document.getElementById('hours').textContent = String(hours).padStart(2, '0');
    document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
    document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
  };
  
  updateTimer();
  setInterval(updateTimer, 1000);
}

// ===== CART FUNCTIONS =====
async function addToCart(productId) {
  const quantity = parseInt(document.getElementById('quantity').value) || 1;
  
  if (quantity > CONFIG.MAX_STOCK) {
    showPopup('❌ Số lượng vượt quá tồn kho!', 'error');
    return;
  }
  
  const formData = new FormData();
  formData.append('action', 'add');
  formData.append('product_id', productId);
  formData.append('quantity', quantity);
  formData.append('csrf', CONFIG.CSRF_TOKEN);
  
  try {
    const response = await fetch('../api/cart_api.php', {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });
    
    if (!response.ok) throw new Error('Network error');
    
    const data = await response.json();
    
    if (data.ok || data.success) {
      playSound();
      shakeCartIcon(); // ✅ NEW: Shake cart icon
      showPopup('✅ Đã thêm vào giỏ hàng!', 'success');
      updateCartBadge(data.cart_count || 0);
      
      // Reset quantity to 1
      document.getElementById('quantity').value = 1;
    } else {
      showPopup('❌ ' + (data.message || 'Không thể thêm vào giỏ'), 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showPopup('❌ Lỗi kết nối. Vui lòng thử lại', 'error');
  }
}

async function buyNow(productId) {
  const quantity = parseInt(document.getElementById('quantity').value) || 1;
  
  if (quantity > CONFIG.MAX_STOCK) {
    showPopup('❌ Số lượng vượt quá tồn kho!', 'error');
    return;
  }
  
  // Add to cart first
  const formData = new FormData();
  formData.append('action', 'add');
  formData.append('product_id', productId);
  formData.append('quantity', quantity);
  formData.append('csrf', CONFIG.CSRF_TOKEN);
  
  try {
    const response = await fetch('../api/cart_api.php', {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });
    
    if (!response.ok) throw new Error('Network error');
    
    const data = await response.json();
    
    if (data.ok || data.success) {
      // Redirect to checkout
      window.location.href = 'checkout.php';
    } else {
      showPopup('❌ ' + (data.message || 'Không thể thực hiện'), 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showPopup('❌ Lỗi kết nối. Vui lòng thử lại', 'error');
  }
}

function updateCartBadge(count) {
  let badge = document.querySelector('.cart-count');
  const cartLink = document.querySelector('.cart-link');
  
  if (!cartLink) return;
  
  if (count > 0) {
    if (badge) {
      badge.textContent = count;
    } else {
      badge = document.createElement('span');
      badge.className = 'cart-count';
      badge.textContent = count;
      cartLink.appendChild(badge);
    }
  } else if (badge) {
    badge.remove();
  }
}

// ===== POPUP =====
function showPopup(message, type = 'success') {
  const popup = document.getElementById('cart-popup');
  const text = document.getElementById('popup-text');
  
  text.textContent = message;
  popup.style.background = type === 'success' ? '#28a745' : '#dc3545';
  popup.classList.add('show');
  
  setTimeout(() => {
    popup.classList.remove('show');
  }, 3000);
}

// ===== TABS =====
function switchTab(tabName) {
  // Hide all tabs
  document.querySelectorAll('.tab-content').forEach(tab => {
    tab.classList.remove('active');
  });
  
  // Remove active from all headers
  document.querySelectorAll('.tab-header').forEach(header => {
    header.classList.remove('active');
  });
  
  // Show selected tab
  document.getElementById(tabName + '-tab').classList.add('active');
  
  // Set active header
  event.target.classList.add('active');
}

// ===== REVIEWS =====
function openReviewModal() {
  // This would open a review modal
  // For now, redirect to reviews tab
  switchTab('reviews');
  window.scrollTo({ top: document.querySelector('.product-tabs').offsetTop - 100, behavior: 'smooth' });
}

async function markHelpful(reviewId) {
  try {
    const formData = new FormData();
    formData.append('action', 'mark_helpful');
    formData.append('review_id', reviewId);
    formData.append('csrf', CONFIG.CSRF_TOKEN);
    
    const response = await fetch('../api/reviews_api.php', {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });
    
    if (!response.ok) throw new Error('Network error');
    
    const data = await response.json();
    
    if (data.success) {
      showPopup('✅ Cảm ơn phản hồi của bạn!', 'success');
      // Update count in UI
      const button = event.target.closest('.btn-helpful');
      const countMatch = button.textContent.match(/\((\d+)\)/);
      if (countMatch) {
        const newCount = parseInt(countMatch[1]) + 1;
        button.innerHTML = `<i class="fa-regular fa-thumbs-up"></i> Hữu ích (${newCount})`;
      }
    } else {
      showPopup('❌ ' + (data.message || 'Không thể thực hiện'), 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showPopup('❌ Lỗi kết nối', 'error');
  }
}

// ===== SHAKE CART ICON =====
function shakeCartIcon() {
  const cartIcon = document.querySelector('.fa-cart-shopping') || 
                   document.querySelector('.cart-link i');
  
  if (cartIcon) {
    cartIcon.classList.add('cart-shake');
    setTimeout(() => {
      cartIcon.classList.remove('cart-shake');
    }, 700);
  }
}

// ===== EXPOSE GLOBAL FUNCTIONS =====
window.changeMainImage = changeMainImage;
window.openImageModal = openImageModal;
window.closeImageModal = closeImageModal;
window.changeQuantity = changeQuantity;
window.addToCart = addToCart;
window.buyNow = buyNow;
window.switchTab = switchTab;
window.openReviewModal = openReviewModal;
window.markHelpful = markHelpful;
window.shakeCartIcon = shakeCartIcon;