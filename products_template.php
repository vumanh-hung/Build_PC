<?php

/**
 * products_template.php - Product Page Template
 * COMPLETE FIX: Both cancel and add to build buttons working
 */

// Include Header
include __DIR__ . '/../includes/header.php';
?>

<!-- ===== CRITICAL: INLINE JAVASCRIPT FUNCTIONS ===== -->
<script>
    // Define PRODUCTS_CONFIG first
    window.PRODUCTS_CONFIG = {
        CSRF_TOKEN: <?= json_encode($csrf) ?>,
        IS_BUILD_MODE: <?= $is_build_mode ? 'true' : 'false' ?>,
        BUILD_MODE: <?= json_encode($build_mode) ?>,
        BUILD_ID: <?= $build_id ?>,
        ITEM_ID: <?= $item_id ?>,
        IS_LOGGED_IN: <?= isLoggedIn() ? 'true' : 'false' ?>,
        REVIEW_SUCCESS: <?= $review_success ? 'true' : 'false' ?>
    };

    console.log('✅ Products Config Loaded:', window.PRODUCTS_CONFIG);

    // ===== UTILITY FUNCTIONS =====
    function showLoading(text) {
        text = text || 'Đang xử lý...';
        var loading = document.getElementById('loading');
        var loadingText = document.getElementById('loading-text');
        if (loading && loadingText) {
            loadingText.textContent = text;
            loading.classList.add('active');
        }
    }

    function hideLoading() {
        var loading = document.getElementById('loading');
        if (loading) {
            loading.classList.remove('active');
        }
    }

    function showToast(message, type) {
        type = type || 'success';
        console.log('🔔 Toast:', message, '(' + type + ')');

        var toast = document.getElementById('toast');
        if (toast) {
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(function() {
                toast.classList.remove('show');
            }, 3000);
        }
    }

    function playTingSound() {
        var sound = document.getElementById('tingSound');
        if (sound) {
            sound.currentTime = 0;
            sound.play().catch(function() {});
        }
    }

    // ===== CRITICAL FUNCTIONS =====
    window.cancelBuildMode = function() {
        console.log('🚫 cancelBuildMode called');

        try {
            sessionStorage.clear();
        } catch (e) {
            console.warn('SessionStorage error:', e);
        }

        var buildId = window.PRODUCTS_CONFIG.BUILD_ID || 0;

        if (buildId && buildId > 0) {
            window.location.href = 'build_manage.php?id=' + buildId;
        } else {
            window.location.href = 'products.php';
        }
    };

    // ===== SELECT PRODUCT FOR BUILD =====
    window.selectProductForBuild = function(productId, buttonElement) {
        console.log('🎯 selectProductForBuild called with productId:', productId);

        var config = window.PRODUCTS_CONFIG;
        var BUILD_ID = config.BUILD_ID;
        var BUILD_MODE = config.BUILD_MODE;
        var ITEM_ID = config.ITEM_ID;

        if (!BUILD_ID) {
            console.error('❌ No BUILD_ID');
            showToast('❌ Thiếu thông tin build!', 'error');
            return;
        }

        showLoading('Đang thêm vào build...');

        // Disable button
        if (buttonElement) {
            buttonElement.disabled = true;
            var originalHTML = buttonElement.innerHTML;
            buttonElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';
        }

        var apiUrl = '';
        var bodyData = {};

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
            if (buttonElement) {
                buttonElement.disabled = false;
                buttonElement.innerHTML = originalHTML;
            }
            return;
        }

        console.log('📡 API:', apiUrl);
        console.log('📦 Body:', bodyData);

        fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(bodyData),
                credentials: 'include'
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                console.log('📨 Response:', data);

                if (data.success) {
                    try {
                        sessionStorage.clear();
                    } catch (e) {}

                    hideLoading();
                    playTingSound();
                    showToast('✅ Đã thêm vào build!', 'success');

                    setTimeout(function() {
                        var successParam = BUILD_MODE === 'replace' ? 'replaced' : 'added';
                        window.location.href = 'build_manage.php?id=' + BUILD_ID + '&success=' + successParam;
                    }, 1000);
                } else {
                    hideLoading();
                    showToast('❌ ' + (data.error || 'Không thể xử lý'), 'error');
                    if (buttonElement) {
                        buttonElement.disabled = false;
                        buttonElement.innerHTML = originalHTML;
                    }
                }
            })
            .catch(function(error) {
                console.error('❌ Error:', error);
                hideLoading();
                showToast('❌ Lỗi kết nối!', 'error');
                if (buttonElement) {
                    buttonElement.disabled = false;
                    buttonElement.innerHTML = originalHTML;
                }
            });
    };

    // ===== REVIEW MODAL FUNCTIONS =====
    window.openReviewModal = function() {
        console.log('📝 openReviewModal called');

        if (!window.PRODUCTS_CONFIG.IS_LOGGED_IN) {
            window.location.href = 'login.php';
            return;
        }

        var modal = document.getElementById('reviewModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    };

    window.closeReviewModal = function() {
        console.log('❌ closeReviewModal called');
        var modal = document.getElementById('reviewModal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    window.setRating = function(rating, event) {
        if (event) {
            event.preventDefault();
        }

        var ratingValue = document.getElementById('ratingValue');
        if (ratingValue) {
            ratingValue.value = rating;
        }

        var buttons = document.querySelectorAll('.rating-btn');
        buttons.forEach(function(btn, i) {
            if (i + 1 <= rating) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    };

    window.updateCount = function(element, countId) {
        var counter = document.getElementById(countId);
        if (counter) {
            counter.textContent = element.value.length;
        }
    };

    window.previewReviewImages = function(files) {
        var container = document.getElementById('previewImages');
        if (!container) return;

        container.innerHTML = '';

        if (files.length > 5) {
            showToast('⚠️ Chỉ được chọn tối đa 5 ảnh', 'error');
            return;
        }

        Array.from(files).forEach(function(file, index) {
            if (file.size > 5000000) {
                showToast('⚠️ File ' + file.name + ' quá lớn (>5MB)', 'error');
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                var div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = '<img src="' + e.target.result + '" alt="Preview">' +
                    '<button type="button" class="remove-preview" onclick="removePreviewImage(' + index + ')">×</button>';
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    };

    window.removePreviewImage = function(index) {
        var input = document.getElementById('reviewImageInput');
        if (!input) return;

        var dt = new DataTransfer();
        var files = input.files;

        for (var i = 0; i < files.length; i++) {
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

        var files = event.dataTransfer.files;
        var input = document.getElementById('reviewImageInput');

        if (input) {
            input.files = files;
            window.previewReviewImages(files);
        }
    };

    console.log('✅ All critical functions defined');
    console.log('✅ cancelBuildMode:', typeof window.cancelBuildMode);
    console.log('✅ selectProductForBuild:', typeof window.selectProductForBuild);

    // ===== ATTACH EVENT LISTENERS WHEN DOM READY =====
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ DOM Ready - Attaching button listeners');

        // Init audio
        document.addEventListener('click', function() {
            var sound = document.getElementById('tingSound');
            if (sound && sound.paused) {
                sound.play().then(function() {
                    sound.pause();
                    sound.currentTime = 0;
                }).catch(function() {});
            }
        }, {
            once: true
        });

        // Attach select product buttons
        if (window.PRODUCTS_CONFIG.IS_BUILD_MODE) {
            setTimeout(function() {
                var buttons = document.querySelectorAll('.select-product-btn');
                console.log('🔘 Found ' + buttons.length + ' select buttons');

                buttons.forEach(function(button, index) {
                    var productId = button.getAttribute('data-product-id');
                    console.log('  Button ' + (index + 1) + ':', productId);

                    // Remove old listener
                    var newButton = button.cloneNode(true);
                    button.parentNode.replaceChild(newButton, button);

                    // Add new listener
                    newButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        var pid = parseInt(this.getAttribute('data-product-id'));
                        console.log('🎯 Button clicked! Product ID:', pid);

                        if (!pid || pid <= 0) {
                            showToast('❌ Sản phẩm không hợp lệ!', 'error');
                            return;
                        }

                        window.selectProductForBuild(pid, this);
                    });
                });

                console.log('✅ All buttons attached!');
            }, 300);
        }

        // Init quantity inputs
        var inputs = document.querySelectorAll('.qty-input');
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                var value = parseInt(this.value) || 1;
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
        var ratingButtons = document.querySelectorAll('.rating-btn');
        ratingButtons.forEach(function(btn, i) {
            if (i + 1 <= 5) btn.classList.add('active');
        });

        if (window.PRODUCTS_CONFIG.REVIEW_SUCCESS) {
            setTimeout(function() {
                window.closeReviewModal();
                location.reload();
            }, 2000);
        }

        window.onclick = function(event) {
            var modal = document.getElementById('reviewModal');
            if (event.target === modal) {
                window.closeReviewModal();
            }
        };

        console.log('✅ All initialized');
    });
</script>

<!-- ===== BUILD MODE BANNER ===== -->
<?php if ($is_build_mode): ?>
    <div id="build-mode-banner" class="build-mode-banner active">
        <div class="banner-content">
            <div class="banner-icon">
                <i class="fa fa-tools"></i>
            </div>
            <div class="banner-text">
                <div class="banner-title">
                    <?= $build_mode === 'replace' ? '🔄 Đang thay thế linh kiện' : '➕ Đang thêm linh kiện mới' ?>
                </div>
                <div class="banner-desc">Click vào nút "Chọn sản phẩm này" bên dưới sản phẩm bạn muốn</div>
            </div>
            <button class="banner-close" onclick="cancelBuildMode()">
                <i class="fa fa-times"></i> Hủy & Quay lại
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- ===== LOADING OVERLAY ===== -->
<div class="loading-overlay" id="loading">
    <div class="spinner"></div>
    <div class="loading-text" id="loading-text">Đang xử lý...</div>
</div>

<!-- ===== PAGE BANNER ===== -->
<?php if (!$is_build_mode): ?>
    <div class="page-banner">
        <div class="banner-content">
            <h1>Danh Sách Sản Phẩm</h1>
            <p>Tìm những sản phẩm công nghệ tốt nhất theo nhu cầu của bạn</p>
        </div>
    </div>
<?php endif; ?>

<!-- ===== MAIN CONTENT ===== -->
<div class="container">
    <h1 class="page-title">💻 Sản Phẩm</h1>

    <!-- ===== SEARCH & FILTER ===== -->
    <?php renderSearchForm($filters, $categories, $brands, $is_build_mode, $build_mode, $build_id, $item_id); ?>

    <!-- ===== PRODUCT LIST ===== -->
    <?php if (empty($products)): ?>
        <div class="product-grid">
            <div class="no-products">
                <i class="fa-solid fa-magnifying-glass"></i>
                <p>Không tìm thấy sản phẩm nào phù hợp.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php renderProducts($products, $is_build_mode, $build_mode, $build_id, $item_id); ?>
        </div>
    <?php endif; ?>
</div>

<!-- ===== REVIEWS SECTION ===== -->
<?php if (!$is_build_mode): ?>
    <?php renderReviewsSection($review_stats, $recent_reviews); ?>
    <?php renderReviewModal($review_success, $review_error); ?>
<?php endif; ?>

<!-- ===== CART POPUP ===== -->
<div id="cart-popup" class="cart-popup">
    <i class="fa-solid fa-check-circle"></i>
    <span id="popup-text">Đã thêm vào giỏ hàng!</span>
</div>

<!-- ===== AUDIO NOTIFICATION ===== -->
<audio id="tingSound" preload="auto">
    <source src="../uploads/sound/ting.mp3" type="audio/mpeg">
</audio>

<!-- ===== TOAST NOTIFICATION ===== -->
<div id="toast" class="toast"></div>

<?php
// Include Footer
include __DIR__ . '/../includes/footer.php';
?>