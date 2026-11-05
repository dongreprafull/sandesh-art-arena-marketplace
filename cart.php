<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Your Cart';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $c = cart();
    if (($_POST['action'] ?? '') === 'update') {
        foreach ($_POST['qty'] ?? [] as $pid => $qty) {
            $pid = (int) $pid;
            $qty = (int) $qty;
            if ($qty <= 0) {
                unset($c[$pid]);
            } else {
                $c[$pid] = $qty;
            }
        }
    } elseif (($_POST['action'] ?? '') === 'remove') {
        unset($c[(int) ($_POST['product_id'] ?? 0)]);
    }
    $_SESSION['cart'] = $c;
    header('Location: cart.php');
    exit;
}

$c = cart();
$items = [];
$total = 0.0;
if ($c) {
    $ids = array_map('intval', array_keys($c));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
    foreach ($products as $prod) {
        $qty = $c[$prod['id']];
        $lineTotal = $qty * (float) $prod['price'];
        $total += $lineTotal;
        $items[] = ['product' => $prod, 'qty' => $qty, 'lineTotal' => $lineTotal];
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <p class="eyebrow">Your Cart</p>
  <h1>Shopping <em>Cart</em></h1>
</section>
<section class="section-pad">
  <?php if (!$items): ?>
    <p>Your cart is empty. <a href="/shop.php">Browse products →</a></p>
  <?php else: ?>
    <form id="cart-update-form" method="post" action="cart.php">
      <input type="hidden" name="action" value="update">
    </form>
    <table class="cart-table">
      <thead><tr><th></th><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): $prod = $it['product']; ?>
        <tr>
          <td><?php if ($prod['image']): ?><img src="<?= e(img_url($prod['image'])) ?>" alt=""><?php endif; ?></td>
          <td><a href="/product.php?id=<?= (int) $prod['id'] ?>" style="color:inherit"><?= e($prod['title']) ?></a></td>
          <td>₹<?= number_format((float) $prod['price'], 2) ?></td>
          <td><input type="number" name="qty[<?= (int) $prod['id'] ?>]" form="cart-update-form" value="<?= (int) $it['qty'] ?>" min="1" max="<?= (int) $prod['stock_qty'] ?>"></td>
          <td>₹<?= number_format($it['lineTotal'], 2) ?></td>
          <td>
            <form method="post" action="cart.php" style="display:inline">
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="product_id" value="<?= (int) $prod['id'] ?>">
              <button class="btn ghost" type="submit">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div style="margin-top:1rem"><button class="btn ghost" type="submit" form="cart-update-form">Update Cart</button></div>
    <div class="cart-summary">
      <p class="total">Total: ₹<?= number_format($total, 2) ?></p>
      <p><a class="button" href="/checkout.php">Proceed to Checkout →</a></p>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
