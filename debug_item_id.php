<?php
// debug_item_id.php
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>body{font-family:monospace;padding:40px;background:#1e1e1e;color:#d4d4d4;}</style>";
echo "</head><body>";

echo "<h2>Debug item_id from URL</h2>";

echo "<h3>$_GET array:</h3>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

$item_id = $_GET['item_id'] ?? '';
$build_id = $_GET['build_id'] ?? '';
$mode = $_GET['mode'] ?? '';

echo "<h3>Extracted values:</h3>";
echo "<p>item_id: <strong>" . htmlspecialchars($item_id) . "</strong> (type: " . gettype($item_id) . ")</p>";
echo "<p>build_id: <strong>" . htmlspecialchars($build_id) . "</strong> (type: " . gettype($build_id) . ")</p>";
echo "<p>mode: <strong>" . htmlspecialchars($mode) . "</strong></p>";

echo "<h3>JavaScript will see:</h3>";
?>
<script>
const ITEM_ID = <?= json_encode($item_id) ?>;
const BUILD_ID = <?= json_encode($build_id) ?>;
const BUILD_MODE = <?= json_encode($mode) ?>;

console.log('ITEM_ID:', ITEM_ID, 'type:', typeof ITEM_ID);
console.log('BUILD_ID:', BUILD_ID, 'type:', typeof BUILD_ID);
console.log('BUILD_MODE:', BUILD_MODE);

document.write('<pre>');
document.write('ITEM_ID: ' + ITEM_ID + ' (type: ' + typeof ITEM_ID + ')\n');
document.write('BUILD_ID: ' + BUILD_ID + ' (type: ' + typeof BUILD_ID + ')\n');
document.write('BUILD_MODE: ' + BUILD_MODE + '\n');
document.write('</pre>');

// Test parseInt
document.write('<h3>parseInt results:</h3>');
document.write('<pre>');
document.write('parseInt(ITEM_ID): ' + parseInt(ITEM_ID) + '\n');
document.write('parseInt(BUILD_ID): ' + parseInt(BUILD_ID) + '\n');
document.write('</pre>');

// Test condition
if (BUILD_MODE === 'replace' && ITEM_ID) {
  document.write('<p style="color:#4ec9b0;">✅ Condition PASSED: BUILD_MODE === "replace" && ITEM_ID</p>');
} else {
  document.write('<p style="color:#f48771;">❌ Condition FAILED</p>');
  document.write('<p>BUILD_MODE === "replace": ' + (BUILD_MODE === 'replace') + '</p>');
  document.write('<p>ITEM_ID truthy: ' + !!ITEM_ID + '</p>');
  document.write('<p>ITEM_ID value: "' + ITEM_ID + '"</p>');
}
</script>
<?php
echo "</body></html>";
?>