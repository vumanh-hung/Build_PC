/**
 * assets/js/products.js - Products Page Handler
 * CRITICAL FIX: Define functions BEFORE DOM loads
 */

console.log('📦 products.js loading...');

// ===== EXPOSE FUNCTIONS IMMEDIATELY (BEFORE DOMContentLoaded) =====
window.cancelBuildMode = function() {
    console.log('🚫 Cancel build mode');
    
    sessionStorage.removeItem('build_mode');
    sessionStorage.removeItem('replacing_item_id');
    sessionStorage.removeItem('replacing_build_id');
    sessionStorage.removeItem('replacing_category');
    sessionStorage.removeItem('adding_build_id');
    sessionStorage.removeItem('adding_category');
    
    const BUILD_ID = window.PRODUCTS_CONFIG?.BUILD_ID || 0;
    
    if (BUILD_ID && BUILD_ID > 0) {
        window.location.href = `build_manage.php?id=${BUILD_ID}`;
    } else {
        window.location.href = 'products.php';
    }
};

window.openReviewModal = function() {
    const IS_LOGGED_IN = window.PRODUCTS_CONFIG?.IS_LOGGED_IN || false;
    if (!IS_LOGGED_IN) {
        window.location.href = 'login.php';
        return;
    }
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.style.display = 'flex';
    }
};

window.closeReviewModal = function() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.setRating = function(rating, event) {
    event.preventDefault();
    const ratingValue = document.getElementById('ratingValue');
    if (ratingValue) {
        ratingValue.value = rating;
    }
    document.querySelectorAll('.rating-btn').forEach((btn, i) => {
        btn.classList.toggle('active', i + 1 <= rating);
    });
};

window.updateCount = function(element, countId) {
    const counter = document.getElementById(countId);
    if (counter) {
        counter.textContent = element.value.length;
    }
};

window.previewReviewImages = function(files) {
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
};

window.removePreviewImage = function(index) {
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
    window.previewReviewImages(dt.files);
};

window.handleImageDrop = function(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.style.background = 'white';
    
    const files = event.dataTransfer.files;
    const input = document.getElementById('reviewImageInput');
    
    if (input) {
        input.files = files;
        window.previewReviewImages(files);
    }
};

console.log('✅ Global functions exposed');

// ===== GET CONFIG =====
function getConfig() {
    if (!window.PRODUCTS_CONFIG) {
        console.error('❌ PRODUCTS_CONFIG not found!');
        return {
            CSRF_TOKEN: '',
            IS_BUILD_MODE: false,
            BUILD_MODE: '',
            BUILD_ID: 0,
            ITEM_ID: 0,
            IS_LOGGED_IN: false,
            REVIEW_SUCCESS: false
        };
    }
    return window.PRODUCTS_CONFIG;
}

const CONFIG = getConfig();
const {
    CSRF_TOKEN,
    IS_BUILD_MODE,
    BUILD_MODE,
    BUILD_ID,
    ITEM_ID,
    IS_LOGGED_IN,
    REVIEW_SUCCESS
} = CONFIG;

console.log('📦 Config loaded:', CONFIG);

// ===== UTILITIES =====
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
    console.log(`🔔 Toast: ${message} (${type})`);
    
    const toast = document.getElementById('toast');
    if (toast) {
        toast.textContent = message;
        toast.className = 'toast ' + type + ' show';
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
}

function playTingSound() {
    const sound = document.getElementById("tingSound");
    if (sound) {
        sound.currentTime = 0;
        sound.play().catch(() => {});
    }
}

// ===== SELECT PRODUCT FOR BUILD =====
async function selectProductForBuild(productId) {
    console.log('🎯 selectProductForBuild:', productId);
    
    if (!BUILD_ID) {
        console.error('❌ No BUILD_ID');
        showToast('❌ Thiếu thông tin build!', 'error');
        return;
    }

    showLoading('Đang thêm vào build...');

    try {
        let apiUrl = '';
        let bodyData = {};

        if (BUILD_MODE === 'replace' && ITEM_ID) {
            apiUrl = '../api/replace_build_item.php';
            bodyData = {
                build_id: parseInt(BUILD_ID),
                item_id: parseInt(ITEM_ID),
                new_product_id: parseInt(productId)
            };
        } else if (BUILD_MODE === 'add') {
            apiUrl = '../api/add_product_to_build.php';
            bodyData = {
                build_id: parseInt(BUILD_ID),
                product_id: parseInt(productId)
            };
        } else {
            hideLoading();
            showToast('❌ Chế độ không hợp lệ!', 'error');
            return;
        }

        console.log('📡 API:', apiUrl);
        console.log('📦 Body:', bodyData);

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(bodyData),
            credentials: 'include'
        });

        const data = await response.json();
        console.log('📨 Response:', data);

        if (data.success) {
            sessionStorage.clear();
            hideLoading();
            playTingSound();
            showToast('✅ Đã thêm vào build!', 'success');
            
            setTimeout(() => {
                const successParam = BUILD_MODE === 'replace' ? 'replaced' : 'added';
                window.location.href = `build_manage.php?id=${BUILD_ID}&success=${successParam}`;
            }, 1000);
        } else {
            hideLoading();
            showToast(`❌ ${data.error || 'Không thể xử lý'}`, 'error');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        hideLoading();
        showToast('❌ Lỗi kết nối!', 'error');
    }
}

// ===== DOM READY =====
document.addEventListener('DOMContentLoaded', () => {
    console.log('✅ DOM Ready');
    
    // Init audio
    document.addEventListener("click", () => {
        const sound = document.getElementById("tingSound");
        if (sound && sound.paused) {
            sound.play().then(() => { 
                sound.pause(); 
                sound.currentTime = 0; 
            }).catch(() => {});
        }
    }, { once: true });
    
    // Init select buttons
    initSelectButtons();
    
    // Init quantity inputs
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
    
    // Init review modal
    document.querySelectorAll('.rating-btn').forEach((btn, i) => {
        if (i + 1 <= 5) btn.classList.add('active');
    });

    if (REVIEW_SUCCESS) {
        setTimeout(() => {
            window.closeReviewModal();
            location.reload();
        }, 2000);
    }
    
    window.onclick = function(event) {
        const modal = document.getElementById('reviewModal');
        if (event.target === modal) {
            window.closeReviewModal();
        }
    };
    
    console.log('✅ All initialized');
});

// ===== SELECT BUTTONS =====
function initSelectButtons() {
    console.log('🔘 initSelectButtons');
    console.log('   IS_BUILD_MODE:', IS_BUILD_MODE);
    
    if (!IS_BUILD_MODE) {
        console.log('⏭️ Skip - not in build mode');
        return;
    }
    
    console.log('✅ Attaching click handlers...');
    
    // ✅ Direct click on each button
    setTimeout(() => {
        const buttons = document.querySelectorAll('.select-product-btn');
        console.log(`✅ Found ${buttons.length} buttons`);
        
        buttons.forEach((button, index) => {
            console.log(`   Button ${index + 1}:`, button.getAttribute('data-product-id'));
            
            // Remove old listeners
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Add new listener
            newButton.addEventListener('click', function(e) {
                console.log('🎯 BUTTON CLICKED!');
                
                e.preventDefault();
                e.stopPropagation();
                
                const productId = parseInt(this.getAttribute('data-product-id'));
                const productName = this.getAttribute('data-product-name');
                
                console.log('   Product ID:', productId);
                console.log('   Product Name:', productName);
                
                if (!productId || productId <= 0) {
                    showToast('❌ Sản phẩm không hợp lệ!', 'error');
                    return;
                }
                
                // Disable button
                this.disabled = true;
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';
                
                // Call API
                selectProductForBuild(productId).finally(() => {
                    this.disabled = false;
                    this.innerHTML = originalHTML;
                });
            });
        });
        
        console.log('✅ All buttons attached!');
    }, 500);
}

// Add shake effect to cart when product added
function shakeCart() {
    const cartLink = document.querySelector('.cart-link');
    if (cartLink) {
        cartLink.classList.add('shake');
        setTimeout(() => {
            cartLink.classList.remove('shake');
        }, 500);
    }
}

console.log('✅ products.js loaded');