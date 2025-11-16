<?php
/**
 * includes/footer.php - Optimized Global Footer
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
                    <p>© <?= date('Y') ?> BuildPC.vn - Máy tính & Linh kiện chính hãng</p>
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

        <button class="back-to-top" id="backToTop">
            <i class="fa-solid fa-chevron-up"></i>
        </button>
    </footer>

    <link rel="stylesheet" href="<?= $basePath ?>assets/css/footer.css?v=1.0">
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('backToTop');
            window.addEventListener('scroll', () => {
                btn.classList.toggle('visible', window.pageYOffset > 300);
            });
            btn?.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>