<?php
// ============ CONFIG DATABASE ============
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'buildpc_db');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("❌ Lỗi kết nối DB: " . $e->getMessage());
}

// ============ DANH SÁCH LINK CẦN CRAWL ============
$links = [
    "https://nguyencongpc.vn/laptop-asus-vivobook-15-x1502va-bq886w-intel-core-i7-13620h",
    "https://nguyencongpc.vn/laptop-asus-vivobook-x1404va-nk394w-intel-core-i3-1315u",
    "https://nguyencongpc.vn/laptop-gigabyte-g6-mf-h2vn854kh",
    "https://nguyencongpc.vn/laptop-lenovo-thinkpad-e14-gen-4-21e300dtva",
    "https://nguyencongpc.vn/laptop-gigabyte-gaming-a16-cmhh2vn893sh",
    "https://nguyencongpc.vn/laptop-lenovo-loq-gaming-15arp9-ryzen-5-7235hs-12gb-512gb-ssd-rtx-4050-6gb-156-inch-fhd-144hz-win-11-luna-grey",
    "https://nguyencongpc.vn/laptop-lenovo-loq-15iax9e-core-i5-12450hx-12gb-512gb-rtx-2050-4gb-156-fhd-144hz-100-srgb-w11-home-en-luna",
    "https://nguyencongpc.vn/laptop-dell-vostro-3530-core-i3-1334u-ram-8gb-ssd-512gb",
    "https://nguyencongpc.vn/laptop-lenovo-ideapad-slim-3-15abr8-ryzen-7-5825u-16gb-512gb-ssd-radeon-graphics-156-inch-fhd-win-11-xam",
    "https://nguyencongpc.vn/laptop-gigabyte-g5-kf-e3vn333sh-i5-12500h-8gb-ram-512gb-ssd-156-fhd-144hz-rtx-4060-8gb-black-win11-2yrs",
    "https://nguyencongpc.vn/laptop-msi-gaming-gf63-thin-10uc-443vn-intel-core-i5-11400h-8gb-ram-512gb-ssd-156fhd-144hz-rtx3050-4gb-win-10-black-1-yr",
    "https://nguyencongpc.vn/laptop-hp-zbook-firefly-14-g8-1a2f1av-intel-core-i5-1135g7-8gb-ram-512gb-ssd-14-fhd-vga-on-windows-10-pro-64-fingerprint-silver-1-yr",
    "https://nguyencongpc.vn/cap-chuyen-doi-hdmi-to-vga",
    "https://nguyencongpc.vn/laptop-gigabyte-aorus-16x-asg-53vnc54sh",
    "https://nguyencongpc.vn/laptop-gigabyte-aorus-master-16-byh-c5vne64sh",
    "https://nguyencongpc.vn/laptop-gigabyte-gaming-a16-cthh3vn893sh",
    "https://nguyencongpc.vn/laptop-gigabyte-aero-x16-1vh93vnc94dh",
    "https://nguyencongpc.vn/laptop-hp-gaming-victus-15-fb1013dx-amd-ryzen-5-7535hs-8gb-ssd-512gb-rtx-2050-156-full-hd-ips-144hz-nk-bh-tai-nc",
    "https://nguyencongpc.vn/laptop-lenovo-yoga-7i-laptop-156-fhd-ips-led-i7-1165g7-12gb-512gb-win-11-home-nk-bh-tai-nc",
    "https://nguyencongpc.vn/laptop-dell-latitude-3420-l3420i5ssd-core-i5-1135g7-8gb-256gb-intel-iris-xe-140-inch-hd-fedora-den",
    "https://nguyencongpc.vn/laptop-lenovo-ideapad-slim-3-15irh8-83em00h6incore-i7-13620h-16gb-ram-512gb-ssd-156-fhd-144hz-arctic-grey",
    "https://nguyencongpc.vn/laptop-gigabyte-g6-kf-h3vn853kh",
    "https://nguyencongpc.vn/laptop-dell-vostro-3530-core-i3-1305u-ram-8gb-ssd-512gb",
    "https://nguyencongpc.vn/laptop-lenovo-ideapad-slim-5-16iah8-83bg001xvn",
    "https://nguyencongpc.vn/laptop-lenovo-loq-15irx9-83dv00ervn",
    "https://nguyencongpc.vn/laptop-gigabyte-g6-mf-72vn854kh",
    "https://nguyencongpc.vn/laptop-lenovo-loq-15iax9-core-i5-12450hx-rtx-2050-4gb-156-inch-fhd-16gb-512gb-win-11-xam",
    "https://nguyencongpc.vn/bo-luu-dien-ups-santak-tg500-500va",
];

function fetchHtml($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function getProductImage($html, $baseUrl) {
    if (preg_match('/<img[^>]*class="[^"]*product[^"]*"[^>]*src="([^"]+)"/', $html, $m) ||
        preg_match('/<img[^>]*src="([^"]+)"[^>]*class="[^"]*product[^"]*"/', $html, $m)) {
        $imgUrl = $m[1];
    }
    elseif (preg_match('/<img[^>]*class="[^"]*product[^"]*"[^>]*data-src="([^"]+)"/', $html, $m)) {
        $imgUrl = $m[1];
    }
    elseif (preg_match('/<img[^>]*src="([^"]+(?:product|item|goods)[^"]*)"/', $html, $m)) {
        $imgUrl = $m[1];
    }
    elseif (preg_match_all('/<img[^>]*src="([^"]+\.(?:jpg|jpeg|png|webp))"[^>]*alt="([^"]*)"?/i', $html, $matches)) {
        foreach ($matches[1] as $url) {
            if (strpos($url, 'media') !== false && strpos($url, 'category') === false) {
                $imgUrl = $url;
                break;
            }
        }
        if (!isset($imgUrl)) $imgUrl = $matches[1][0] ?? false;
    }
    else {
        return false;
    }

    if (empty($imgUrl)) return false;

    if (strpos($imgUrl, 'http') !== 0) {
        $parsedUrl = parse_url($baseUrl);
        $domain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $imgUrl = rtrim($domain, '/') . '/' . ltrim($imgUrl, '/');
    }

    return $imgUrl;
}

function downloadImage($imgUrl, $filePath) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $imgUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => "Mozilla/5.0",
        CURLOPT_TIMEOUT => 10
    ]);
    
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return false;
    return file_put_contents($filePath, $imageData) !== false;
}

// ============ MAIN SYNC ============

$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$success = 0;
$failed = 0;
$productId = 10; // Bắt đầu từ product_id = 10 (như trong DB của bạn)

echo "🔄 BẮT ĐẦU ĐỒNG BỘ ẢNH...\n\n";

foreach ($links as $index => $url) {
    echo "[$productId] Đang xử lý: $url\n";

    try {
        // 1. Crawl ảnh từ URL
        $html = fetchHtml($url);
        if (!$html) throw new Exception("Không thể fetch HTML");

        $imgUrl = getProductImage($html, $url);
        if (!$imgUrl) throw new Exception("Không tìm thấy ảnh");

        echo "   📷 URL ảnh: $imgUrl\n";

        // 2. Tạo tên file
        $fileExt = pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        $fileExt = $fileExt ?: 'jpg';
        $fileName = "product_" . $productId . "." . $fileExt;
        $filePath = $uploadDir . $fileName;

        // 3. Download ảnh
        if (!downloadImage($imgUrl, $filePath)) {
            throw new Exception("Lỗi download ảnh");
        }

        // 4. Lưu đường dẫn vào database
        $imagePath = "uploads/" . $fileName;
        
        // Kiểm tra sản phẩm có tồn tại không
        $checkStmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = :id");
        $checkStmt->execute([':id' => $productId]);
        
        if ($checkStmt->rowCount() > 0) {
            // Update ảnh vào cột 'image' của bảng products
            $updateStmt = $pdo->prepare("
                UPDATE products 
                SET image = :image, updated_at = NOW() 
                WHERE product_id = :id
            ");
            
            $updateStmt->execute([
                ':image' => $imagePath,
                ':id' => $productId
            ]);

            echo "   ✅ Lưu vào DB: $imagePath\n\n";
            $success++;
        } else {
            echo "   ⚠️  Sản phẩm ID $productId không tồn tại trong DB\n";
            echo "   💾 Ảnh đã lưu vào: $filePath\n\n";
            $failed++;
        }

    } catch (Exception $e) {
        echo "   ❌ Lỗi: " . $e->getMessage() . "\n\n";
        $failed++;
    }

    $productId++;
}

echo "==========================================\n";
echo "📊 KẾT QUẢ ĐỒNG BỘ\n";
echo "==========================================\n";
echo "✅ Thành công: $success\n";
echo "❌ Thất bại: $failed\n";
echo "📁 Thư mục ảnh: $uploadDir\n";
echo "💾 Database: " . DB_NAME . "\n";
echo "==========================================\n";