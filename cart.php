<?php
require_once 'functions.php';
include 'views_header.php';

if (isset($_GET['created'])) {
    $createdId = intval($_GET['created']);
}
$configs = getConfigurations();
?>
<div class="container">
  <h1>Cấu hình đã lưu</h1>
  <?php if (!empty($configs)): ?>
    <table class="table">
      <tr><th>#</th><th>Tên</th><th>Ngày</th><th>Sản phẩm</th><th>Tổng giá</th></tr>
      <?php foreach ($configs as $c): 
         $items = getConfigurationItems($c['id']);
         $sum = 0;
         foreach ($items as $it) $sum += $it['price'];
      ?>
        <tr>
          <td><?= $c['id'] ?></td>
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td><?= $c['created_at'] ?></td>
          <td>
            <ul>
            <?php foreach ($items as $it): ?>
              <li><?= htmlspecialchars($it['name']) ?> (<?= htmlspecialchars($it['category']) ?>) - <?= number_format($it['price'],0) ?>₫</li>
            <?php endforeach; ?>
            </ul>
          </td>
          <td><?= number_format($sum,0) ?>₫</td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>Chưa có cấu hình nào được lưu.</p>
  <?php endif; ?>

  <p><a class="button" href="index.php">Quay lại</a></p>
</div>
<?php include 'views_footer.php'; ?>
