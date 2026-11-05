<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Become a Vendor';

if (is_vendor()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $businessName = trim($_POST['business_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bankAccountName = trim($_POST['bank_account_name'] ?? '');
    $bankAccountNumber = trim($_POST['bank_account_number'] ?? '');
    $bankIfsc = trim($_POST['bank_ifsc'] ?? '');
    $bankName = trim($_POST['bank_name'] ?? '');

    $pdo = db();

    if ($name === '' || $email === '' || $password === '' || $businessName === '' || $phone === '') {
        $error = 'Please fill in your name, email, phone, password and business name.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (empty($_FILES['pan_doc']['tmp_name']) || empty($_FILES['aadhar_doc']['tmp_name'])) {
        $error = 'Please upload both your PAN card and Aadhar card for KYC verification.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists. Try logging in instead.';
        } else {
            $panDoc = handle_doc_upload('pan_doc', null, 'uploads/vendor_docs');
            $aadharDoc = handle_doc_upload('aadhar_doc', null, 'uploads/vendor_docs');
            $gstDoc = handle_doc_upload('gst_doc', null, 'uploads/vendor_docs');

            if (!$panDoc || !$aadharDoc) {
                $error = 'Documents must be a JPG, PNG or PDF file.';
            } else {
                $pdo->beginTransaction();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, role, is_admin) VALUES (?, ?, ?, ?, ?, 0)')
                    ->execute([$name, $email, $phone, $hash, 'vendor']);
                $userId = (int) $pdo->lastInsertId();

                $pdo->prepare('INSERT INTO vendors (user_id, business_name, phone, address, status, pan_doc, aadhar_doc, gst_doc, bank_account_name, bank_account_number, bank_ifsc, bank_name)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([
                        $userId, $businessName, $phone, $address ?: null, 'pending',
                        $panDoc, $aadharDoc, $gstDoc,
                        $bankAccountName ?: null, $bankAccountNumber ?: null, $bankIfsc ?: null, $bankName ?: null,
                    ]);
                $pdo->commit();

                $_SESSION['user_id'] = $userId;
                $_SESSION['is_admin'] = false;
                header('Location: dashboard.php?welcome=1');
                exit;
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="page-hero">
  <p class="eyebrow">Sell With Us</p>
  <h1>Become a <em>Vendor</em></h1>
  <p>List your own art, prints or handmade crafts on Sandesh Art Marketplace. We handle payments and delivery — you keep your share after a platform commission, settled every month.</p>
</section>
<section class="section-pad">
  <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
  <form class="site-form" method="post" enctype="multipart/form-data" style="max-width:34rem">
    <h3 style="font-family:'Playfair Display',serif">Your Details</h3>
    <div>
      <label for="name">Your Full Name</label>
      <input id="name" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
    </div>
    <div>
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>">
    </div>
    <div>
      <label for="phone">Phone</label>
      <input id="phone" name="phone" required value="<?= e($_POST['phone'] ?? '') ?>">
    </div>
    <div>
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required minlength="6">
    </div>

    <h3 style="font-family:'Playfair Display',serif;margin-top:1rem">Business Details</h3>
    <div>
      <label for="business_name">Business / Studio Name</label>
      <input id="business_name" name="business_name" required value="<?= e($_POST['business_name'] ?? '') ?>">
    </div>
    <div>
      <label for="address">Business Address</label>
      <textarea id="address" name="address"><?= e($_POST['address'] ?? '') ?></textarea>
    </div>

    <h3 style="font-family:'Playfair Display',serif;margin-top:1rem">KYC Documents (for verification)</h3>
    <div>
      <label for="pan_doc">PAN Card</label>
      <input id="pan_doc" name="pan_doc" type="file" accept="image/*,.pdf" required>
    </div>
    <div>
      <label for="aadhar_doc">Aadhar Card</label>
      <input id="aadhar_doc" name="aadhar_doc" type="file" accept="image/*,.pdf" required>
    </div>
    <div>
      <label for="gst_doc">GST Certificate (optional)</label>
      <input id="gst_doc" name="gst_doc" type="file" accept="image/*,.pdf">
    </div>

    <h3 style="font-family:'Playfair Display',serif;margin-top:1rem">Bank Details (for monthly settlement)</h3>
    <div>
      <label for="bank_account_name">Account Holder Name</label>
      <input id="bank_account_name" name="bank_account_name" value="<?= e($_POST['bank_account_name'] ?? '') ?>">
    </div>
    <div>
      <label for="bank_account_number">Account Number</label>
      <input id="bank_account_number" name="bank_account_number" value="<?= e($_POST['bank_account_number'] ?? '') ?>">
    </div>
    <div>
      <label for="bank_ifsc">IFSC Code</label>
      <input id="bank_ifsc" name="bank_ifsc" value="<?= e($_POST['bank_ifsc'] ?? '') ?>">
    </div>
    <div>
      <label for="bank_name">Bank Name</label>
      <input id="bank_name" name="bank_name" value="<?= e($_POST['bank_name'] ?? '') ?>">
    </div>

    <p class="form-note">After you submit, our team reviews your documents. Once approved you can start adding products — commission is deducted automatically, and your share is settled to this bank account every month.</p>
    <button class="button" type="submit">Submit Application <b>→</b></button>
  </form>
  <p class="form-note">Already a vendor? <a href="/vendor/login.php">Vendor Login</a></p>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
