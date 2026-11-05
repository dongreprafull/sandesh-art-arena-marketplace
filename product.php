<?php
require_once __DIR__ . '/config.php';
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    include __DIR__ . '/includes/header.php';
    echo '<section class="section-pad"><p>Product not found.</p></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$added = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    $qty = max(1, (int) ($_POST['qty'] ?? 1));
    $c = cart();
    $c[$id] = ($c[$id] ?? 0) + $qty;
    $_SESSION['cart'] = $c;
    $added = true;
}

$pageTitle = $p['title'];
include __DIR__ . '/includes/header.php';
?>
<section class="section-pad" style="padding-top:clamp(2.5rem,6vw,4rem)">
  <?php if ($added): ?><div class="flash ok">Added to cart. <a href="/cart.php">View Cart →</a></div><?php endif; ?>
  <div class="product-detail">
    <div>
      <?php if ($p['image']): ?><img src="<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>"><?php endif; ?>
    </div>
    <div>
      <span class="tag"><?= e($p['category']) ?></span>
      <h1><?= e($p['title']) ?></h1>
      <p class="price">₹<?= number_format((float) $p['price'], 2) ?></p>
      <p><?= nl2br(e($p['description'] ?? '')) ?></p>
      <?php if ($p['stock_qty'] > 0): ?>
        <form method="post" class="site-form" style="margin-top:1rem;max-width:12rem">
          <input type="hidden" name="action" value="add_to_cart">
          <div>
            <label for="qty">Quantity</label>
            <input id="qty" name="qty" type="number" min="1" max="<?= (int) $p['stock_qty'] ?>" value="1">
          </div>
          <button class="button" type="submit">Add to Cart <b>→</b></button>
        </form>
        <p class="form-note"><?= (int) $p['stock_qty'] ?> in stock</p>
      <?php else: ?>
        <p class="stock-note" style="font-size:.95rem">Currently out of stock.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
