/**
 * assets/js/contact.js - Contact Page Interactive Features
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== FORM VALIDATION & ENHANCEMENT =====
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        // Real-time validation
        const inputs = contactForm.querySelectorAll('input, textarea');
        
        inputs.forEach(input => {
            // Add focus animation
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
                validateField(this);
            });
            
            // Real-time input validation
            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    validateField(this);
                }
            });
        });
        
        // Form submit handling
        contactForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Vui lòng kiểm tra lại thông tin đã nhập!', 'error');
            } else {
                // Show loading state
                const submitBtn = this.querySelector('.submit-btn');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Đang gửi...</span>';
                
                // Reset after form processes (if validation fails server-side)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 3000);
            }
        });
    }
    
    // ===== FIELD VALIDATION =====
    function validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        let errorMessage = '';
        
        // Remove previous error
        removeFieldError(field);
        
        // Required field check
        if (value === '') {
            errorMessage = 'Trường này không được để trống';
        }
        // Email validation
        else if (fieldName === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                errorMessage = 'Email không hợp lệ';
            }
        }
        // Name validation
        else if (fieldName === 'name') {
            if (value.length < 2) {
                errorMessage = 'Tên phải có ít nhất 2 ký tự';
            }
        }
        // Message validation
        else if (fieldName === 'message') {
            if (value.length < 10) {
                errorMessage = 'Nội dung phải có ít nhất 10 ký tự';
            }
        }
        
        if (errorMessage) {
            showFieldError(field, errorMessage);
            return false;
        }
        
        field.classList.add('valid');
        return true;
    }
    
    function showFieldError(field, message) {
        field.classList.add('error');
        field.classList.remove('valid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${message}`;
        
        field.parentElement.appendChild(errorDiv);
    }
    
    function removeFieldError(field) {
        field.classList.remove('error');
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    // ===== NOTIFICATION SYSTEM =====
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const icon = type === 'success' ? 'fa-circle-check' : 
                     type === 'error' ? 'fa-circle-exclamation' : 'fa-info-circle';
        
        notification.innerHTML = `
            <i class="fa-solid ${icon}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        // Trigger animation
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Auto remove
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }
    
    // ===== MAP INTERACTION =====
    const mapContainer = document.querySelector('.map-container');
    
    if (mapContainer) {
        // Prevent scrolling interference
        const mapIframe = mapContainer.querySelector('iframe');
        
        if (mapIframe) {
            // Disable pointer events until clicked
            mapIframe.style.pointerEvents = 'none';
            
            mapContainer.addEventListener('click', function() {
                mapIframe.style.pointerEvents = 'auto';
            });
            
            // Re-disable when mouse leaves
            mapContainer.addEventListener('mouseleave', function() {
                mapIframe.style.pointerEvents = 'none';
            });
        }
    }
    
    // ===== INFO CARD ANIMATIONS =====
    const infoItems = document.querySelectorAll('.info-item');
    
    if (infoItems.length > 0) {
        const observerOptions = {
            threshold: 0.2,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 100);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        infoItems.forEach(item => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            item.style.transition = 'all 0.5s ease';
            observer.observe(item);
        });
    }
    
    // ===== PHONE NUMBER FORMATTING =====
    const phoneLinks = document.querySelectorAll('a[href^="tel:"]');
    
    phoneLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add visual feedback
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 200);
        });
    });
    
    // ===== AUTO-SCROLL TO FORM ON ERROR =====
    const alertError = document.querySelector('.alert-error');
    
    if (alertError) {
        setTimeout(() => {
            alertError.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 100);
    }
    
    // ===== AUTO-HIDE SUCCESS MESSAGE =====
    const alertSuccess = document.querySelector('.alert-success');
    
    if (alertSuccess) {
        setTimeout(() => {
            alertSuccess.style.transition = 'all 0.5s ease';
            alertSuccess.style.opacity = '0';
            alertSuccess.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                alertSuccess.remove();
            }, 500);
        }, 8000);
    }
    
    // ===== CHARACTER COUNTER FOR MESSAGE =====
    const messageTextarea = document.querySelector('textarea[name="message"]');
    
    if (messageTextarea) {
        const maxLength = 1000;
        const counterDiv = document.createElement('div');
        counterDiv.className = 'char-counter';
        counterDiv.textContent = `0 / ${maxLength}`;
        
        messageTextarea.setAttribute('maxlength', maxLength);
        messageTextarea.parentElement.appendChild(counterDiv);
        
        messageTextarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            counterDiv.textContent = `${currentLength} / ${maxLength}`;
            
            if (currentLength > maxLength * 0.9) {
                counterDiv.style.color = '#ff9800';
            } else {
                counterDiv.style.color = '#999';
            }
        });
    }
    
    // ===== COPY EMAIL TO CLIPBOARD =====
    const emailLinks = document.querySelectorAll('a[href^="mailto:"]');
    
    emailLinks.forEach(link => {
        // Add copy icon
        const copyIcon = document.createElement('i');
        copyIcon.className = 'fa-solid fa-copy copy-email-icon';
        copyIcon.style.marginLeft = '8px';
        copyIcon.style.cursor = 'pointer';
        copyIcon.style.fontSize = '14px';
        copyIcon.title = 'Sao chép email';
        
        link.appendChild(copyIcon);
        
        copyIcon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const email = link.textContent.replace('📧', '').trim();
            navigator.clipboard.writeText(email).then(() => {
                showNotification('Đã sao chép email!', 'success');
                this.className = 'fa-solid fa-check copy-email-icon';
                
                setTimeout(() => {
                    this.className = 'fa-solid fa-copy copy-email-icon';
                }, 2000);
            });
        });
    });
    
});

// ===== ADD CUSTOM STYLES FOR DYNAMIC ELEMENTS =====
const style = document.createElement('style');
style.textContent = `
    .field-error {
        color: #dc3545;
        font-size: 0.85rem;
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
        animation: shake 0.3s ease;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .form-group input.error,
    .form-group textarea.error {
        border-color: #dc3545;
        background: #fff5f5;
    }
    
    .form-group input.valid,
    .form-group textarea.valid {
        border-color: #28a745;
    }
    
    .form-group.focused label {
        color: #1a5fc9;
    }
    
    .notification {
        position: fixed;
        top: 20px;
        right: -400px;
        background: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
        max-width: 350px;
    }
    
    .notification.show {
        right: 20px;
    }
    
    .notification-success {
        border-left: 4px solid #28a745;
        color: #155724;
    }
    
    .notification-error {
        border-left: 4px solid #dc3545;
        color: #721c24;
    }
    
    .notification-info {
        border-left: 4px solid #2e8bfa;
        color: #004085;
    }
    
    .notification i {
        font-size: 20px;
    }
    
    .char-counter {
        text-align: right;
        font-size: 0.85rem;
        color: #999;
        margin-top: 6px;
        font-weight: 500;
    }
    
    .copy-email-icon {
        transition: all 0.3s ease;
        opacity: 0.6;
    }
    
    .copy-email-icon:hover {
        opacity: 1;
        color: #2e8bfa;
    }
`;
document.head.appendChild(style);