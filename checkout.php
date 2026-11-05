<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/payments.php';
$pageTitle = 'Checkout';
$pdo = db();
$s = msettings();

$c = cart();
if (!$c) {
    header('Location: cart.php');
    exit;
}

$ids = array_map('intval', array_keys($c));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$cartProducts = $stmt->fetchAll();

$items = [];
$total = 0.0;
foreach ($cartProducts as $prod) {
    $qty = min($c[$prod['id']], max(0, (int) $prod['stock_qty']));
    if ($qty <= 0) continue;
    $lineTotal = $qty * (float) $prod['price'];
    $total += $lineTotal;
    $items[] = ['product' => $prod, 'qty' => $qty, 'lineTotal' => $lineTotal];
}

$error = '';
$order = null;
$razorOrder = null;
$codEnabled = !empty($s['cod_enabled']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'place') {
    $name = trim($_POST['buyer_name'] ?? '');
    $email = trim($_POST['buyer_email'] ?? '');
    $phone = trim($_POST['buyer_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $method = $_POST['method'] ?? '';

    if (!$items) {
        $error = 'Your cart is empty.';
    } elseif ($name === '' || $phone === '' || $address === '') {
        $error = 'Please fill in your name, phone and delivery address.';
    } elseif (!in_array($method, ['razorpay', 'upi', 'cod'], true) || ($method === 'cod' && !$codEnabled)) {
        $error = 'Please choose a valid payment method.';
    } else {
        $pdo->beginTransaction();
        $paymentStatus = $method === 'cod' ? 'cod_pending' : 'pending';
        $stmt = $pdo->prepare("INSERT INTO orders (buyer_name, buyer_email, buyer_phone, address, city, pincode, total_amount, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email ?: null, $phone, $address, $city ?: null, $pincode ?: null, $total, $method, $paymentStatus]);
        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, title, price, qty) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $it) {
            $itemStmt->execute([$orderId, $it['product']['id'], $it['product']['title'], $it['product']['price'], $it['qty']]);
        }
        $pdo->commit();

        if ($method === 'cod') {
            $pdo->prepare("UPDATE orders SET receipt_no = ? WHERE id = ?")->execute([generate_receipt_no($orderId), $orderId]);
            unset($_SESSION['cart']);
            header('Location: order-success.php?id=' . $orderId);
            exit;
        }

        if ($method === 'razorpay') {
            $keyId = $s['razorpay_key_id'] ?? '';
            $keySecret = $s['razorpay_key_secret'] ?? '';
            if ($keyId === '' || $keySecret === '') {
                $error = 'Online payments are not configured yet. Please use Cash on Delivery or offline UPI instead.';
            } else {
                $rOrder = razorpay_create_order($total, $keyId, $keySecret, 'SM' . $orderId . '-' . time());
                if (!$rOrder || empty($rOrder['id'])) {
                    $error = 'Could not start the online payment right now. Please try another method.';
                } else {
                    $pdo->prepare("UPDATE orders SET razorpay_order_id = ? WHERE id = ?")->execute([$rOrder['id'], $orderId]);
                    $order = ['id' => $orderId, 'name' => $name, 'email' => $email, 'phone' => $phone, 'method' => $method];
                    $razorOrder = $rOrder;
                }
            }
        } else {
            $order = ['id' => $orderId, 'name' => $name, 'method' => $method];
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <p class="eyebrow">Checkout</p>
  <h1><em>Checkout</em></h1>
</section>
<section class="section-pad">
  <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>

  <?php if ($order && $order['method'] === 'upi'): ?>
    <div class="checkout-grid">
      <div class="upi-box">
        <h3>Pay via UPI</h3>
        <p>UPI ID: <strong><?= e($s['upi_id'] ?? 'not configured') ?></strong></p>
        <?php if (!empty($s['upi_qr_image'])): ?><img src="<?= e($s['upi_qr_image']) ?>" alt="UPI QR code"><?php endif; ?>
        <p>Amount: <strong>₹<?= number_format($total, 2) ?></strong></p>
        <p>After paying, upload a screenshot below. We'll verify it and confirm your order.</p>
      </div>
      <div>
        <form class="site-form" method="post" action="order-upi-proof.php" enctype="multipart/form-data">
          <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
          <div>
            <label for="proof">Payment Screenshot</label>
            <input id="proof" name="proof" type="file" accept="image/*,.pdf" required>
          </div>
          <button class="button" type="submit">Submit Payment Proof <b>→</b></button>
        </form>
      </div>
    </div>

  <?php elseif ($order && $order['method'] === 'razorpay' && $razorOrder): ?>
    <div class="flash ok">Click below to complete your secure payment.</div>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <button id="rzp-pay-btn" class="button">Pay ₹<?= number_format($total, 2) ?> Now <b>→</b></button>
    <script>
      document.getElementById('rzp-pay-btn').addEventListener('click', function () {
        var options = {
          key: <?= json_encode($s['razorpay_key_id']) ?>,
          amount: <?= (int) round($total * 100) ?>,
          currency: 'INR',
          order_id: <?= json_encode($razorOrder['id']) ?>,
          name: 'Sandesh Art Marketplace',
          description: 'Order #<?= (int) $order['id'] ?>',
          handler: function (resp) {
            var f = document.createElement('form');
            f.method = 'POST';
            f.action = 'order-verify.php';
            var fields = {
              razorpay_payment_id: resp.razorpay_payment_id,
              razorpay_order_id: resp.razorpay_order_id,
              razorpay_signature: resp.razorpay_signature,
              order_id: <?= (int) $order['id'] ?>
            };
            for (var k in fields) {
              var i = document.createElement('input');
              i.type = 'hidden'; i.name = k; i.value = fields[k];
              f.appendChild(i);
            }
            document.body.appendChild(f);
            f.submit();
          },
          prefill: {
            name: <?= json_encode($order['name']) ?>,
            email: <?= json_encode($order['email']) ?>,
            contact: <?= json_encode($order['phone']) ?>
          },
          theme: { color: '#ef5f50' }
        };
        var rzp = new Razorpay(options);
        rzp.open();
      });
    </script>

  <?php else: ?>
    <div class="checkout-grid">
      <form class="site-form" method="post">
        <input type="hidden" name="step" value="place">
        <div>
          <label for="buyer_name">Full Name</label>
          <input id="buyer_name" name="buyer_name" required value="<?= e($_POST['buyer_name'] ?? '') ?>">
        </div>
        <div>
          <label for="buyer_phone">Phone</label>
          <input id="buyer_phone" name="buyer_phone" required value="<?= e($_POST['buyer_phone'] ?? '') ?>">
        </div>
        <div>
          <label for="buyer_email">Email (optional, for receipt)</label>
          <input id="buyer_email" name="buyer_email" type="email" value="<?= e($_POST['buyer_email'] ?? '') ?>">
        </div>
        <div>
          <label for="address">Delivery Address</label>
          <textarea id="address" name="address" required><?= e($_POST['address'] ?? '') ?></textarea>
        </div>
        <div>
          <label for="city">City</label>
          <input id="city" name="city" value="<?= e($_POST['city'] ?? '') ?>">
        </div>
        <div>
          <label for="pincode">Pincode</label>
          <input id="pincode" name="pincode" value="<?= e($_POST['pincode'] ?? '') ?>">
        </div>
        <div class="method-tabs">
          <label><input type="radio" name="method" value="razorpay" checked> Pay Online (Razorpay)</label>
          <label><input type="radio" name="method" value="upi"> Offline UPI Transfer</label>
          <?php if ($codEnabled): ?><label><input type="radio" name="method" value="cod"> Cash on Delivery</label><?php endif; ?>
        </div>
        <?php if (!empty($s['shipping_note'])): ?><p class="form-note"><?= e($s['shipping_note']) ?></p><?php endif; ?>
        <button class="button" type="submit">Place Order <b>→</b></button>
      </form>
      <div class="order-summary">
        <h3>Order Summary</h3>
        <?php foreach ($items as $it): ?>
          <div class="line"><span><?= e($it['product']['title']) ?> × <?= (int) $it['qty'] ?></span><span>₹<?= number_format($it['lineTotal'], 2) ?></span></div>
        <?php endforeach; ?>
        <div class="total"><span>Total</span><span>₹<?= number_format($total, 2) ?></span></div>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
