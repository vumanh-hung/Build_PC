/**
 * brands.js - Brands Page JavaScript
 * Client-side logic cho trang thương hiệu
 */

// ================================================
// CONFIGURATION
// ================================================

const BrandsPage = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
    cartApiUrl: '../api/cart.php',
    soundEnabled: true,
    
    // Elements
    elements: {
        toast: null,
        cartCount: null,
        cartLink: null,
        addToCartButtons: null,
        qtyInputs: null,
        cartSound: null
    },
    
    // Initialize
    init() {
        this.cacheElements();
        this.initAOS();
        this.attachEventListeners();
        this.initQuantityInputs();
        console.log('✅ Brands page initialized');
    },
    
    // Cache DOM elements
    cacheElements() {
        this.elements.toast = document.getElementById('toast');
        this.elements.cartCount = document.querySelector('.cart-count');
        this.elements.cartLink = document.querySelector('.cart-link');
        this.elements.addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
        this.elements.qtyInputs = document.querySelectorAll('.qty-input');
        this.elements.cartSound = document.getElementById('cartSound');
    },
    
    // Initialize AOS animations
    initAOS() {
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 600,
                easing: 'ease-out-cubic',
                once: true,
                offset: 50,
                disable: 'mobile' // Disable on mobile for better performance
            });
        }
    },
    
    // Attach all event listeners
    attachEventListeners() {
        // Add to cart buttons
        this.elements.addToCartButtons?.forEach(button => {
            button.addEventListener('click', (e) => this.handleAddToCart(e));
        });
        
        // Quantity inputs validation
        this.elements.qtyInputs?.forEach(input => {
            input.addEventListener('change', (e) => this.validateQuantity(e));
            input.addEventListener('keypress', (e) => this.handleQuantityKeypress(e));
        });
        
        // Brand cards click tracking
        document.querySelectorAll('.brand-card').forEach(card => {
            card.addEventListener('click', (e) => this.trackBrandClick(e));
        });
    },
    
    // Initialize quantity inputs
    initQuantityInputs() {
        this.elements.qtyInputs?.forEach(input => {
            input.value = 1; // Default value
            input.min = 1;
            input.max = 99;
        });
    },
    
    // ================================================
    // CART OPERATIONS
    // ================================================
    
    /**
     * Handle add to cart button click
     */
    async handleAddToCart(event) {
        event.preventDefault();
        const button = event.currentTarget;
        const productId = button.getAttribute('data-product-id');
        
        if (!productId) {
            this.showToast('❌ Lỗi: Không tìm thấy ID sản phẩm', 'error');
            return;
        }
        
        // Get quantity from input
        const qtyInput = button.closest('.product-actions')?.querySelector('.qty-input');
        const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
        
        // Validate quantity
        if (quantity < 1 || quantity > 99) {
            this.showToast('❌ Số lượng không hợp lệ (1-99)', 'error');
            return;
        }
        
        // Disable button while processing
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang thêm...';
        
        try {
            await this.addToCart(productId, quantity);
            
            // Reset button
            button.disabled = false;
            button.innerHTML = '<i class="fa-solid fa-cart-plus"></i> Thêm';
            
            // Reset quantity input
            if (qtyInput) qtyInput.value = 1;
            
        } catch (error) {
            // Reset button on error
            button.disabled = false;
            button.innerHTML = '<i class="fa-solid fa-cart-plus"></i> Thêm';
        }
    },
    
    /**
     * Add product to cart via API
     */
    async addToCart(productId, quantity = 1) {
        const formData = new URLSearchParams();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        formData.append('csrf', this.csrf);
        
        try {
            const response = await fetch(this.cartApiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success || data.ok) {
                this.showToast(`✓ Đã thêm ${quantity} sản phẩm vào giỏ hàng`, 'success');
                this.updateCartCount(quantity);
                this.playCartSound();
                this.animateCartIcon();
                
                // Track event (if analytics available)
                this.trackAddToCart(productId, quantity);
                
            } else {
                throw new Error(data.message || 'Không thể thêm vào giỏ hàng');
            }
            
        } catch (error) {
            console.error('Add to cart error:', error);
            this.showToast(`❌ ${error.message}`, 'error');
            throw error;
        }
    },
    
    /**
     * Update cart count badge
     */
    updateCartCount(increment = 0) {
        let countEl = this.elements.cartCount;
        const cartLink = this.elements.cartLink;
        
        if (!countEl && increment > 0) {
            // Create badge if doesn't exist
            countEl = document.createElement('span');
            countEl.className = 'cart-count';
            countEl.textContent = increment;
            cartLink?.appendChild(countEl);
            this.elements.cartCount = countEl;
        } else if (countEl) {
            // Update existing badge
            const currentCount = parseInt(countEl.textContent) || 0;
            const newCount = currentCount + increment;
            countEl.textContent = Math.max(0, newCount);
            
            // Animate badge
            countEl.classList.add('bounce');
            setTimeout(() => countEl.classList.remove('bounce'), 600);
            
            // Remove if count is 0
            if (newCount <= 0) {
                countEl.remove();
                this.elements.cartCount = null;
            }
        }
    },
    
    /**
     * Animate cart icon
     */
    animateCartIcon() {
        const cartLink = this.elements.cartLink;
        if (cartLink) {
            cartLink.classList.add('cart-shake');
            setTimeout(() => {
                cartLink.classList.remove('cart-shake');
            }, 600);
        }
    },
    
    /**
     * Play cart sound
     */
    playCartSound() {
        if (this.soundEnabled && this.elements.cartSound) {
            this.elements.cartSound.play().catch(error => {
                console.log('Audio play failed:', error);
            });
        }
    },
    
    // ================================================
    // QUANTITY INPUT HANDLERS
    // ================================================
    
    /**
     * Validate quantity input
     */
    validateQuantity(event) {
        const input = event.target;
        let value = parseInt(input.value) || 1;
        
        // Clamp value between 1 and 99
        value = Math.max(1, Math.min(99, value));
        input.value = value;
    },
    
    /**
     * Handle keypress on quantity input
     */
    handleQuantityKeypress(event) {
        // Only allow numbers
        const charCode = event.which || event.keyCode;
        if (charCode < 48 || charCode > 57) {
            event.preventDefault();
            return false;
        }
    },
    
    // ================================================
    // TOAST NOTIFICATIONS
    // ================================================
    
    /**
     * Show toast notification
     */
    showToast(message, type = 'success') {
        const toast = this.elements.toast;
        if (!toast) return;
        
        // Remove existing classes
        toast.className = 'toast';
        
        // Add new class
        toast.classList.add(type, 'show');
        toast.textContent = message;
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    },
    
    // ================================================
    // ANALYTICS & TRACKING
    // ================================================
    
    /**
     * Track brand click
     */
    trackBrandClick(event) {
        const brandCard = event.currentTarget;
        const brandLink = brandCard.querySelector('.brand-link');
        const brandName = brandCard.querySelector('.brand-name')?.textContent;
        
        if (brandName) {
            console.log('Brand clicked:', brandName);
            
            // Track with Google Analytics if available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'brand_click', {
                    'event_category': 'engagement',
                    'event_label': brandName
                });
            }
        }
    },
    
    /**
     * Track add to cart event
     */
    trackAddToCart(productId, quantity) {
        console.log('Product added to cart:', { productId, quantity });
        
        // Track with Google Analytics if available
        if (typeof gtag !== 'undefined') {
            gtag('event', 'add_to_cart', {
                'event_category': 'ecommerce',
                'event_label': `Product ${productId}`,
                'value': quantity
            });
        }
    }
};

// ================================================
// UTILITY FUNCTIONS
// ================================================

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ================================================
// INITIALIZE ON DOM READY
// ================================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => BrandsPage.init());
} else {
    BrandsPage.init();
}

// Export for use in other scripts
window.BrandsPage = BrandsPage;