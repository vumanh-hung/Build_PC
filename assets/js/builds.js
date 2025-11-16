/**
 * builds.js - Builds Page JavaScript
 * Client-side logic cho trang xây dựng cấu hình PC
 */

// ================================================
// BUILDS PAGE OBJECT
// ================================================

const BuildsPage = {
    // State
    isBuildMode: false,
    currentBuildId: null,
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
    userId: document.querySelector('meta[name="user-id"]')?.content || '',
    isLoggedIn: document.querySelector('meta[name="user-logged-in"]')?.content === 'true',
    
    // API Endpoints
    api: {
        createBuild: '../api/create_empty_build.php',
        updateBuild: '../api/update_build.php',
        getBuildItems: '../api/get_build_items.php',
        addToCart: '../api/add_build_to_cart.php',
        deleteBuild: '../api/delete_build.php',
        cartStatus: '../api/cart_api.php'
    },
    
    // Elements
    elements: {
        toast: null,
        buildModeIndicator: null,
        instructionBox: null,
        categoriesGrid: null,
        categoryItems: null,
        cartCount: null,
        tingSound: null
    },
    
    /**
     * Initialize the page
     */
    init() {
        console.log('🔧 Initializing Builds Page...');
        
        // Cache DOM elements
        this.cacheElements();
        
        // Initialize AOS
        this.initAOS();
        
        // Check if returning from products page
        this.checkBuildModeState();
        
        // Attach event listeners
        this.attachEventListeners();
        
        // Enable audio on first interaction
        this.enableAudio();
        
        console.log('✅ Builds Page initialized');
    },
    
    /**
     * Cache DOM elements
     */
    cacheElements() {
        this.elements.toast = document.getElementById('toast');
        this.elements.buildModeIndicator = document.getElementById('buildModeIndicator');
        this.elements.instructionBox = document.getElementById('instructionBox');
        this.elements.categoriesGrid = document.getElementById('categoriesGrid');
        this.elements.categoryItems = document.querySelectorAll('.category-card');
        this.elements.cartCount = document.querySelector('.cart-count');
        this.elements.tingSound = document.getElementById('tingSound');
    },
    
    /**
     * Initialize AOS animations
     */
    initAOS() {
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 600,
                easing: 'ease-out-cubic',
                once: true,
                offset: 50
            });
        }
    },
    
    /**
     * Enable audio on first user interaction
     */
    enableAudio() {
        const enableAudioOnce = () => {
            const sound = this.elements.tingSound;
            if (sound && sound.paused) {
                sound.play().then(() => {
                    sound.pause();
                    sound.currentTime = 0;
                }).catch(() => {});
            }
            document.removeEventListener('click', enableAudioOnce);
        };
        
        document.addEventListener('click', enableAudioOnce, { once: true });
    },
    
    /**
     * Attach all event listeners
     */
    attachEventListeners() {
        // Category item clicks
        this.elements.categoryItems.forEach(item => {
            item.addEventListener('click', (e) => this.handleCategoryClick(e));
        });
    },
    
    /**
     * Check if we're in build mode (returning from products page)
     */
    checkBuildModeState() {
        const buildId = sessionStorage.getItem('current_build_id');
        const buildMode = sessionStorage.getItem('build_creation_mode');
        
        if (buildMode === 'creating' && buildId) {
            console.log('🔄 Resuming build mode:', buildId);
            this.isBuildMode = true;
            this.currentBuildId = buildId;
            this.enterBuildMode();
        }
    },
    
    /**
     * Handle category card click
     */
    handleCategoryClick(event) {
        const card = event.currentTarget;
        const categoryId = card.dataset.categoryId;
        const categoryName = card.dataset.categoryName;
        
        if (this.isBuildMode && this.currentBuildId) {
            // In build mode - go to products page
            this.goToProductsPage(categoryId, categoryName);
        } else {
            // Not in build mode - show instruction
            this.showToast('⚠️ Vui lòng nhấn "Bắt đầu tạo cấu hình" trước khi chọn linh kiện!', 'warning');
        }
    },
    
    /**
     * Start creating new build
     */
    async startNewBuild() {
        console.log('🔧 Starting new build...');
        
        // Check if user is logged in
        if (!this.isLoggedIn) {
            this.showToast('⚠️ Vui lòng đăng nhập để tạo cấu hình!', 'warning');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
            return;
        }
        
        try {
            // Create empty build
            const response = await fetch(this.api.createBuild, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: 'Cấu hình mới',
                    csrf: this.csrf
                }),
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success && data.build_id) {
                console.log('✅ Build created:', data.build_id);
                
                // Update state
                this.isBuildMode = true;
                this.currentBuildId = data.build_id;
                
                // Store in sessionStorage
                sessionStorage.setItem('current_build_id', this.currentBuildId);
                sessionStorage.setItem('build_creation_mode', 'creating');
                
                // Enter build mode
                this.enterBuildMode();
                
                this.showToast('✅ Đã tạo cấu hình mới! Hãy chọn linh kiện bạn muốn.', 'success');
            } else {
                throw new Error(data.error || 'Không thể tạo cấu hình');
            }
        } catch (error) {
            console.error('❌ Error creating build:', error);
            this.showToast('❌ ' + error.message, 'error');
        }
    },
    
    /**
     * Enter build mode (show indicator, highlight categories)
     */
    enterBuildMode() {
        console.log('🔧 Entering build mode');
        
        // Hide instruction box
        if (this.elements.instructionBox) {
            this.elements.instructionBox.style.display = 'none';
        }
        
        // Show indicator
        if (this.elements.buildModeIndicator) {
            this.elements.buildModeIndicator.classList.add('active');
        }
        
        // Highlight category cards
        this.elements.categoryItems.forEach(card => {
            card.classList.add('build-mode');
        });
    },
    
    /**
     * Cancel build creation
     */
    cancelBuild() {
        console.log('🚫 Canceling build creation');
        
        if (!confirm('Bạn có chắc muốn hủy? Cấu hình sẽ được giữ lại để bạn có thể chỉnh sửa sau.')) {
            return;
        }
        
        // Clear sessionStorage
        sessionStorage.removeItem('current_build_id');
        sessionStorage.removeItem('build_creation_mode');
        
        // Reload page
        location.reload();
    },
    
    /**
     * Finish build - save and name it
     */
    async finishBuild() {
        console.log('✅ Finishing build...');
        
        if (!this.currentBuildId) {
            this.showToast('⚠️ Không tìm thấy cấu hình!', 'warning');
            return;
        }
        
        try {
            // Step 1: Get build items
            const itemsResponse = await fetch(
                `${this.api.getBuildItems}?build_id=${this.currentBuildId}`
            );
            const itemsData = await itemsResponse.json();
            
            console.log('📦 Build items:', itemsData);
            
            if (!itemsData.success) {
                throw new Error(itemsData.error || 'Không thể lấy danh sách sản phẩm');
            }
            
            // Step 2: Validate items
            if (!itemsData.items || itemsData.items.length === 0) {
                this.showToast('⚠️ Bạn chưa chọn linh kiện nào! Vui lòng chọn ít nhất 1 sản phẩm.', 'warning');
                return;
            }
            
            // Step 3: Get build name from user
            const name = prompt('Đặt tên cho cấu hình:', 'Cấu hình của tôi');
            
            if (!name || name.trim() === '') {
                this.showToast('⚠️ Bạn cần đặt tên cho cấu hình!', 'warning');
                return;
            }
            
            // Step 4: Extract product IDs
            const productIds = itemsData.items.map(item => item.product_id);
            
            // Step 5: Update build
            const updatePayload = {
                build_id: this.currentBuildId,
                name: name.trim(),
                parts: productIds,
                csrf: this.csrf
            };
            
            const updateResponse = await fetch(this.api.updateBuild, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(updatePayload)
            });
            
            const updateData = await updateResponse.json();
            
            if (updateData.success) {
                console.log('✅ Build saved successfully!');
                this.showToast('✅ Đã lưu cấu hình: ' + name, 'success');
                
                // Clear sessionStorage
                sessionStorage.removeItem('current_build_id');
                sessionStorage.removeItem('build_creation_mode');
                
                // Redirect to manage page
                setTimeout(() => {
                    window.location.href = `build_manage.php?id=${this.currentBuildId}`;
                }, 1000);
            } else {
                throw new Error(updateData.error || 'Không thể lưu cấu hình');
            }
        } catch (error) {
            console.error('❌ Error finishing build:', error);
            this.showToast('❌ ' + error.message, 'error');
        }
    },
    
    /**
     * Navigate to products page for category selection
     */
    goToProductsPage(categoryId, categoryName) {
        console.log('➡️ Going to products page:', { categoryId, categoryName });
        
        // Store context in sessionStorage
        sessionStorage.setItem('adding_category', categoryName);
        sessionStorage.setItem('adding_build_id', this.currentBuildId);
        
        // Redirect
        window.location.href = `products.php?category_id=${categoryId}&build_id=${this.currentBuildId}&mode=add`;
    },
    
    /**
     * Add entire build to cart
     */
    async addBuildToCart(buildId) {
        console.log('🛒 Adding build to cart:', buildId);
        
        try {
            const response = await fetch(this.api.addToCart, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    build_id: buildId,
                    csrf: this.csrf
                }),
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('✅ Đã thêm cấu hình vào giỏ hàng!', 'success');
                this.playSound();
                this.refreshCartCount();
                this.shakeCartIcon();
            } else {
                throw new Error(data.error || 'Không thể thêm vào giỏ hàng');
            }
        } catch (error) {
            console.error('❌ Error adding build to cart:', error);
            this.showToast('❌ ' + error.message, 'error');
        }
    },
    
    /**
     * Delete a build
     */
    async deleteBuild(buildId) {
        console.log('🗑️ Deleting build:', buildId);
        
        if (!confirm('Bạn có chắc muốn xóa cấu hình này không?')) {
            return;
        }
        
        try {
            const response = await fetch(this.api.deleteBuild, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    build_id: buildId,
                    csrf: this.csrf
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('✅ Đã xóa cấu hình!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                throw new Error(data.error || 'Không thể xóa cấu hình');
            }
        } catch (error) {
            console.error('❌ Error deleting build:', error);
            this.showToast('❌ ' + error.message, 'error');
        }
    },
    
    /**
     * Refresh cart count
     */
    async refreshCartCount() {
        try {
            const response = await fetch(this.api.cartStatus, {
                credentials: 'include'
            });
            const data = await response.json();
            
            if (data.ok && data.cart_count !== undefined) {
                const countEl = this.elements.cartCount;
                const cartLink = document.querySelector('.cart-link');
                
                if (data.cart_count > 0) {
                    if (countEl) {
                        countEl.textContent = data.cart_count;
                    } else if (cartLink) {
                        const badge = document.createElement('span');
                        badge.className = 'cart-count';
                        badge.textContent = data.cart_count;
                        cartLink.appendChild(badge);
                        this.elements.cartCount = badge;
                    }
                } else if (countEl) {
                    countEl.remove();
                    this.elements.cartCount = null;
                }
            }
        } catch (error) {
            console.error('Error refreshing cart count:', error);
        }
    },
    
    /**
     * Shake cart icon animation
     */
    shakeCartIcon() {
        const cartIcon = document.querySelector('.fa-cart-shopping');
        if (cartIcon) {
            cartIcon.classList.add('cart-shake');
            setTimeout(() => {
                cartIcon.classList.remove('cart-shake');
            }, 700);
        }
    },
    
    /**
     * Play notification sound
     */
    playSound() {
        const sound = this.elements.tingSound;
        if (sound) {
            sound.play().catch(error => {
                console.log('Audio play failed:', error);
            });
        }
    },
    
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
    }
};

// ================================================
// INITIALIZE ON DOM READY
// ================================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => BuildsPage.init());
} else {
    BuildsPage.init();
}

// Export for use in inline scripts
window.BuildsPage = BuildsPage;