/**
 * assets/js/auth.js - Authentication Pages Interactivity
 * Login & Register Pages
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== PASSWORD TOGGLE =====
    initPasswordToggles();
    
    // ===== PASSWORD STRENGTH CHECKER =====
    initPasswordStrength();
    
    // ===== FORM VALIDATION =====
    initFormValidation();
    
    // ===== AUTO HIDE ALERTS =====
    initAlertAutoHide();
    
    // ===== REMEMBER ME =====
    initRememberMe();
    
    // ===== PASSWORD TOGGLE =====
    function initPasswordToggles() {
        const toggleButtons = document.querySelectorAll('.password-toggle');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const wrapper = this.closest('.password-input-wrapper');
                const input = wrapper.querySelector('.form-input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    }
    
    // ===== PASSWORD STRENGTH =====
    function initPasswordStrength() {
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrength');
        
        if (!passwordInput || !strengthBar) return;
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password.length === 0) {
                strengthBar.classList.remove('active', 'weak', 'medium', 'strong');
                return;
            }
            
            strengthBar.classList.add('active');
            
            const strength = calculatePasswordStrength(password);
            
            strengthBar.classList.remove('weak', 'medium', 'strong');
            
            if (strength < 40) {
                strengthBar.classList.add('weak');
            } else if (strength < 70) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });
    }
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        // Length
        if (password.length >= 6) strength += 20;
        if (password.length >= 8) strength += 10;
        if (password.length >= 12) strength += 10;
        
        // Contains lowercase
        if (/[a-z]/.test(password)) strength += 15;
        
        // Contains uppercase
        if (/[A-Z]/.test(password)) strength += 15;
        
        // Contains numbers
        if (/\d/.test(password)) strength += 15;
        
        // Contains special characters
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 15;
        
        return strength;
    }
    
    // ===== FORM VALIDATION =====
    function initFormValidation() {
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        
        if (loginForm) {
            loginForm.addEventListener('submit', handleLoginSubmit);
        }
        
        if (registerForm) {
            registerForm.addEventListener('submit', handleRegisterSubmit);
            initRealTimeValidation(registerForm);
        }
    }
    
    function handleLoginSubmit(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        
        if (username === '' || password === '') {
            e.preventDefault();
            showFormError('Vui lòng nhập đầy đủ thông tin!');
            return false;
        }
        
        showLoadingState(this);
    }
    
    function handleRegisterSubmit(e) {
        const form = this;
        const password = document.getElementById('password').value;
        const passwordConfirm = document.getElementById('password_confirm').value;
        const terms = document.getElementById('termsCheckbox').checked;
        
        // Check password match
        if (password !== passwordConfirm) {
            e.preventDefault();
            showFormError('Mật khẩu xác nhận không khớp!');
            highlightError('password_confirm');
            return false;
        }
        
        // Check password length
        if (password.length < 6) {
            e.preventDefault();
            showFormError('Mật khẩu phải có ít nhất 6 ký tự!');
            highlightError('password');
            return false;
        }
        
        // Check terms
        if (!terms) {
            e.preventDefault();
            showFormError('Vui lòng đồng ý với điều khoản sử dụng!');
            return false;
        }
        
        showLoadingState(form);
    }
    
    function initRealTimeValidation(form) {
        const username = form.querySelector('#username');
        const email = form.querySelector('#email');
        const password = form.querySelector('#password');
        const passwordConfirm = form.querySelector('#password_confirm');
        
        // Username validation
        username?.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value.length > 0 && value.length < 3) {
                this.classList.add('error');
                showFieldError(this, 'Tên đăng nhập phải có ít nhất 3 ký tự');
            } else {
                this.classList.remove('error');
                removeFieldError(this);
            }
        });
        
        // Email validation
        email?.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value.length > 0 && !isValidEmail(value)) {
                this.classList.add('error');
                showFieldError(this, 'Email không hợp lệ');
            } else {
                this.classList.remove('error');
                removeFieldError(this);
            }
        });
        
        // Password confirmation match
        passwordConfirm?.addEventListener('input', function() {
            if (password.value === this.value && this.value.length > 0) {
                this.classList.add('success');
                this.classList.remove('error');
                removeFieldError(this);
            } else if (this.value.length > 0) {
                this.classList.add('error');
                this.classList.remove('success');
            }
        });
        
        // Clear error on input
        const inputs = form.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.classList.contains('error') && this.value.trim() !== '') {
                    this.classList.remove('error');
                    removeFieldError(this);
                }
            });
        });
    }
    
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    function showFieldError(input, message) {
        removeFieldError(input);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'form-error';
        errorDiv.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${message}`;
        
        input.parentElement.appendChild(errorDiv);
    }
    
    function removeFieldError(input) {
        const existingError = input.parentElement.querySelector('.form-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    function highlightError(inputId) {
        const input = document.getElementById(inputId);
        if (input) {
            input.classList.add('error');
            input.focus();
            
            setTimeout(() => {
                input.classList.remove('error');
            }, 3000);
        }
    }
    
    function showFormError(message) {
        const existingAlert = document.querySelector('.alert-error');
        
        if (existingAlert) {
            existingAlert.querySelector('span').textContent = message;
            existingAlert.style.animation = 'shake 0.3s ease';
            setTimeout(() => {
                existingAlert.style.animation = '';
            }, 300);
        } else {
            const alert = document.createElement('div');
            alert.className = 'alert alert-error';
            alert.innerHTML = `
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>${message}</span>
            `;
            
            const form = document.querySelector('.auth-form');
            form.parentElement.insertBefore(alert, form);
            
            setTimeout(() => {
                alert.style.animation = 'slideUp 0.3s ease-out reverse';
                setTimeout(() => alert.remove(), 300);
            }, 3000);
        }
    }
    
    function showLoadingState(form) {
        const submitBtn = form.querySelector('.btn-submit');
        if (submitBtn) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            const icon = submitBtn.querySelector('i');
            const originalIconClass = icon.className;
            icon.className = 'fa-solid fa-spinner';
            
            // Store original for restoration if needed
            submitBtn.dataset.originalIcon = originalIconClass;
        }
    }
    
    // ===== AUTO HIDE ALERTS =====
    function initAlertAutoHide() {
        const successAlert = document.getElementById('successAlert');
        
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.animation = 'slideUp 0.3s ease-out reverse';
                setTimeout(() => successAlert.remove(), 300);
            }, 5000);
        }
        
        const errorAlert = document.getElementById('errorAlert');
        
        if (errorAlert) {
            // Shake animation on load
            setTimeout(() => {
                errorAlert.style.animation = 'shake 0.3s ease';
            }, 100);
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                errorAlert.style.opacity = '0';
                errorAlert.style.transform = 'translateY(-10px)';
                errorAlert.style.transition = 'all 0.3s ease';
                setTimeout(() => errorAlert.remove(), 300);
            }, 5000);
        }
    }
    
    // ===== REMEMBER ME =====
    function initRememberMe() {
        const rememberCheckbox = document.getElementById('rememberMe');
        const usernameInput = document.getElementById('username');
        
        if (!rememberCheckbox || !usernameInput) return;
        
        // Load saved username
        const savedUsername = localStorage.getItem('remembered_username');
        if (savedUsername) {
            usernameInput.value = savedUsername;
            rememberCheckbox.checked = true;
        }
        
        // Save on form submit
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function() {
                if (rememberCheckbox.checked) {
                    localStorage.setItem('remembered_username', usernameInput.value);
                } else {
                    localStorage.removeItem('remembered_username');
                }
            });
        }
    }
    
    // ===== KEYBOARD SHORTCUTS =====
    document.addEventListener('keydown', function(e) {
        // ESC to clear form
        if (e.key === 'Escape') {
            const activeInput = document.activeElement;
            if (activeInput && activeInput.classList.contains('form-input')) {
                activeInput.blur();
            }
        }
    });
    
    // ===== FOCUS EFFECTS =====
    const formInputs = document.querySelectorAll('.form-input');
    
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
    
    // ===== PREVENT DOUBLE SUBMIT =====
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        let isSubmitting = false;
        
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
        });
    });
    
});

// ===== UTILITY FUNCTIONS =====
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

// ===== PAGE VISIBILITY =====
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Refresh CSRF token or session check if needed
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (form.classList.contains('loading')) {
                form.classList.remove('loading');
                const submitBtn = form.querySelector('.btn-submit');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                }
            }
        });
    }
});