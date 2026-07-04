<?php
/**
 * api/chatbot.php - AI Chatbot Backend
 * Dùng Google Gemini API để tư vấn sản phẩm và hỗ trợ khách hàng
 */

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ===== Lấy dữ liệu từ request =====
$body = json_decode(file_get_contents('php://input'), true);
$message    = trim($body['message'] ?? '');
$history    = $body['history'] ?? [];  // Lịch sử hội thoại trước
$currentProduct = $body['currentProduct'] ?? null;

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tin nhắn không được để trống']);
    exit;
}

// ===== Lấy thông tin sản phẩm từ DB =====
function getProductContext(PDO $pdo, string $keyword = ''): string {
    try {
        // Lấy danh mục
        $cats = $pdo->query("SELECT name FROM categories ORDER BY name LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
        $catList = implode(', ', $cats);

        // Tìm sản phẩm theo keyword (nếu có)
        if (!empty($keyword)) {
            $stmt = $pdo->prepare("
                SELECT p.name, c.name AS category, b.name AS brand, p.price, p.stock
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN brands b     ON p.brand_id = b.brand_id
                WHERE (p.name LIKE ? OR c.name LIKE ? OR b.name LIKE ?)
                  AND p.stock > 0
                ORDER BY p.price ASC
                LIMIT 8
            ");
            $kw = "%$keyword%";
            $stmt->execute([$kw, $kw, $kw]);
        } else {
            // Lấy mẫu sản phẩm đại diện mỗi danh mục
            $stmt = $pdo->query("
                SELECT p.name, c.name AS category, b.name AS brand, p.price, p.stock
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN brands b     ON p.brand_id = b.brand_id
                WHERE p.stock > 0
                ORDER BY p.category_id, p.price ASC
                LIMIT 20
            ");
        }

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($products)) {
            return "Danh mục có: $catList. Hiện không có sản phẩm cụ thể phù hợp.";
        }

        $lines = ["Danh mục sản phẩm: $catList", "Sản phẩm hiện có (còn hàng):"];
        foreach ($products as $p) {
            $price = number_format($p['price'], 0, ',', '.');
            $lines[] = "- [{$p['category']}] {$p['name']} ({$p['brand']}) - {$price}₫ | Tồn: {$p['stock']}";
        }
        return implode("\n", $lines);
    } catch (\Throwable $e) {
        return "Cửa hàng bán linh kiện máy tính đầy đủ các loại: CPU, GPU, RAM, Mainboard, SSD, PSU, Case, Tản nhiệt.";
    }
}

// ===== Trích xuất keyword từ tin nhắn =====
function extractKeyword(string $message): string {
    $pcParts = ['cpu', 'gpu', 'vga', 'ram', 'ssd', 'nvme', 'mainboard', 'case', 'psu', 'nguồn',
                'tản nhiệt', 'cooler', 'monitor', 'màn hình', 'intel', 'amd', 'nvidia',
                'i3', 'i5', 'i7', 'i9', 'ryzen', 'rtx', 'rx', 'ddr', 'asus', 'msi', 'gigabyte'];
    $msg = mb_strtolower($message);
    foreach ($pcParts as $part) {
        if (str_contains($msg, $part)) {
            return $part;
        }
    }
    return '';
}

// ===== Xây dựng system prompt =====
$pdo = getPDO();
$keyword       = extractKeyword($message);
$productContext = getProductContext($pdo, $keyword);

$productContextString = "";
if (!empty($currentProduct) && !empty($currentProduct['PRODUCT_NAME'])) {
    $formattedPrice = number_format($currentProduct['PRICE'] ?? 0, 0, ',', '.');
    $specs = "";
    if (!empty($currentProduct['SPECS']) && is_array($currentProduct['SPECS'])) {
        foreach ($currentProduct['SPECS'] as $s) {
            $specs .= "- {$s['name']}: {$s['value']}\n";
        }
    }
    $productContextString = "\n**[QUAN TRỌNG] Sản phẩm Khách hàng đang xem trực tiếp tại trang hiện tại:**\n";
    $productContextString .= "- Tên sản phẩm: {$currentProduct['PRODUCT_NAME']}\n";
    $productContextString .= "- Thương hiệu: " . ($currentProduct['BRAND'] ?? 'Không rõ') . "\n";
    $productContextString .= "- Danh mục: " . ($currentProduct['CATEGORY'] ?? 'Không rõ') . "\n";
    $productContextString .= "- Giá bán: {$formattedPrice}₫\n";
    $productContextString .= "- Tồn kho: " . ($currentProduct['STOCK'] ?? 0) . " sản phẩm\n";
    if (!empty($specs)) {
        $productContextString .= "- Thông số chi tiết:\n" . $specs;
    }
}

$systemPrompt = <<<PROMPT
Bạn là trợ lý AI của cửa hàng **BuildPC.vn** – chuyên tư vấn linh kiện máy tính và xây dựng cấu hình PC.

**Nhiệm vụ của bạn:**
1. Trả lời mọi câu hỏi của khách hàng (bao gồm tư vấn build PC, thông tin sản phẩm, cũng như các câu hỏi kiến thức chung, kỹ thuật, đời sống...).
2. Nếu khách hỏi về mua sắm hoặc build PC, hãy giới thiệu sản phẩm phù hợp từ danh sách hàng thực tế của cửa hàng.
3. Trả lời câu hỏi về dịch vụ của cửa hàng như bảo hành, vận chuyển, thanh toán, đổi trả khi được hỏi.

**Thông tin thực tế của cửa hàng:**
$productContext
$productContextString

**Thông tin cửa hàng:**
- Địa chỉ: TP.HCM
- Hotline: 1900 1234
- Email: support@buildpc.vn
- Thanh toán: COD, chuyển khoản, VNPay, MoMo, ZaloPay
- Bảo hành: Theo hãng, 12-36 tháng tùy linh kiện
- Vận chuyển: Giao toàn quốc, nội thành HCM giao trong ngày

**Cách trả lời:**
- Ngắn gọn, thân thiện, dùng tiếng Việt.
- Dùng emoji phù hợp để sinh động.
- Khi gợi ý build PC, liệt kê rõ từng linh kiện và giá ước tính.
- Không tự chế thông tin sản phẩm hoặc giá cả không có thật của cửa hàng. Đối với các câu hỏi ngoài phạm vi cửa hàng, hãy trả lời một cách thông thái và chính xác nhất dựa trên hiểu biết của bạn.
PROMPT;

// ===== Xây dựng payload Gemini =====
$contents = [];

// System instruction (Gemini dùng systemInstruction cho API v1)
$payload = [
    'systemInstruction' => [
        'parts' => [['text' => $systemPrompt]]
    ],
    'contents'          => [],
    'generationConfig'  => [
        'temperature'     => 0.7,
        'maxOutputTokens' => 800,
        'topP'            => 0.8,
    ]
];

// Thêm lịch sử hội thoại
foreach ($history as $turn) {
    if (!empty($turn['role']) && !empty($turn['text'])) {
        $payload['contents'][] = [
            'role'  => $turn['role'] === 'user' ? 'user' : 'model',
            'parts' => [['text' => $turn['text']]]
        ];
    }
}

// Thêm tin nhắn hiện tại
$payload['contents'][] = [
    'role'  => 'user',
    'parts' => [['text' => $message]]
];

// ===== Gọi Gemini API =====
$apiKey  = GEMINI_API_KEY;
$model   = GEMINI_MODEL;
$apiUrl  = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response   = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(503);
    echo json_encode(['error' => 'Không thể kết nối đến AI server: ' . $curlError]);
    exit;
}

$data = json_decode($response, true);

// Xử lý response
if ($httpCode !== 200 || empty($data['candidates'][0]['content']['parts'][0]['text'])) {
    $errMsg = $data['error']['message'] ?? 'Không có phản hồi từ AI';
    http_response_code(502);
    echo json_encode(['error' => $errMsg, 'debug' => $data]);
    exit;
}

$reply = $data['candidates'][0]['content']['parts'][0]['text'];

echo json_encode([
    'reply'    => $reply,
    'keyword'  => $keyword,
    'tokens'   => $data['usageMetadata'] ?? null,
], JSON_UNESCAPED_UNICODE);
