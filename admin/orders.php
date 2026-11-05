<?php
require_once __DIR__ . '/../includes/payments.php';
$pageTitle = 'Orders';
require_once __DIR__ . '/includes/admin_header.php';
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($postId && $action === 'verify_upi') {
        $receiptNo = generate_receipt_no($postId);
        $pdo->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'confirmed', receipt_no = ? WHERE id = ? AND payment_status = 'pending'")
            ->execute([$receiptNo, $postId]);
    } elseif ($postId && $action === 'reject_upi') {
        $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ? AND payment_status = 'pending'")->execute([$postId]);
    } elseif ($postId && $action === 'update_status') {
        $newStatus = $_POST['order_status'] ?? '';
        if (in_array($newStatus, ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled'], true)) {
            $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?")->execute([$newStatus, $postId]);
        }
    }
    header('Location: orders.php' . ($id ? '?id=' . $id : ''));
    exit;
}

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order) {
        echo '<p>Order not found. <a href="orders.php">Back to Orders</a></p>';
        require_once __DIR__ . '/includes/admin_footer.php';
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
    ?>
    <p><a href="orders.php">← Back to Orders</a></p>
    <h2>Order <?= e($order['receipt_no'] ?? ('#' . $order['id'])) ?></h2>
    <div class="admin-cards">
      <div class="admin-card">
        <p class="admin-card-label">Buyer</p>
        <p style="font-size:1.1rem;font-weight:700"><?= e($order['buyer_name']) ?></p>
        <p class="muted"><?= e($order['buyer_phone']) ?><?= $order['buyer_email'] ? ' · ' . e($order['buyer_email']) : '' ?></p>
      </div>
      <div class="admin-card">
        <p class="admin-card-label">Deliver To</p>
        <p><?= nl2br(e($order['address'])) ?></p>
        <p class="muted"><?= e($order['city'] ?? '') ?> <?= e($order['pincode'] ?? '') ?></p>
      </div>
      <div class="admin-card">
        <p class="admin-card-label">Payment</p>
        <p><span class="status-pill status-<?= e($order['payment_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $order['payment_status']))) ?></span></p>
        <p class="muted"><?= ['razorpay' => 'Razorpay', 'upi' => 'UPI', 'cod' => 'Cash on Delivery'][$order['payment_method']] ?? '' ?></p>
      </div>
    </div>

    <table class="admin-table" style="margin-top:1rem">
      <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?= e($it['title']) ?></td>
            <td>₹<?= number_format((float) $it['price'], 2) ?></td>
            <td><?= (int) $it['qty'] ?></td>
            <td>₹<?= number_format((float) $it['price'] * $it['qty'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
        <tr><td colspan="3" style="text-align:right;font-weight:700">Total</td><td style="font-weight:700">₹<?= number_format((float) $order['total_amount'], 2) ?></td></tr>
      </tbody>
    </table>

    <?php if ($order['payment_method'] === 'upi' && $order['upi_proof_url']): ?>
      <p style="margin-top:1rem"><a class="btn ghost" href="../<?= e($order['upi_proof_url']) ?>" target="_blank">View Payment Proof ↗</a></p>
    <?php endif; ?>

    <?php if ($order['payment_status'] === 'pending'): ?>
      <div style="margin-top:1rem">
        <form method="post" style="display:inline">
          <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
          <input type="hidden" name="action" value="verify_upi">
          <button class="btn primary" type="submit" onclick="return confirm('Mark this payment as verified/paid?')">Verify Payment</button>
        </form>
        <form method="post" style="display:inline">
          <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
          <input type="hidden" name="action" value="reject_upi">
          <button class="btn danger" type="submit" onclick="return confirm('Mark this payment as failed?')">Reject Payment</button>
        </form>
      </div>
    <?php endif; ?>

    <h3 style="margin-top:2rem">Update Order Status</h3>
    <form method="post" class="admin-form" style="flex-direction:row;align-items:flex-end;gap:1rem">
      <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
      <input type="hidden" name="action" value="update_status">
      <div style="flex:1">
        <label for="order_status">Status</label>
        <select id="order_status" name="order_status">
          <?php foreach (['placed', 'confirmed', 'shipped', 'delivered', 'cancelled'] as $st): ?>
            <option value="<?= $st ?>" <?= $order['order_status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn primary" type="submit">Save</button>
    </form>
    <?php
} else {
    $statusFilter = $_GET['status'] ?? '';
    $sql = "SELECT * FROM orders";
    $params = [];
    if ($statusFilter === 'pending_upi') {
        $sql .= " WHERE payment_method = 'upi' AND payment_status = 'pending'";
    } elseif (in_array($statusFilter, ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled'], true)) {
        $sql .= " WHERE order_status = ?";
        $params[] = $statusFilter;
    }
    $sql .= " ORDER BY created_at DESC LIMIT 200";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    ?>
    <div class="admin-topbar-actions" style="margin-bottom:1rem">
      <a class="btn <?= $statusFilter === '' ? 'primary' : 'ghost' ?>" href="orders.php">All</a>
      <a class="btn <?= $statusFilter === 'pending_upi' ? 'primary' : 'ghost' ?>" href="orders.php?status=pending_upi">Pending UPI</a>
      <a class="btn <?= $statusFilter === 'placed' ? 'primary' : 'ghost' ?>" href="orders.php?status=placed">Placed</a>
      <a class="btn <?= $statusFilter === 'confirmed' ? 'primary' : 'ghost' ?>" href="orders.php?status=confirmed">Confirmed</a>
      <a class="btn <?= $statusFilter === 'shipped' ? 'primary' : 'ghost' ?>" href="orders.php?status=shipped">Shipped</a>
      <a class="btn <?= $statusFilter === 'delivered' ? 'primary' : 'ghost' ?>" href="orders.php?status=delivered">Delivered</a>
    </div>
    <table class="admin-table">
      <thead><tr><th>Date</th><th>Order No.</th><th>Buyer</th><th>Amount</th><th>Method</th><th>Payment</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
          <td><?= e($o['receipt_no'] ?? ('#' . $o['id'])) ?></td>
          <td><?= e($o['buyer_name']) ?><br><small class="muted"><?= e($o['buyer_phone']) ?></small></td>
          <td>₹<?= number_format((float) $o['total_amount'], 2) ?></td>
          <td><?= ['razorpay' => 'Razorpay', 'upi' => 'UPI', 'cod' => 'COD'][$o['payment_method']] ?? '' ?></td>
          <td><span class="status-pill status-<?= e($o['payment_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $o['payment_status']))) ?></span></td>
          <td><span class="status-pill status-<?= e($o['order_status']) ?>"><?= e(ucfirst($o['order_status'])) ?></span></td>
          <td><a class="btn ghost" href="orders.php?id=<?= (int) $o['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?><tr><td colspan="8">No orders found.</td></tr><?php endif; ?>
      </tbody>
    </table>
    <?php
}
require_once __DIR__ . '/includes/admin_footer.php';
