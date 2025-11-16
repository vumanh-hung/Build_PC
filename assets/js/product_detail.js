/**
 * assets/js/product_detail.js - Product Detail Page Interactivity
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== GLOBAL VARIABLES =====
    let compareList = JSON.parse(localStorage.getItem('compareList') || '[]');
    const productData = window.PRODUCT_DATA || {};
    
    // ===== IMAGE GALLERY =====
    initImageGallery();
    
    // ===== QUANTITY CONTROLS =====
    initQuantityControls();
    
    // ===== TABS =====
    initTabs();
    
    // ===== FLASH SALE COUNTDOWN =====
    if (productData.IS_FLASH_SALE) {
        initFlashSaleCountdown();
    }
    
    // ===== ACTION BUTTONS =====
    initActionButtons();
    
    // ===== COMPARE FUNCTIONALITY =====
    initCompareSystem();
    
    // ===== IMAGE GALLERY =====
    function initImageGallery() {
        const thumbnails = document.querySelectorAll('.thumbnail-item');
        const mainImage = document.getElementById('mainProductImage');
        
        if (!thumbnails.length || !mainImage) return;
        
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const imageSrc = this.dataset.image;
                
                // Update main image with fade effect
                mainImage.style.opacity = '0';
                setTimeout(() => {
                    mainImage.src = imageSrc;
                    mainImage.style.opacity = '1';
                }, 200);
                
                // Update active state
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
    
    // ===== QUANTITY CONTROLS =====
    function initQuantityControls() {
        const qtyInput = document.getElementById('productQuantity');
        const decreaseBtn = document.querySelector('.qty-decrease');
        const increaseBtn = document.querySelector('.qty-increase');
        
        if (!qtyInput) return;
        
        const maxStock = parseInt(productData.MAX_STOCK) || 99;
        
        decreaseBtn?.addEventListener('click', () => {
            let value = parseInt(qtyInput.value) || 1;
            if (value > 1) {
                qtyInput.value = value - 1;
            }
        });
        
        increaseBtn?.addEventListener('click', () => {
            let value = parseInt(qtyInput.value) || 1;
            if (value < maxStock) {
                qtyInput.value = value + 1;
            } else {
                showNotification('Đã đạt số lượng tối đa trong kho', 'warning');
            }
        });
        
        // Validate manual input
        qtyInput.addEventListener('change', function() {
            let value = parseInt(this.value) || 1;
            if (value < 1) value = 1;
            if (value > maxStock) value = maxStock;
            this.value = value;
        });
    }
    
    // ===== TABS SYSTEM =====
    function initTabs() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanels = document.querySelectorAll('.tab-panel');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                
                // Update buttons
                tabBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Update panels
                tabPanels.forEach(panel => {
                    panel.classList.remove('active');
                    if (panel.id === `${tabName}-panel`) {
                        panel.classList.add('active');
                    }
                });
            });
        });
    }
    
    // ===== FLASH SALE COUNTDOWN =====
    function initFlashSaleCountdown() {
        const countdownEl = document.querySelector('.flash-sale-countdown');
        if (!countdownEl) return;
        
        const endTime = new Date(productData.FLASH_SALE_END).getTime();
        
        const timer = setInterval(() => {
            const now = new Date().getTime();
            const distance = endTime - now;
            
            if (distance < 0) {
                clearInterval(timer);
                countdownEl.innerHTML = '<p style="color: #e74c3c; font-weight: 700;">Flash Sale đã kết thúc</p>';
                return;
            }
            
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('flashHours').textContent = String(hours).padStart(2, '0');
            document.getElementById('flashMinutes').textContent = String(minutes).padStart(2, '0');
            document.getElementById('flashSeconds').textContent = String(seconds).padStart(2, '0');
        }, 1000);
    }
    
    // ===== ACTION BUTTONS =====
    function initActionButtons() {
        const buyNowBtn = document.getElementById('buyNowBtn');
        const addToCartBtn = document.getElementById('addToCartBtn');
        
        buyNowBtn?.addEventListener('click', handleBuyNow);
        addToCartBtn?.addEventListener('click', handleAddToCart);
    }
    
    function handleBuyNow() {
        const quantity = document.getElementById('productQuantity').value;
        window.location.href = `checkout.php?product_id=${productData.PRODUCT_ID}&quantity=${quantity}`;
    }
    
    function handleAddToCart() {
        const quantity = document.getElementById('productQuantity').value;
        
        fetch('./add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productData.PRODUCT_ID}&quantity=${quantity}&csrf=${encodeURIComponent(productData.CSRF_TOKEN)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                playCartSound();
                showNotification('✓ Đã thêm sản phẩm vào giỏ hàng!', 'success');
                updateCartCount(data.cart_count || quantity);
            } else {
                showNotification('✗ ' + (data.message || 'Có lỗi xảy ra'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('✗ Không thể thêm vào giỏ hàng. Vui lòng thử lại!', 'error');
        });
    }
    
    function updateCartCount(count) {
        const cartCountEl = document.getElementById('headerCartCount');
        if (cartCountEl) {
            cartCountEl.textContent = count;
        } else {
            const cartLink = document.getElementById('headerCartLink');
            if (cartLink) {
                const span = document.createElement('span');
                span.id = 'headerCartCount';
                span.className = 'cart-count';
                span.textContent = count;
                cartLink.appendChild(span);
            }
        }
    }
    
    // ===== CART SOUND =====
    function playCartSound() {
        const sound = document.getElementById('cartSound');
        if (sound) {
            sound.currentTime = 0;
            sound.play().catch(() => playWebAudioBeep());
        } else {
            playWebAudioBeep();
        }
    }
    
    function playWebAudioBeep() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const now = audioContext.currentTime;
            
            // Beep 1
            const osc1 = audioContext.createOscillator();
            const gain1 = audioContext.createGain();
            osc1.connect(gain1);
            gain1.connect(audioContext.destination);
            gain1.gain.setValueAtTime(0.3, now);
            osc1.frequency.setValueAtTime(800, now);
            gain1.gain.exponentialRampToValueAtTime(0.01, now + 0.08);
            osc1.start(now);
            osc1.stop(now + 0.08);
            
            // Beep 2
            const osc2 = audioContext.createOscillator();
            const gain2 = audioContext.createGain();
            osc2.connect(gain2);
            gain2.connect(audioContext.destination);
            gain2.gain.setValueAtTime(0.2, now + 0.1);
            osc2.frequency.setValueAtTime(1000, now + 0.1);
            gain2.gain.exponentialRampToValueAtTime(0.01, now + 0.18);
            osc2.start(now + 0.1);
            osc2.stop(now + 0.18);
        } catch (e) {
            console.log('Cannot play audio:', e);
        }
    }
    
    // ===== COMPARE SYSTEM =====
    function initCompareSystem() {
        const compareBtn = document.getElementById('compareToggleBtn');
        const clearBtn = document.getElementById('clearCompareBtn');
        const goBtn = document.getElementById('goCompareBtn');
        
        compareBtn?.addEventListener('click', toggleCompare);
        clearBtn?.addEventListener('click', clearCompareList);
        goBtn?.addEventListener('click', goToComparePage);
        
        updateCompareUI();
    }
    
    function toggleCompare() {
        const productId = productData.PRODUCT_ID;
        const productName = productData.PRODUCT_NAME;
        const btn = document.getElementById('compareToggleBtn');
        
        const index = compareList.findIndex(item => item.id === productId);
        
        if (index > -1) {
            // Remove from compare
            compareList.splice(index, 1);
            btn.classList.remove('active');
            btn.innerHTML = '<i class="fa-solid fa-balance-scale"></i><span>Thêm vào so sánh</span>';
            showNotification('Đã bỏ khỏi danh sách so sánh', 'info');
        } else {
            // Add to compare
            if (compareList.length >= 4) {
                showNotification('Chỉ có thể so sánh tối đa 4 sản phẩm', 'warning');
                return;
            }
            compareList.push({ id: productId, name: productName });
            btn.classList.add('active');
            btn.innerHTML = '<i class="fa-solid fa-check"></i><span>Đã thêm vào so sánh</span>';
            showNotification('Đã thêm vào danh sách so sánh', 'success');
        }
        
        localStorage.setItem('compareList', JSON.stringify(compareList));
        updateCompareUI();
    }
    
    function updateCompareUI() {
        const compareBar = document.getElementById('compareFixedBar');
        const compareCounter = document.getElementById('compareCounter');
        const compareItemsList = document.getElementById('compareItemsList');
        const compareBtn = document.getElementById('compareToggleBtn');
        
        // Update button state
        if (compareBtn) {
            const currentProductId = productData.PRODUCT_ID;
            if (compareList.find(item => item.id === currentProductId)) {
                compareBtn.classList.add('active');
                compareBtn.innerHTML = '<i class="fa-solid fa-check"></i><span>Đã thêm vào so sánh</span>';
            } else {
                compareBtn.classList.remove('active');
                compareBtn.innerHTML = '<i class="fa-solid fa-balance-scale"></i><span>Thêm vào so sánh</span>';
            }
        }
        
        // Show/hide compare bar
        if (compareList.length > 0) {
            compareBar.style.display = 'block';
            compareCounter.textContent = compareList.length;
            
            // Update items list
            compareItemsList.innerHTML = '';
            compareList.forEach(product => {
                const item = document.createElement('div');
                item.className = 'compare-item';
                item.innerHTML = `
                    <span>${product.name}</span>
                    <button class="btn-remove-item" onclick="removeCompareItem(${product.id})">
                        <i class="fa-solid fa-times"></i>
                    </button>
                `;
                compareItemsList.appendChild(item);
            });
        } else {
            compareBar.style.display = 'none';
        }
    }
    
    window.removeCompareItem = function(productId) {
        compareList = compareList.filter(item => item.id !== productId);
        localStorage.setItem('compareList', JSON.stringify(compareList));
        updateCompareUI();
        showNotification('Đã xóa sản phẩm khỏi danh sách', 'info');
    };
    
    function clearCompareList() {
        if (confirm('Bạn có chắc muốn xóa tất cả sản phẩm khỏi danh sách so sánh?')) {
            compareList = [];
            localStorage.removeItem('compareList');
            updateCompareUI();
            showNotification('Đã xóa tất cả sản phẩm', 'info');
        }
    }
    
    function goToComparePage() {
        if (compareList.length < 2) {
            showNotification('Vui lòng chọn ít nhất 2 sản phẩm để so sánh', 'warning');
            return;
        }
        const ids = compareList.map(item => item.id).join(',');
        window.location.href = `product_compare.php?ids=${ids}`;
    }
    
    // ===== NOTIFICATION SYSTEM =====
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `toast-notification toast-${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            warning: 'fa-exclamation-circle',
            info: 'fa-info-circle',
            error: 'fa-times-circle'
        };
        
        notification.innerHTML = `
            <i class="fa-solid ${icons[type]}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 10);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
});

// ===== DYNAMIC STYLES FOR NOTIFICATIONS =====
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    .toast-notification {
        position: fixed;
        top: 80px;
        right: -400px;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
        max-width: 400px;
        color: white;
    }
    
    .toast-notification.show {
        right: 20px;
    }
    
    .toast-success {
        background: linear-gradient(135deg, #28a745, #20c997);
    }
    
    .toast-warning {
        background: linear-gradient(135deg, #ffc107, #ff9800);
        color: #333;
    }
    
    .toast-info {
        background: linear-gradient(135deg, #17a2b8, #007bff);
    }
    
    .toast-error {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }
    
    .toast-notification i {
        font-size: 20px;
    }
    
    @media (max-width: 768px) {
        .toast-notification {
            max-width: calc(100% - 40px);
            right: -100%;
        }
        
        .toast-notification.show {
            right: 20px;
        }
    }
`;
document.head.appendChild(notificationStyles);