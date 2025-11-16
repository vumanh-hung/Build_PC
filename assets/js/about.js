/**
 * about.js - About Page JavaScript
 * Client-side logic cho trang giới thiệu
 */

// ================================================
// ABOUT PAGE OBJECT
// ================================================

const AboutPage = {
    // Configuration
    config: {
        counterDuration: 2000,
        counterEasing: 'easeOutQuad'
    },
    
    // Elements
    elements: {
        statCards: null,
        featureCards: null,
        teamCards: null,
        missionCards: null
    },
    
    /**
     * Initialize the page
     */
    init() {
        console.log('📖 Initializing About Page...');
        
        // Cache DOM elements
        this.cacheElements();
        
        // Initialize AOS
        this.initAOS();
        
        // Setup counter animations
        this.setupCounterAnimations();
        
        // Setup interactive features
        this.setupInteractiveFeatures();
        
        // Setup parallax effects
        this.setupParallaxEffects();
        
        console.log('✅ About Page initialized');
    },
    
    /**
     * Cache DOM elements
     */
    cacheElements() {
        this.elements.statCards = document.querySelectorAll('.stat-card');
        this.elements.featureCards = document.querySelectorAll('.feature-card');
        this.elements.teamCards = document.querySelectorAll('.team-card');
        this.elements.missionCards = document.querySelectorAll('.mission-card');
    },
    
    /**
     * Initialize AOS animations
     */
    initAOS() {
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                easing: 'ease-out-cubic',
                once: true,
                offset: 50,
                delay: 0
            });
            console.log('✅ AOS initialized');
        } else {
            console.warn('⚠️ AOS library not loaded');
        }
    },
    
    /**
     * Setup counter animations for stats
     */
    setupCounterAnimations() {
        if (!this.elements.statCards || this.elements.statCards.length === 0) {
            console.log('⚠️ No stat cards found');
            return;
        }
        
        // Create Intersection Observer
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateStatCard(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.5,
            rootMargin: '0px'
        });
        
        // Observe all stat cards
        this.elements.statCards.forEach(card => {
            observer.observe(card);
        });
        
        console.log('✅ Counter animations setup');
    },
    
    /**
     * Animate a single stat card
     */
    animateStatCard(card) {
        const numberEl = card.querySelector('.stat-number');
        if (!numberEl) return;
        
        const targetText = numberEl.textContent;
        const hasPercent = targetText.includes('%');
        const hasPlus = targetText.includes('+');
        
        // Extract number
        const match = targetText.match(/(\d+,?\d*)/);
        if (!match) return;
        
        const targetNumber = parseInt(match[1].replace(/,/g, ''));
        
        // Animate counter
        this.animateCounter(numberEl, targetNumber, hasPercent, hasPlus);
        
        // Add animation class
        card.classList.add('animated');
    },
    
    /**
     * Animate counter from 0 to target
     */
    animateCounter(element, target, hasPercent = false, hasPlus = false) {
        const duration = this.config.counterDuration;
        const startTime = Date.now();
        
        const animate = () => {
            const currentTime = Date.now();
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function
            const eased = this.easeOutQuad(progress);
            const current = Math.floor(eased * target);
            
            // Format number
            let displayValue = current.toLocaleString('vi-VN');
            
            if (hasPlus && progress === 1) {
                displayValue += '+';
            }
            if (hasPercent && progress === 1) {
                displayValue += '%';
            }
            
            element.textContent = displayValue;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    },
    
    /**
     * Easing function - ease out quad
     */
    easeOutQuad(t) {
        return t * (2 - t);
    },
    
    /**
     * Setup interactive features
     */
    setupInteractiveFeatures() {
        // Add click tracking for team cards
        this.elements.teamCards.forEach((card, index) => {
            card.addEventListener('click', () => {
                this.trackTeamCardClick(index);
            });
        });
        
        // Add hover effects for feature cards
        this.elements.featureCards.forEach((card, index) => {
            card.addEventListener('mouseenter', () => {
                this.onFeatureCardHover(card, index);
            });
        });
        
        // Add click tracking for CTA buttons
        document.querySelectorAll('.btn-primary, .btn-secondary').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.trackButtonClick(e.currentTarget);
            });
        });
        
        console.log('✅ Interactive features setup');
    },
    
    /**
     * Track team card click
     */
    trackTeamCardClick(index) {
        console.log(`👤 Team card ${index} clicked`);
        
        // Track with Google Analytics if available
        if (typeof gtag !== 'undefined') {
            gtag('event', 'team_card_click', {
                'event_category': 'engagement',
                'event_label': `Team Member ${index + 1}`
            });
        }
    },
    
    /**
     * Handle feature card hover
     */
    onFeatureCardHover(card, index) {
        // Add ripple effect
        const icon = card.querySelector('.feature-icon');
        if (icon) {
            icon.style.animation = 'none';
            setTimeout(() => {
                icon.style.animation = 'pulse 0.6s ease';
            }, 10);
        }
    },
    
    /**
     * Track button click
     */
    trackButtonClick(button) {
        const buttonText = button.textContent.trim();
        console.log(`🔘 Button clicked: ${buttonText}`);
        
        // Track with Google Analytics if available
        if (typeof gtag !== 'undefined') {
            gtag('event', 'cta_click', {
                'event_category': 'engagement',
                'event_label': buttonText
            });
        }
    },
    
    /**
     * Setup parallax effects
     */
    setupParallaxEffects() {
        // Parallax for hero banner
        const heroBanner = document.querySelector('.hero-banner');
        
        if (heroBanner) {
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const parallax = scrolled * 0.5;
                
                heroBanner.style.transform = `translateY(${parallax}px)`;
            });
        }
        
        // Floating animation for about image
        const aboutImage = document.querySelector('.about-image img');
        if (aboutImage) {
            this.startFloatingAnimation(aboutImage);
        }
        
        console.log('✅ Parallax effects setup');
    },
    
    /**
     * Start floating animation
     */
    startFloatingAnimation(element) {
        let position = 0;
        let direction = 1;
        const speed = 0.5;
        const maxOffset = 20;
        
        const animate = () => {
            position += speed * direction;
            
            if (position >= maxOffset || position <= -maxOffset) {
                direction *= -1;
            }
            
            element.style.transform = `translateY(${position}px)`;
            requestAnimationFrame(animate);
        };
        
        requestAnimationFrame(animate);
    },
    
    /**
     * Scroll to section
     */
    scrollToSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    },
    
    /**
     * Show section with animation
     */
    showSection(sectionClass) {
        const section = document.querySelector(`.${sectionClass}`);
        if (section) {
            section.style.opacity = '0';
            section.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                section.style.transition = 'all 0.6s ease';
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, 100);
        }
    }
};

// ================================================
// UTILITY FUNCTIONS
// ================================================

/**
 * Format number with thousands separator
 */
function formatNumber(num) {
    return num.toLocaleString('vi-VN');
}

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
// SMOOTH SCROLL FOR ANCHOR LINKS
// ================================================

document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll for all anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// ================================================
// PERFORMANCE MONITORING
// ================================================

if ('PerformanceObserver' in window) {
    try {
        const observer = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.entryType === 'paint') {
                    console.log(`${entry.name}: ${entry.startTime.toFixed(2)}ms`);
                }
            }
        });
        
        observer.observe({ entryTypes: ['paint'] });
    } catch (e) {
        // Performance observer not supported
    }
}

// ================================================
// LAZY LOADING IMAGES
// ================================================

if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// ================================================
// INITIALIZE ON DOM READY
// ================================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => AboutPage.init());
} else {
    AboutPage.init();
}

// Export for use in other scripts or inline code
window.AboutPage = AboutPage;

console.log('📖 About.js loaded successfully');