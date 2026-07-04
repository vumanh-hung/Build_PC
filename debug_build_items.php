<?php
// Debug script: Check build items
// Usage: php debug_build_items.php?build_id=X

require_once __DIR__ . '/../db.php';

$build_id = isset($_GET['build_id']) ? intval($_GET['build_id']) : 0;

if (!$build_id) {
    echo "Usage: ?build_id=X\n";
    exit;
}

try {
    $pdo = getPDO();
    
    echo "===== DEBUG BUILD ITEMS =====\n\n";
    echo "Build ID: $build_id\n\n";
    
    // Check if build exists
    $stmt = $pdo->prepare("SELECT * FROM builds WHERE build_id = :id");
    $stmt->execute([':id' => $build_id]);
    $build = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$build) {
        echo "❌ Build NOT FOUND!\n";
        exit;
    }
    
    echo "✅ Build exists:\n";
    echo "   Name: {$build['name']}\n";
    echo "   Total: {$build['total_price']}\n";
    echo "   Created: {$build['created_at']}\n\n";
    
    // Check build_items
    $stmt = $pdo->prepare("
        SELECT 
            bi.*,
            p.name as product_name,
            p.price,
            c.name as category_name
        FROM build_items bi
        LEFT JOIN products p ON bi.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE bi.build_id = :id
    ");
    $stmt->execute([':id' => $build_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Build Items Count: " . count($items) . "\n\n";
    
    if (empty($items)) {
        echo "❌ NO ITEMS FOUND!\n";
        echo "\nPossible causes:\n";
        echo "1. Products not added to build_items table\n";
        echo "2. Wrong build_id\n";
        echo "3. Items were deleted\n";
    } else {
        echo "✅ Items found:\n\n";
        foreach ($items as $item) {
            echo "Item #{$item['item_id']}:\n";
            echo "  Product ID: {$item['product_id']}\n";
            echo "  Product Name: {$item['product_name']}\n";
            echo "  Category: {$item['category_name']}\n";
            echo "  Price: {$item['price']}\n";
            echo "  Quantity: {$item['quantity']}\n";
            echo "\n";
        }
    }
    
    // Check if get_build_items.php would work
    echo "===== API SIMULATION =====\n\n";
    
    if (empty($items)) {
        echo "get_build_items.php would return:\n";
        echo json_encode([
            'success' => true,
            'items' => [],
            'count' => 0
        ], JSON_PRETTY_PRINT);
        echo "\n\n";
        echo "❌ This would trigger 'Chưa chọn linh kiện' error!\n";
    } else {
        $api_items = array_map(function($item) {
            return [
                'item_id' => $item['item_id'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'product_name' => $item['product_name'],
                'price' => $item['price'],
                'category_name' => $item['category_name']
            ];
        }, $items);
        
        echo "get_build_items.php would return:\n";
        echo json_encode([
            'success' => true,
            'items' => $api_items,
            'count' => count($api_items)
        ], JSON_PRETTY_PRINT);
        echo "\n\n";
        echo "✅ This should work!\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}