// ===== GET CONFIG FROM PHP =====
const CONFIG = window.PRODUCTS_CONFIG || {};
const {
    CSRF_TOKEN,
    IS_BUILD_MODE,
    BUILD_MODE,
    BUILD_ID,
    ITEM_ID, // ✅ Đổi từ ITEM_ID sang ITEM_ID
    IS_LOGGED_IN,
    REVIEW_SUCCESS
} = CONFIG;

// ===== CONSTANTS =====
const API_URL = '../api/cart_api.php';

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
    console.log('🔧 Products page loaded');
    console.log('Config:', CONFIG);
    
    // Initialize all features
    initAudio();
    initBuildMode();
    initSelectButtons(); // ✅ Thêm init cho select buttons
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

// ===== BUILD MODE FUNCTIONS =====
function initBuildMode() {
    if (!IS_BUILD_MODE) return;
    
    console.log('🔧 Initializing Build Mode');
    console.log('   BUILD_MODE:', BUILD_MODE);
    console.log('   BUILD_ID:', BUILD_ID);
    console.log('   ITEM_ID:', ITEM_ID);
    
    // Update banner title if category is stored
    const categoryName = BUILD_MODE === 'replace' 
        ? sessionStorage.getItem('replacing_category') 
        : sessionStorage.getItem('adding_category');
    
    if (categoryName) {
        const title = document.getElementById('banner-title');
        if (title) {
            if (BUILD_MODE === 'replace') {
                title.innerHTML = `🔄 Đang thay thế <strong>${categoryName}</strong>`;
            } else {
                title.innerHTML = `➕ Đang thêm <strong>${categoryName}</strong>`;
            }
        }
    }
}

function cancelBuildMode() {
    console.log('🚫 Canceling build mode');
    
    // Clear session storage
    sessionStorage.removeItem('build_mode');
    sessionStorage.removeItem('replacing_item_id');
    sessionStorage.removeItem('replacing_build_id');
    sessionStorage.removeItem('replacing_category');
    sessionStorage.removeItem('adding_build_id');
    sessionStorage.removeItem('adding_category');
    
    // Redirect back
    if (BUILD_ID && BUILD_ID > 0) {
        window.location.href = `build_manage.php?id=${BUILD_ID}`;
    } else {
        window.location.href = 'products.php';
    }
}

async function selectProductForBuild(productId) {
    console.log('🎯 selectProductForBuild called');
    console.log('   Product ID:', productId);
    console.log('   Build ID:', BUILD_ID);
    console.log('   Build Mode:', BUILD_MODE);
    console.log('   Build Item ID:', ITEM_ID);
    
    // ✅ Validation
    if (!BUILD_ID || BUILD_ID <= 0) {
        console.error('❌ Invalid BUILD_ID:', BUILD_ID);
        showToast('❌ Thiếu thông tin build!', 'error');
        return;
    }

    if (!productId || productId <= 0) {
        console.error('❌ Invalid productId:', productId);
        showToast('❌ Thiếu thông tin sản phẩm!', 'error');
        return;
    }

    showLoading('Đang xử lý...');

    try {
        let apiUrl = '';
        let bodyData = {};

        if (BUILD_MODE === 'replace' && ITEM_ID > 0) {
            apiUrl = '../api/replace_build_item.php';
            bodyData = {
                build_id: parseInt(BUILD_ID),
                item_id: parseInt(ITEM_ID), // ✅ Sử dụng item_id
                new_product_id: parseInt(productId)
            };
            console.log('🔄 REPLACE mode - API call:', apiUrl);
            console.log('   Body:', bodyData);
        } else if (BUILD_MODE === 'add') {
            apiUrl = '../api/add_product_to_build.php';
            bodyData = {
                build_id: parseInt(BUILD_ID),
                product_id: parseInt(productId)
            };
            console.log('➕ ADD mode - API call:', apiUrl);
            console.log('   Body:', bodyData);
        } else {
            console.error('❌ Invalid mode or missing item_id:', {BUILD_MODE, ITEM_ID});
            hideLoading();
            showToast('❌ Chế độ không hợp lệ!', 'error');
            return;
        }

        console.log('📡 Sending request...');
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(bodyData),
            credentials: 'include'
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
            const successParam = BUILD_MODE === 'replace' ? 'replaced' : 'added';
            const redirectUrl = `build_manage.php?id=${BUILD_ID}&success=${successParam}`;
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

// ===== SELECT BUTTON INITIALIZATION (BUILD MODE) =====
function initSelectButtons() {
    if (!IS_BUILD_MODE) {
        console.log('⏭️ Skipping select buttons (not in build mode)');
        return;
    }
    
    const buttons = document.querySelectorAll('.select-product-btn');
    console.log('🔘 Found', buttons.length, 'select buttons');
    
    buttons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = parseInt(this.getAttribute('data-product-id'));
            const productName = this.getAttribute('data-product-name');

            console.log('✅ Select button clicked:', productId, productName);

            if (!productId || productId <= 0) {
                console.error('❌ Invalid product ID:', productId);
                showToast('❌ Sản phẩm không hợp lệ!', 'error');
                return;
            }

            this.disabled = true;
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';

            await selectProductForBuild(productId);

            this.disabled = false;
            this.innerHTML = originalHTML;
        });
    });
    
    console.log('✅ Initialized', buttons.length, 'select buttons');
}

// ===== QUANTITY INPUT INITIALIZATION =====
function initQuantityInputs() {
    const inputs = document.querySelectorAll('.qty-input');
    
    inputs.forEach(input => {
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
    
    console.log('✅ Initialized', inputs.length, 'quantity inputs');
}

// ===== REVIEW MODAL FUNCTIONS =====
function initReviewModal() {
    // Initialize rating buttons
    document.querySelectorAll('.rating-btn').forEach((btn, i) => {
        if (i + 1 <= 5) btn.classList.add('active');
    });

    // Auto close modal after success
    if (REVIEW_SUCCESS) {
        setTimeout(() => {
            closeReviewModal();
            location.reload();
        }, 2000);
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('reviewModal');
        if (event.target === modal) {
            closeReviewModal();
        }
    };
    
    console.log('✅ Initialized review modal');
}

function openReviewModal() {
    if (!IS_LOGGED_IN) {
        window.location.href = 'login.php';
        return;
    }
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function setRating(rating, event) {
    event.preventDefault();
    const ratingValue = document.getElementById('ratingValue');
    if (ratingValue) {
        ratingValue.value = rating;
    }
    document.querySelectorAll('.rating-btn').forEach((btn, i) => {
        btn.classList.toggle('active', i + 1 <= rating);
    });
}

function updateCount(element, countId) {
    const counter = document.getElementById(countId);
    if (counter) {
        counter.textContent = element.value.length;
    }
}

function previewReviewImages(files) {
    const container = document.getElementById('previewImages');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (files.length > 5) {
        showToast('⚠️ Chỉ được chọn tối đa 5 ảnh', 'error');
        return;
    }
    
    Array.from(files).forEach((file, index) => {
        if (file.size > 5000000) {
            showToast(`⚠️ File ${file.name} quá lớn (>5MB)`, 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <button type="button" class="remove-preview" onclick="removePreviewImage(${index})">×</button>
            `;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

function removePreviewImage(index) {
    const input = document.getElementById('reviewImageInput');
    if (!input) return;
    
    const dt = new DataTransfer();
    const files = input.files;
    
    for (let i = 0; i < files.length; i++) {
        if (i !== index) {
            dt.items.add(files[i]);
        }
    }
    
    input.files = dt.files;
    previewReviewImages(dt.files);
}

function handleImageDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.style.background = 'white';
    
    const files = event.dataTransfer.files;
    const input = document.getElementById('reviewImageInput');
    
    if (input) {
        input.files = files;
        previewReviewImages(files);
    }
}

// ===== EXPOSE FUNCTIONS TO GLOBAL SCOPE =====
window.cancelBuildMode = cancelBuildMode;
window.openReviewModal = openReviewModal;
window.closeReviewModal = closeReviewModal;
window.setRating = setRating;
window.updateCount = updateCount;
window.previewReviewImages = previewReviewImages;
window.removePreviewImage = removePreviewImage;
window.handleImageDrop = handleImageDrop;