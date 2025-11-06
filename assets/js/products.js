// ===== GLOBAL VARIABLES =====
const API_URL = '../api/cart_api.php';
let CONFIG = {};

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
  // Get config from window object
  CONFIG = window.PRODUCTS_CONFIG || {};
  
  console.log('🔧 Products page loaded');
  console.log('Config:', CONFIG);
  
  // Initialize all features
  initAudio();
  initBuildMode();
  initCartButtons();
  initQuantityInputs();
  initReviewModal();
  
  console.log('✅ Initialization complete');
});

// ===== AUDIO FUNCTIONS =====
function initAudio() {
  // Enable audio on first user interaction
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

function playTingSound() {
  const sound = document.getElementById("tingSound");
  if (sound) {
    sound.play().catch(() => {});
  }
}

// ===== UTILITY FUNCTIONS =====
function showLoading(text = 'Đang xử lý...') {
  const loading = document.getElementById('loading');
  const loadingText = document.getElementById('loading-text');
  if (loading && loadingText) {
    loadingText.textContent = text;
    loading.classList.add('active');
  }
}

function hideLoading() {
  const loading = document.getElementById('loading');
  if (loading) {
    loading.classList.remove('active');
  }
}

function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  if (toast) {
    toast.textContent = message;
    toast.className = 'toast ' + type + ' show';
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  }
}

function shakeCartIcon() {
  const cartIcon = document.querySelector(".fa-cart-shopping");
  if (cartIcon) {
    cartIcon.classList.add("cart-shake");
    setTimeout(() => cartIcon.classList.remove("cart-shake"), 700);
  }
}

function showCartPopup(text = 'Đã thêm vào giỏ hàng!') {
  const popup = document.getElementById("cart-popup");
  const popupText = document.getElementById('popup-text');
  if (popup && popupText) {
    popupText.textContent = text;
    popup.classList.add("show");
    setTimeout(() => popup.classList.remove("show"), 3000);
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

// ===== CART API FUNCTIONS =====
async function refreshCartCount() {
  try {
    const response = await fetch(API_URL, {
      method: 'GET',
      credentials: 'include'
    });
    
    if (!response.ok) throw new Error('Network response was not ok');
    
    const data = await response.json();
    
    if (data.ok && data.cart_count !== undefined) {
      updateCartBadge(data.cart_count);
    }
  } catch (error) {
    console.error('Error refreshing cart count:', error);
  }
}

async function addToCart(productId, quantity = 1, productName = '') {
  if (quantity < 1 || quantity > 99) {
    showToast('❌ Số lượng không hợp lệ (1-99)', 'error');
    return;
  }

  const formData = new FormData();
  formData.append('action', 'add');
  formData.append('product_id', productId);
  formData.append('quantity', quantity);
  formData.append('csrf', CONFIG.CSRF_TOKEN);

  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });

    if (!response.ok) throw new Error('Network response was not ok');

    const data = await response.json();

    if (data.ok || data.success) {
      playTingSound();
      showCartPopup();
      shakeCartIcon();
      await refreshCartCount();
      console.log(`✅ Added ${quantity}x ${productName} to cart`);
    } else {
      showToast(`❌ ${data.message || 'Không thể thêm vào giỏ hàng'}`, 'error');
    }
  } catch (error) {
    console.error('Error adding to cart:', error);
    showToast('❌ Lỗi kết nối. Vui lòng thử lại', 'error');
  }
}

// ===== BUILD MODE FUNCTIONS =====
function initBuildMode() {
  if (!CONFIG.IS_BUILD_MODE) return;
  
  // Update banner title if category is stored
  const categoryName = CONFIG.BUILD_MODE === 'replace' 
    ? sessionStorage.getItem('replacing_category') 
    : sessionStorage.getItem('adding_category');
  
  if (categoryName) {
    const title = document.getElementById('banner-title');
    if (title) {
      if (CONFIG.BUILD_MODE === 'replace') {
        title.innerHTML = `🔄 Đang thay thế <strong>${categoryName}</strong>`;
      } else {
        title.innerHTML = `➕ Đang thêm <strong>${categoryName}</strong>`;
      }
    }
  }
}

function cancelBuildMode() {
  // Clear session storage
  sessionStorage.removeItem('build_mode');
  sessionStorage.removeItem('replacing_item_id');
  sessionStorage.removeItem('replacing_build_id');
  sessionStorage.removeItem('replacing_category');
  sessionStorage.removeItem('adding_build_id');
  sessionStorage.removeItem('adding_category');
  
  // Redirect back
  if (CONFIG.BUILD_ID) {
    window.location.href = `build_manage.php?id=${CONFIG.BUILD_ID}`;
  } else {
    window.location.href = 'products.php';
  }
}

async function selectProductForBuild(productId) {
  console.log('🎯 selectProductForBuild called');
  console.log('   Product ID:', productId);
  console.log('   Build ID:', CONFIG.BUILD_ID);
  console.log('   Build Mode:', CONFIG.BUILD_MODE);
  console.log('   Item ID:', CONFIG.ITEM_ID);
  
  if (!CONFIG.BUILD_ID || !productId) {
    console.error('❌ Missing data:', {BUILD_ID: CONFIG.BUILD_ID, productId});
    showToast('❌ Thiếu thông tin!', 'error');
    return;
  }

  showLoading('Đang xử lý...');

  try {
    let apiUrl = '';
    let bodyData = {};

    if (CONFIG.BUILD_MODE === 'replace' && CONFIG.ITEM_ID && CONFIG.ITEM_ID !== '' && CONFIG.ITEM_ID !== '0' && CONFIG.ITEM_ID !== 0) {
      apiUrl = '../api/replace_build_item.php';
      bodyData = {
        build_id: parseInt(CONFIG.BUILD_ID),
        item_id: parseInt(CONFIG.ITEM_ID),
        new_product_id: parseInt(productId)
      };
      console.log('🔄 REPLACE mode - API call:', apiUrl);
      console.log('   Body:', bodyData);
    } else if (CONFIG.BUILD_MODE === 'add') {
      apiUrl = '../api/add_product_to_build.php';
      bodyData = {
        build_id: parseInt(CONFIG.BUILD_ID),
        product_id: parseInt(productId)
      };
      console.log('➕ ADD mode - API call:', apiUrl);
      console.log('   Body:', bodyData);
    } else {
      console.error('❌ Invalid mode:', CONFIG.BUILD_MODE);
      hideLoading();
      showToast('❌ Chế độ không hợp lệ!', 'error');
      return;
    }

    console.log('📡 Sending request...');
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(bodyData)
    });

    console.log('📨 Response status:', response.status);
    const data = await response.json();
    console.log('📨 Response data:', data);

    if (data.success) {
      console.log('✅ Success! Redirecting...');
      
      // Clear sessionStorage
      sessionStorage.removeItem('build_mode');
      sessionStorage.removeItem('replacing_item_id');
      sessionStorage.removeItem('replacing_build_id');
      sessionStorage.removeItem('replacing_category');
      sessionStorage.removeItem('adding_build_id');
      sessionStorage.removeItem('adding_category');

      hideLoading();
      
      // Redirect with success param
      const successParam = CONFIG.BUILD_MODE === 'replace' ? 'replaced' : 'added';
      const redirectUrl = `build_manage.php?id=${CONFIG.BUILD_ID}&success=${successParam}`;
      console.log('🔀 Redirecting to:', redirectUrl);
      
      window.location.href = redirectUrl;
    } else {
      console.error('❌ API returned error:', data.error);
      hideLoading();
      showToast(`❌ ${data.error || 'Không thể xử lý'}`, 'error');
    }
  } catch (error) {
    console.error('❌ Exception:', error);
    console.error('Stack:', error.stack);
    hideLoading();
    showToast('❌ Lỗi kết nối server!', 'error');
  }
}

// ===== CART BUTTON INITIALIZATION =====
function initCartButtons() {
  document.querySelectorAll('.add-to-cart-btn').forEach(button => {
    // Clone to remove all old event listeners
    const newButton = button.cloneNode(true);
    button.parentNode.replaceChild(newButton, button);
    
    // Add new event listener
    newButton.addEventListener('click', async function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const productId = this.getAttribute('data-product-id');
      const productName = this.getAttribute('data-product-name');

      console.log('Button clicked:', productId, 'Build mode:', CONFIG.IS_BUILD_MODE);

      if (CONFIG.IS_BUILD_MODE) {
        // BUILD MODE: Add/Replace in build
        console.log('🔧 Build mode action');
        await selectProductForBuild(productId);
      } else {
        // NORMAL MODE: Add to cart
        console.log('🛒 Cart mode action');
        const qtyInput = this.parentElement.querySelector('.qty-input');
        const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

        this.disabled = true;
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang thêm...';

        await addToCart(productId, quantity, productName);

        this.disabled = false;
        this.innerHTML = originalText;
      }
    });
  });
  
  console.log('✅ Initialized', document.querySelectorAll('.add-to-cart-btn').length, 'cart buttons');
}

// ===== QUANTITY INPUT INITIALIZATION =====
function initQuantityInputs() {
  document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('change', function() {
      let value = parseInt(this.value) || 1;
      if (value < 1) value = 1;
      if (value > 99) value = 99;
      this.value = value;
    });

    input.addEventListener('keydown', function(e) {
      if (e.key === '-' || e.key === 'e' || e.key === 'E' || e.key === '+') {
        e.preventDefault();
      }
    });
  });
  
  console.log('✅ Initialized', document.querySelectorAll('.qty-input').length, 'quantity inputs');
}

// ===== REVIEW MODAL FUNCTIONS =====
function initReviewModal() {
  // Initialize rating buttons
  document.querySelectorAll('.rating-btn').forEach((btn, i) => {
    if (i + 1 <= 5) btn.classList.add('active');
  });

  // Auto close modal after success
  if (CONFIG.REVIEW_SUCCESS) {
    setTimeout(() => {
      closeReviewModal();
      location.reload();
    }, 2000);
  }
  
  console.log('✅ Initialized review modal');
}

function openReviewModal() {
  if (!CONFIG.IS_LOGGED_IN) {
    window.location.href = 'login.php';
    return;
  }
  const modal = document.getElementById('reviewModal');
  if (modal) {
    modal.classList.add('active');
  }
}

function closeReviewModal() {
  const modal = document.getElementById('reviewModal');
  if (modal) {
    modal.classList.remove('active');
  }
}

function setRating(rating, e) {
  e.preventDefault();
  const ratingValue = document.getElementById('ratingValue');
  if (ratingValue) {
    ratingValue.value = rating;
  }
  document.querySelectorAll('.rating-btn').forEach((btn, i) => {
    btn.classList.toggle('active', i + 1 <= rating);
  });
}

function updateCount(el, id) {
  const counter = document.getElementById(id);
  if (counter) {
    counter.textContent = el.value.length;
  }
}

function previewReviewImages(files) {
  const container = document.getElementById('previewImages');
  if (!container) return;
  
  container.innerHTML = '';
  Array.from(files).slice(0, 5).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const div = document.createElement('div');
      div.className = 'preview-img';
      div.innerHTML = `<img src="${e.target.result}" alt="">`;
      container.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

function handleImageDrop(e) {
  e.preventDefault();
  e.currentTarget.style.background = 'white';
  const input = document.getElementById('reviewImageInput');
  if (input) {
    previewReviewImages(e.dataTransfer.files);
    input.files = e.dataTransfer.files;
  }
}

// ===== EXPOSE FUNCTIONS TO GLOBAL SCOPE =====
window.cancelBuildMode = cancelBuildMode;
window.openReviewModal = openReviewModal;
window.closeReviewModal = closeReviewModal;
window.setRating = setRating;
window.updateCount = updateCount;
window.previewReviewImages = previewReviewImages;
window.handleImageDrop = handleImageDrop;