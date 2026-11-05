<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Home';
$s = msettings();
$featured = db()->query("
  SELECT p.* FROM products p LEFT JOIN vendors v ON v.id = p.vendor_id
  WHERE p.is_active = 1 AND (p.vendor_id IS NULL OR v.status = 'approved')
  ORDER BY p.sort_order DESC, p.id DESC LIMIT 4
")->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <p class="eyebrow">Sandesh Art Arena</p>
  <h1>Original Art, <em>Handmade</em> with Heart</h1>
  <p><?= nl2br(e($s['about_text'] ?? '')) ?></p>
  <p><a class="button" href="/shop.php">Shop All Products →</a></p>
</section>
<section class="section-pad">
  <h2>Featured Pieces</h2>
  <?php if ($featured): ?>
  <div class="grid-cards">
    <?php foreach ($featured as $p): ?>
      <div class="product-card">
        <a href="/product.php?id=<?= (int) $p['id'] ?>">
          <?php if ($p['image']): ?><img src="<?= e(img_url($p['image'])) ?>" alt="<?= e($p['title']) ?>"><?php endif; ?>
        </a>
        <div class="product-body">
          <span class="tag"><?= e($p['category']) ?></span>
          <h3><a href="/product.php?id=<?= (int) $p['id'] ?>" style="color:inherit;text-decoration:none"><?= e($p['title']) ?></a></h3>
          <p class="price">₹<?= number_format((float) $p['price'], 2) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p>Products will appear here once added from the admin panel.</p>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
