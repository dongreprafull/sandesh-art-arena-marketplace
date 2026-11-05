<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Shop';
$pdo = db();

$visibleFilter = "p.is_active = 1 AND (p.vendor_id IS NULL OR v.status = 'approved')";
$categories = $pdo->query("SELECT DISTINCT p.category FROM products p LEFT JOIN vendors v ON v.id = p.vendor_id WHERE $visibleFilter ORDER BY p.category")->fetchAll(PDO::FETCH_COLUMN);
$activeCat = $_GET['category'] ?? '';

if ($activeCat !== '' && in_array($activeCat, $categories, true)) {
    $stmt = $pdo->prepare("SELECT p.*, v.business_name FROM products p LEFT JOIN vendors v ON v.id = p.vendor_id WHERE $visibleFilter AND p.category = ? ORDER BY p.sort_order DESC, p.id DESC");
    $stmt->execute([$activeCat]);
} else {
    $activeCat = '';
    $stmt = $pdo->query("SELECT p.*, v.business_name FROM products p LEFT JOIN vendors v ON v.id = p.vendor_id WHERE $visibleFilter ORDER BY p.sort_order DESC, p.id DESC");
}
$products = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <p class="eyebrow">Shop</p>
  <h1>All <em>Products</em></h1>
  <div class="category-chips">
    <a href="/shop.php" class="<?= $activeCat === '' ? 'active' : '' ?>">All</a>
    <?php foreach ($categories as $cat): ?>
      <a href="/shop.php?category=<?= urlencode($cat) ?>" class="<?= $activeCat === $cat ? 'active' : '' ?>"><?= e($cat) ?></a>
    <?php endforeach; ?>
  </div>
</section>
<section class="section-pad">
  <?php if ($products): ?>
  <div class="grid-cards">
    <?php foreach ($products as $p): ?>
      <div class="product-card <?= $p['stock_qty'] <= 0 ? 'out-of-stock' : '' ?>">
        <a href="/product.php?id=<?= (int) $p['id'] ?>">
          <?php if ($p['image']): ?><img src="<?= e(img_url($p['image'])) ?>" alt="<?= e($p['title']) ?>"><?php endif; ?>
        </a>
        <div class="product-body">
          <span class="tag"><?= e($p['category']) ?></span>
          <h3><a href="/product.php?id=<?= (int) $p['id'] ?>" style="color:inherit;text-decoration:none"><?= e($p['title']) ?></a></h3>
          <p><?= e(mb_strimwidth($p['description'] ?? '', 0, 80, '…')) ?></p>
          <?php if ($p['stock_qty'] <= 0): ?><p class="stock-note">Out of stock</p><?php endif; ?>
          <p class="muted" style="font-size:.78rem">Sold by <?= e($p['business_name'] ?? 'Sandesh Art Arena') ?></p>
          <p class="price">₹<?= number_format((float) $p['price'], 2) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p>No products in this category yet.</p>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
