<aside class="sidebar">
  <h3>Danh mục sản phẩm</h3>
  <ul>
    <?php
    $categories = $pdo->query("SELECT * FROM categories LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($categories as $c) {
        echo "<li><a href='page/products.php?cat={$c['category_id']}'>{$c['name']}</a></li>";
    }
    ?>
  </ul>

  <h3>Thương hiệu nổi bật</h3>
  <ul>
    <?php
    $brands = $pdo->query("SELECT * FROM brands LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($brands as $b) {
        echo "<li><a href='page/products.php?brand={$b['brand_id']}'>{$b['name']}</a></li>";
    }
    ?>
  </ul>
</aside>
