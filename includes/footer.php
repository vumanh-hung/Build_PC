<?php
/**
 * includes/footer.php - Global Footer
 * Mobile Bottom Nav & Search Overlay are in header.php
 */
?>
    </main>

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-top">
                <div class="footer-section">
                    <h3 class="footer-heading">
                        <i class="fa-solid fa-desktop"></i> BuildPC.vn
                    </h3>
                    <p class="footer-desc">
                        Hệ thống cấu hình và bán linh kiện máy tính chính hãng,
                        giá tốt nhất thị trường.
                    </p>
                    <div class="footer-social">
                        <a href="#" class="social-link" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#" class="social-link" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
                        <a href="#" class="social-link" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h4 class="footer-heading">Sản phẩm</h4>
                    <ul class="footer-links">
                        <li><a href="<?= $basePath ?>page/products.php">CPU</a></li>
                        <li><a href="<?= $basePath ?>page/products.php">Mainboard</a></li>
                        <li><a href="<?= $basePath ?>page/products.php">RAM</a></li>
                        <li><a href="<?= $basePath ?>page/products.php">VGA</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4 class="footer-heading">Hỗ trợ</h4>
                    <ul class="footer-links">
                        <li><a href="<?= $basePath ?>page/about.php">Giới thiệu</a></li>
                        <li><a href="<?= $basePath ?>page/contact.php">Liên hệ</a></li>
                        <li><a href="<?= $basePath ?>page/builds.php">Hướng dẫn build</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4 class="footer-heading">Liên hệ</h4>
                    <ul class="footer-contact">
                        <li><i class="fa-solid fa-location-dot"></i> <span>TP.HCM</span></li>
                        <li><i class="fa-solid fa-phone"></i> <span>1900 1234</span></li>
                        <li><i class="fa-solid fa-envelope"></i> <span>support@buildpc.vn</span></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-copyright">
                    <p>© <?= date('Y') ?> BuildPC.vn - Máy tính &amp; Linh kiện chính hãng</p>
                </div>
                <div class="footer-payment">
                    <span class="payment-label">Thanh toán:</span>
                    <div class="payment-methods">
                        <i class="fa-brands fa-cc-visa"></i>
                        <i class="fa-brands fa-cc-mastercard"></i>
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- CSS -->
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/footer.css?v=2.0">

    <!-- AI Chatbot Widget -->
    <link rel="stylesheet" href="<?= $basePath ?>assets/css/product_query.css?v=1.0" id="chatbot-css">
    <script src="<?= $basePath ?>assets/js/product_query.js?v=1.0" defer></script>

    <!-- Mobile nav ripple + scroll hide/show (footer-owned behaviour) -->
    <script>
    (function () {
        'use strict';

        // ── Ripple on bottom nav items ────────────────────────────────
        document.querySelectorAll('.mobile-nav-item').forEach(function (item) {
            item.addEventListener('click', function (e) {
                var ripple = document.createElement('span');
                ripple.className = 'ripple';
                var rect = this.getBoundingClientRect();
                var size = Math.max(rect.width, rect.height);
                ripple.style.cssText = [
                    'width:' + size + 'px',
                    'height:' + size + 'px',
                    'left:' + (e.clientX - rect.left - size / 2) + 'px',
                    'top:' + (e.clientY - rect.top - size / 2) + 'px'
                ].join(';');
                this.appendChild(ripple);
                setTimeout(function () { ripple.remove(); }, 600);
            });
        });

        // ── Hide bottom nav on scroll down, show on scroll up ─────────
        var lastScrollY = window.scrollY;
        var scrollTimer;
        var bottomNav = document.getElementById('mobileBottomNav');

        if (bottomNav && window.innerWidth <= 768) {
            window.addEventListener('scroll', function () {
                var y = window.scrollY;
                clearTimeout(scrollTimer);

                if (y > lastScrollY && y > 120) {
                    bottomNav.style.transform = 'translateY(100%)';
                } else {
                    bottomNav.style.transform = 'translateY(0)';
                }

                // Always show when near the bottom of page
                scrollTimer = setTimeout(function () {
                    if (window.innerHeight + y >= document.documentElement.scrollHeight - 100) {
                        bottomNav.style.transform = 'translateY(0)';
                    }
                }, 200);

                lastScrollY = y;
            }, { passive: true });
        }

        // ── Sync cart badge: header ↔ mobile bottom nav ───────────────
        var headerBadge = document.getElementById('headerCartCount');
        var mobileBadge = document.getElementById('mobileCartBadge');

        if (headerBadge && mobileBadge && window.MutationObserver) {
            new MutationObserver(function () {
                mobileBadge.textContent = headerBadge.textContent;
                mobileBadge.classList.add('updated');
                setTimeout(function () { mobileBadge.classList.remove('updated'); }, 500);
            }).observe(headerBadge, { childList: true, characterData: true, subtree: true });
        }

        // ── Page bottom padding: make room for bottom nav on mobile ───
        if (window.innerWidth <= 768) {
            document.body.style.paddingBottom = '72px';
        }
    })();
    </script>

</body>
</html>