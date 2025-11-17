/**
 * assets/js/account.js - Account Page JavaScript
 * Xử lý upload avatar, navigation và quản lý builds
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== NAVIGATION =====
    initNavigation();
    
    // ===== AVATAR UPLOAD =====
    initAvatarUpload();
    
    // ===== PASSWORD VALIDATION =====
    initPasswordValidation();
    
    // ===== AUTO HIDE ALERTS =====
    initAutoHideAlerts();
    
    // ===== NAVIGATION =====
    function initNavigation() {
        const menuItems = document.querySelectorAll('.menu-item[data-section]');
        const sections = document.querySelectorAll('.content-section');

        menuItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const targetSection = this.dataset.section;

                // Update menu
                menuItems.forEach(mi => mi.classList.remove('active'));
                this.classList.add('active');

                // Show section
                sections.forEach(section => {
                    section.classList.remove('active');
                    if (section.id === targetSection + '-section') {
                        section.classList.add('active');
                    }
                });

                // Update URL hash
                window.location.hash = targetSection;
            });
        });

        // Handle initial hash
        const hash = window.location.hash.substring(1);
        if (hash) {
            const targetItem = document.querySelector(`.menu-item[data-section="${hash}"]`);
            if (targetItem) {
                targetItem.click();
            }
        }
    }
    
    // ===== AVATAR UPLOAD =====
    function initAvatarUpload() {
    const btnChangeAvatar = document.getElementById('btnChangeAvatar');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');

    // Kiểm tra có phải tài khoản Google không
    const isGoogleAccount = document.querySelector('.google-avatar-badge') !== null;
    
    if (isGoogleAccount) {
        // Nếu là tài khoản Google, không cho upload
        if (btnChangeAvatar) {
            btnChangeAvatar.style.display = 'none';
        }
        console.log('✓ Tài khoản Google - Avatar được đồng bộ tự động');
        return;
    }

    if (!btnChangeAvatar || !avatarInput) {
        console.log('⚠ Avatar upload elements not found');
        return;
    }

    // Click button to trigger file input
    btnChangeAvatar.addEventListener('click', function() {
        avatarInput.click();
    });

    // Handle file selection
    avatarInput.addEventListener('change', function() {
        const file = this.files[0];
        
        if (!file) return;

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showNotification('Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)', 'error');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('Kích thước file tối đa 5MB', 'error');
            return;
        }

        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            if (avatarPreview) {
                avatarPreview.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);

        // Upload file
        uploadAvatar(file);
    });
}

    function uploadAvatar(file) {
        const formData = new FormData();
        formData.append('avatar', file);

        // Show loading
        showLoadingOverlay('Đang tải ảnh lên...');

        fetch('upload_avatar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoadingOverlay();
            
            if (data.ok) {
                showNotification(data.message || 'Cập nhật avatar thành công!', 'success');
                
                // Update preview
                if (data.avatar_url) {
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            } else {
                showNotification(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(error => {
            hideLoadingOverlay();
            console.error('Upload error:', error);
            showNotification('Lỗi kết nối. Vui lòng thử lại!', 'error');
        });
    }

    // ===== LOADING OVERLAY =====
    function showLoadingOverlay(message = 'Đang xử lý...') {
        let overlay = document.querySelector('.upload-overlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'upload-overlay';
            overlay.innerHTML = `
                <div class="upload-spinner">
                    <i class="fa-solid fa-spinner"></i>
                    <p>${message}</p>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        
        overlay.classList.add('active');
    }

    function hideLoadingOverlay() {
        const overlay = document.querySelector('.upload-overlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
    }

    // ===== PASSWORD VALIDATION =====
    function initPasswordValidation() {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        if (!newPassword || !confirmPassword) return;

        confirmPassword.addEventListener('input', function() {
            if (this.value !== newPassword.value) {
                this.setCustomValidity('Mật khẩu xác nhận không khớp');
            } else {
                this.setCustomValidity('');
            }
        });

        newPassword.addEventListener('input', function() {
            if (confirmPassword.value && confirmPassword.value !== this.value) {
                confirmPassword.setCustomValidity('Mật khẩu xác nhận không khớp');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    }

    // ===== AUTO HIDE ALERTS =====
    function initAutoHideAlerts() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    }

    // ===== NOTIFICATION =====
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        const existing = document.querySelectorAll('.toast-notification');
        existing.forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = `toast-notification toast-${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-circle',
            info: 'fa-info-circle'
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
        }, 4000);
    }

    // Make showNotification available globally
    window.showNotification = showNotification;

});

// ===== BUILD MANAGEMENT FUNCTIONS =====

/**
 * Add build to cart
 */
function addBuildToCart(buildId) {
    if (!buildId) {
        showNotification('ID cấu hình không hợp lệ', 'error');
        return;
    }

    // Show loading
    const btn = event.target.closest('.btn-cart-add');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang thêm...';

    fetch('../api/add_build_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ build_id: buildId })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;

        if (data.ok) {
            showNotification('Đã thêm cấu hình vào giỏ hàng!', 'success');
            
            // Update cart count if element exists
            const cartCount = document.querySelector('.cart-count');
            if (cartCount && data.cart_count) {
                cartCount.textContent = data.cart_count;
                cartCount.classList.add('bounce');
                setTimeout(() => cartCount.classList.remove('bounce'), 500);
            }
        } else {
            showNotification(data.message || 'Không thể thêm vào giỏ hàng', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding build to cart:', error);
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        showNotification('Lỗi kết nối. Vui lòng thử lại!', 'error');
    });
}

/**
 * Delete build
 */
function deleteBuild(buildId) {
    if (!buildId) {
        showNotification('ID cấu hình không hợp lệ', 'error');
        return;
    }

    // Confirm deletion
    if (!confirm('Bạn có chắc chắn muốn xóa cấu hình này?')) {
        return;
    }

    // Show loading
    const btn = event.target.closest('.btn-delete-build');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    fetch('../api/delete_build.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ build_id: buildId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            showNotification('Đã xóa cấu hình thành công!', 'success');
            
            // Remove card from DOM with animation
            const buildCard = btn.closest('.build-card-account');
            if (buildCard) {
                buildCard.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    buildCard.remove();
                    
                    // Check if no builds left
                    const buildsGrid = document.querySelector('.builds-grid-account');
                    if (buildsGrid && buildsGrid.children.length === 0) {
                        location.reload(); // Reload to show empty state
                    }
                }, 300);
            }

            // Update builds count in sidebar
            const buildsCountBadge = document.querySelector('.menu-item[data-section="builds"] .badge');
            if (buildsCountBadge) {
                const currentCount = parseInt(buildsCountBadge.textContent);
                const newCount = currentCount - 1;
                if (newCount > 0) {
                    buildsCountBadge.textContent = newCount;
                } else {
                    buildsCountBadge.remove();
                }
            }

            // Update stat value
            const statValue = document.querySelector('.stats-summary .stat-item:nth-child(2) .stat-value');
            if (statValue) {
                const currentCount = parseInt(statValue.textContent);
                statValue.textContent = Math.max(0, currentCount - 1);
            }
        } else {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            showNotification(data.message || 'Không thể xóa cấu hình', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting build:', error);
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        showNotification('Lỗi kết nối. Vui lòng thử lại!', 'error');
    });
}

// Add fadeOut animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
    
    .cart-count.bounce {
        animation: bounce 0.5s ease;
    }
    
    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.3); }
    }
`;
document.head.appendChild(style);