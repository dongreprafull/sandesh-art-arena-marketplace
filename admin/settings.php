<?php
$pageTitle = 'Store Settings';
require_once __DIR__ . '/includes/admin_header.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $about = trim($_POST['about_text'] ?? '');
    $shipping = trim($_POST['shipping_note'] ?? '');
    $codEnabled = isset($_POST['cod_enabled']) ? 1 : 0;
    $upiId = trim($_POST['upi_id'] ?? '');
    $upiQr = trim($_POST['upi_qr_image'] ?? '');
    $keyId = trim($_POST['razorpay_key_id'] ?? '');
    $keySecret = trim($_POST['razorpay_key_secret'] ?? '');

    $pdo->prepare("UPDATE marketplace_settings SET about_text = ?, shipping_note = ?, cod_enabled = ?, upi_id = ?, upi_qr_image = ?, razorpay_key_id = ?, razorpay_key_secret = ? WHERE id = 1")
        ->execute([
            $about ?: null, $shipping ?: null, $codEnabled,
            $upiId ?: null, $upiQr ?: null, $keyId ?: null, $keySecret ?: null,
        ]);
    header('Location: settings.php?saved=1');
    exit;
}

$s = $pdo->query("SELECT * FROM marketplace_settings WHERE id = 1")->fetch() ?: [];
?>
<?php if (isset($_GET['saved'])): ?><div class="flash ok">Settings saved.</div><?php endif; ?>

<form class="admin-form settings-section" method="post">
  <h2>Store Info</h2>
  <div>
    <label for="about_text">About / Homepage Intro Text</label>
    <textarea id="about_text" name="about_text" rows="4"><?= e($s['about_text'] ?? '') ?></textarea>
  </div>
  <div>
    <label for="shipping_note">Shipping Note (shown at checkout)</label>
    <textarea id="shipping_note" name="shipping_note" rows="3"><?= e($s['shipping_note'] ?? '') ?></textarea>
  </div>
  <div>
    <label><input type="checkbox" name="cod_enabled" value="1" <?= !empty($s['cod_enabled']) ? 'checked' : '' ?>> Enable Cash on Delivery (COD)</label>
  </div>

  <h2>Offline UPI Payment</h2>
  <div>
    <label for="upi_id">UPI ID</label>
    <input id="upi_id" name="upi_id" value="<?= e($s['upi_id'] ?? '') ?>" placeholder="sandeshartarena@upi">
  </div>
  <div>
    <label for="upi_qr_image">UPI QR Code Image URL</label>
    <input id="upi_qr_image" name="upi_qr_image" value="<?= e($s['upi_qr_image'] ?? '') ?>">
  </div>

  <h2>Razorpay (Online Payments)</h2>
  <p class="muted">Leave blank to keep online payments disabled — buyers will see COD / offline UPI only.</p>
  <div>
    <label for="razorpay_key_id">Razorpay Key ID</label>
    <input id="razorpay_key_id" name="razorpay_key_id" value="<?= e($s['razorpay_key_id'] ?? '') ?>">
  </div>
  <div>
    <label for="razorpay_key_secret">Razorpay Key Secret</label>
    <input id="razorpay_key_secret" name="razorpay_key_secret" type="password" value="<?= e($s['razorpay_key_secret'] ?? '') ?>">
  </div>

  <button class="btn primary" type="submit">Save Settings</button>
</form>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
