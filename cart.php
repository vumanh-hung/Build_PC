<?php
// cart.php (cập nhật để hỗ trợ JSON khi gọi AJAX)
// === KHỞI ĐỘNG PHIÊN LÀM VIỆC (SESSION) ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// helper: tính số lượng cart hiện tại
function getCartCountFromSession() {
    $count = 0;
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $it) {
            if (is_array($it) && isset($it['quantity'])) {
                $count += (int)$it['quantity'];
            } else {
                $count += (int)$it;
            }
        }
    }
    return $count;
}

// helper: trả JSON response cho AJAX
function ajaxResponse(array $data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// === XỬ LÝ ĐƯỜNG DẪN TƯƠNG ĐỐI ===
$basePath = (strpos($_SERVER['PHP_SELF'], '/page/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false)
    ? '../'
    : './';

// include DB nếu cần (nếu file nằm khác thư mục, sửa path)
require_once $basePath . 'db.php';
include $basePath . 'includes/header.php';

// ====== Khởi tạo giỏ hàng ======
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// helper: kiểm tra xem request này muốn JSON không (ajax=1 || Accept ứng dụng JSON)
function isAjaxRequest() {
    if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == '1') return true;
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (strpos($accept, 'application/json') !== false) return true;
    return false;
}

// ====== XỬ LÝ HÀNH ĐỘNG ======
$ajax = isAjaxRequest();

// Add (GET or POST support)
if ((isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id'])) ||
    (isset($_POST['action']) && $_POST['action'] === 'add' && isset($_POST['id']))) {

    $id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['id']);

    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $found = false;

        // Nếu sản phẩm đã có trong giỏ thì tăng số lượng
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                $item['quantity']++;
                $found = true;
                break;
            }
        }

        // Nếu chưa có thì thêm mới
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $product['product_id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => 1
            ];
        }
    }

    // Trả JSON nếu AJAX, ngược lại redirect
    if ($ajax) {
        ajaxResponse(['ok' => true, 'cart_count' => getCartCountFromSession()]);
    } else {
        header("Location: cart.php");
        exit;
    }
}

// ====== Xóa sản phẩm ======
if ((isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) ||
    (isset($_POST['action']) && $_POST['action'] === 'remove' && isset($_POST['id']))) {

    $id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['id']);
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($item) => $item['id'] != $id));

    if ($ajax) {
        ajaxResponse(['ok' => true, 'cart_count' => getCartCountFromSession()]);
    } else {
        header("Location: cart.php");
        exit;
    }
}

// ====== Cập nhật số lượng (POST - form submit or AJAX) ======
if ((isset($_POST['update_cart']) && !isset($_POST['ajax'])) || (isset($_POST['action']) && $_POST['action'] === 'update')) {
    // nếu POST từ form (update_cart) hoặc AJAX với action=update
    $items = $_POST['qty'] ?? ($_POST['items'] ?? null);

    if (is_array($items)) {
        // items có dạng qty[id] => value hoặc items[pid] => qty
        // chuẩn hoá
        foreach ($items as $id => $qty) {
            $id = intval($id); $qty = max(1, intval($qty));
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $id) {
                    $item['quantity'] = $qty;
                    break;
                }
            }
        }
    }

    if ($ajax) {
        ajaxResponse(['ok' => true, 'cart_count' => getCartCountFromSession()]);
    } else {
        header("Location: cart.php");
        exit;
    }
}

// ====== Xóa toàn bộ giỏ hàng ======
if ((isset($_GET['action']) && $_GET['action'] === 'clear') || (isset($_POST['action']) && $_POST['action'] === 'clear')) {
    unset($_SESSION['cart']);
    if ($ajax) {
        ajaxResponse(['ok' => true, 'cart_count' => 0]);
    } else {
        header("Location: cart.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Giỏ hàng - BuildPC.vn</title>
<style>
/* (giữ CSS nguyên bản của bạn) */
body { font-family: "Segoe UI", sans-serif; background: #f7faff; margin: 0; padding: 0; }
.container { max-width: 1100px; margin: 40px auto; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 30px; }
h1 { color: #007bff; text-align: center; margin-bottom: 30px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 14px; border-bottom: 1px solid #ddd; text-align: center; }
th { background: #007bff; color: white; }
td img { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; }
td input[type="number"] { width: 60px; text-align: center; border: 1px solid #ccc; border-radius: 4px; padding: 5px; }
.total { text-align: right; font-size: 18px; font-weight: bold; color: #007bff; margin-top: 20px; }
.btn { padding: 10px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
.btn-update { background: #007bff; color: white; }
.btn-clear { background: #ff4d4d; color: white; }
.btn-checkout { background: #28a745; color: white; float: right; }
.empty { text-align: center; color: #666; font-size: 18px; padding: 50px 0; }
</style>
</head>

<body>
<div class="container">
  <h1>🛒 Giỏ hàng của bạn</h1>

  <?php if (empty($_SESSION['cart'])): ?>
    <p class="empty">Giỏ hàng trống. <a href="<?php echo $basePath; ?>index.php">Mua sắm ngay!</a></p>
  <?php else: ?>
    <form method="post" id="cart-form">
      <table>
        <thead>
          <tr>
            <th>Hình ảnh</th>
            <th>Tên sản phẩm</th>
            <th>Giá</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $total = 0;
          foreach ($_SESSION['cart'] as $item):
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
          ?>
          <tr>
            <td><img src="<?php echo $basePath; ?>uploads/<?php echo htmlspecialchars($item['image']); ?>" alt=""></td>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td><?php echo number_format($item['price']); ?>₫</td>
            <td>
              <input class="qty-input" type="number" name="qty[<?php echo $item['id']; ?>]" 
                     value="<?php echo $item['quantity']; ?>" min="1" data-id="<?php echo $item['id']; ?>">
            </td>
            <td class="subtotal"><?php echo number_format($subtotal); ?>₫</td>
            <td>
              <a class="remove-link" href="<?php echo $basePath; ?>cart.php?action=remove&id=<?php echo $item['id']; ?>" 
                 style="color:red; text-decoration:none;">🗑️ Xóa</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="total">
        Tổng cộng: <span id="total-amount"><?php echo number_format($total); ?></span>₫
      </div>

      <div style="margin-top:20px; display:flex; justify-content:space-between;">
        <button type="submit" name="update_cart" class="btn btn-update">🔄 Cập nhật giỏ hàng</button>
        <div>
          <a id="clear-cart" href="<?php echo $basePath; ?>cart.php?action=clear" class="btn btn-clear">🧹 Xóa tất cả</a>
          <a href="<?php echo $basePath; ?>checkout.php" class="btn btn-checkout">💳 Thanh toán</a>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>

<script>
// JS: khi submit form cập nhật, gửi AJAX để server trả cart_count và cập nhật badge (không cần reload)
document.getElementById('cart-form')?.addEventListener('submit', async function(e){
    e.preventDefault();
    const form = new FormData(this);
    // mark as ajax
    form.append('action', 'update');
    form.append('ajax', '1');

    try {
        const res = await fetch('<?php echo $basePath; ?>cart.php', {
            method: 'POST',
            body: form
        });
        const data = await res.json();
        if (data.ok) {
            // reload small để cập nhật UI subtotal & total từ server hoặc cập nhật thủ công:
            location.reload(); // đơn giản, hoặc bạn có thể cập nhật DOM từ data
        } else {
            alert(data.message || 'Cập nhật thất bại');
        }
    } catch (err) {
        console.error(err);
        alert('Lỗi kết nối');
    }
});

// JS: xóa 1 item qua AJAX (soạn lại các link remove-link)
document.querySelectorAll('.remove-link').forEach(a => {
    a.addEventListener('click', async function(e){
        e.preventDefault();
        if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
        const url = new URL(this.href, window.location.origin);
        url.searchParams.set('ajax', '1');
        try {
            const res = await fetch(url.toString(), { method: 'GET' });
            const data = await res.json();
            if (data.ok) {
                location.reload();
            } else {
                alert('Xóa thất bại');
            }
        } catch (err) {
            console.error(err);
            alert('Lỗi kết nối');
        }
    });
});

// JS: xóa toàn bộ giỏ bằng AJAX (nút clear-cart)
document.getElementById('clear-cart')?.addEventListener('click', async function(e){
    e.preventDefault();
    if (!confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) return;
    const url = new URL(this.href, window.location.origin);
    url.searchParams.set('ajax', '1');
    try {
        const res = await fetch(url.toString(), { method: 'GET' });
        const data = await res.json();
        if (data.ok) {
            location.reload();
        } else {
            alert('Không thể xóa giỏ hàng');
        }
    } catch (err) {
        console.error(err);
        alert('Lỗi kết nối');
    }
});
</script>

<?php include $basePath . 'includes/footer.php'; ?>
</body>
</html>
