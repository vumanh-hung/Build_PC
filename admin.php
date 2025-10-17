<?php
session_start();
require_once 'functions.php';
require_once 'config.php';

// handle login/logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['is_admin']);
    header('Location: admin.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    if ($u === ADMIN_USER && $p === ADMIN_PASS) {
        $_SESSION['is_admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $err = 'Sai thông tin đăng nhập';
    }
}

if (!isset($_SESSION['is_admin'])) {
    include 'views_header.php';
    ?>
    <div class="container">
      <h1>Admin login</h1>
      <?php if (!empty($err)): ?><div class="small" style="color:#c00;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
      <form method="post">
        <label>Username: <input type="text" name="username"></label><br><br>
        <label>Password: <input type="password" name="password"></label><br><br>
        <button class="button" type="submit" name="login">Login</button>
      </form>
      <p><a href="index.php">Back to site</a></p>
    </div>
    <?php
    include 'views_footer.php';
    exit;
}

// admin area
$products = getAllProducts();
include 'views_header.php';
?>
<div class="container">
  <h1>Admin - Quản lý sản phẩm</h1>
  <p><a class="button" href="product_add.php">Thêm sản phẩm mới</a> <a class="button" href="index.php">Xem site</a> <a class="button" href="admin.php?action=logout">Logout</a></p>
  <table class="table">
    <tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Image</th><th>Actions</th></tr>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><?= htmlspecialchars($p['category']) ?></td>
        <td><?= number_format($p['price'],0) ?>₫</td>
        <td><?= htmlspecialchars($p['image']) ?></td>
        <td>
          <a class="button" href="product_edit.php?id=<?= $p['id'] ?>">Edit</a>
          <a class="button button-red" href="product_delete.php?id=<?= $p['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include 'views_footer.php'; ?>
