<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Track Order';
$pdo = db();
$order = null;
$items = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderRef = trim($_POST['order_ref'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($orderRef === '' || $phone === '') {
        $error = 'Please enter your order number and phone number.';
    } else {
        $id = (int) ltrim($orderRef, '#');
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE (id = ? OR receipt_no = ?) AND buyer_phone = ?");
        $stmt->execute([$id, $orderRef, $phone]);
        $order = $stmt->fetch();
        if ($order) {
            $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$order['id']]);
            $items = $stmt->fetchAll();
        } else {
            $error = 'No matching order found. Check your order number and phone number.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <p class="eyebrow">Track Order</p>
  <h1>Track Your <em>Order</em></h1>
</section>
<section class="section-pad">
  <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
  <form class="site-form" method="post">
    <div>
      <label for="order_ref">Order No. (e.g. SM-2026-00001 or #12)</label>
      <input id="order_ref" name="order_ref" required value="<?= e($_POST['order_ref'] ?? '') ?>">
    </div>
    <div>
      <label for="phone">Phone Number Used at Checkout</label>
      <input id="phone" name="phone" required value="<?= e($_POST['phone'] ?? '') ?>">
    </div>
    <button class="button" type="submit">Track Order <b>→</b></button>
  </form>

  <?php if ($order): ?>
    <div class="receipt-card" style="margin-top:2rem">
      <h3>Order <?= e($order['receipt_no'] ?? ('#' . $order['id'])) ?></h3>
      <div class="receipt-row"><span>Placed On</span><strong><?= e(date('d M Y', strtotime($order['created_at']))) ?></strong></div>
      <div class="receipt-row"><span>Order Status</span><strong><span class="status-pill status-<?= e($order['order_status']) ?>"><?= e(ucfirst($order['order_status'])) ?></span></strong></div>
      <div class="receipt-row"><span>Payment Status</span><strong><span class="status-pill status-<?= e($order['payment_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $order['payment_status']))) ?></span></strong></div>
      <?php foreach ($items as $it): ?>
        <div class="receipt-row"><span><?= e($it['title']) ?> × <?= (int) $it['qty'] ?></span><strong>₹<?= number_format((float) $it['price'] * $it['qty'], 2) ?></strong></div>
      <?php endforeach; ?>
      <div class="receipt-row"><span>Total</span><strong class="receipt-amount">₹<?= number_format((float) $order['total_amount'], 2) ?></strong></div>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
