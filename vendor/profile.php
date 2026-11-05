<?php
$pageTitle = 'Profile & Bank Details';
require_once __DIR__ . '/includes/vendor_header.php';
$pdo = db();
$vid = (int) $vendor['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $businessName = trim($_POST['business_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bankAccountName = trim($_POST['bank_account_name'] ?? '');
    $bankAccountNumber = trim($_POST['bank_account_number'] ?? '');
    $bankIfsc = trim($_POST['bank_ifsc'] ?? '');
    $bankName = trim($_POST['bank_name'] ?? '');

    $panDoc = handle_doc_upload('pan_doc', $vendor['pan_doc'], 'uploads/vendor_docs');
    $aadharDoc = handle_doc_upload('aadhar_doc', $vendor['aadhar_doc'], 'uploads/vendor_docs');
    $gstDoc = handle_doc_upload('gst_doc', $vendor['gst_doc'], 'uploads/vendor_docs');

    // If a rejected vendor updates their documents, put them back into "pending" for re-review.
    $newStatus = $vendor['status'];
    $docsChanged = $panDoc !== $vendor['pan_doc'] || $aadharDoc !== $vendor['aadhar_doc'] || $gstDoc !== $vendor['gst_doc'];
    if ($vendor['status'] === 'rejected' && $docsChanged) {
        $newStatus = 'pending';
    }

    $pdo->prepare("UPDATE vendors SET business_name = ?, phone = ?, address = ?, bank_account_name = ?, bank_account_number = ?, bank_ifsc = ?, bank_name = ?, pan_doc = ?, aadhar_doc = ?, gst_doc = ?, status = ?, rejection_reason = CASE WHEN ? = 'pending' THEN NULL ELSE rejection_reason END WHERE id = ?")
        ->execute([$businessName, $phone, $address ?: null, $bankAccountName ?: null, $bankAccountNumber ?: null, $bankIfsc ?: null, $bankName ?: null, $panDoc, $aadharDoc, $gstDoc, $newStatus, $newStatus, $vid]);

    header('Location: profile.php?saved=1');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->execute([$vid]);
$vendor = $stmt->fetch();
?>
<?php if (isset($_GET['saved'])): ?><div class="flash ok">Profile updated.</div><?php endif; ?>
<form class="admin-form settings-section" method="post" enctype="multipart/form-data">
  <h2>Business Details</h2>
  <div>
    <label for="business_name">Business / Studio Name</label>
    <input id="business_name" name="business_name" value="<?= e($vendor['business_name']) ?>" required>
  </div>
  <div>
    <label for="phone">Phone</label>
    <input id="phone" name="phone" value="<?= e($vendor['phone']) ?>" required>
  </div>
  <div>
    <label for="address">Business Address</label>
    <textarea id="address" name="address"><?= e($vendor['address'] ?? '') ?></textarea>
  </div>

  <h2>KYC Documents</h2>
  <div>
    <label for="pan_doc">PAN Card</label>
    <?php if ($vendor['pan_doc']): ?><p><a class="doc-link" href="<?= e(img_url($vendor['pan_doc'])) ?>" target="_blank">View current PAN document ↗</a></p><?php endif; ?>
    <input id="pan_doc" name="pan_doc" type="file" accept="image/*,.pdf">
  </div>
  <div>
    <label for="aadhar_doc">Aadhar Card</label>
    <?php if ($vendor['aadhar_doc']): ?><p><a class="doc-link" href="<?= e(img_url($vendor['aadhar_doc'])) ?>" target="_blank">View current Aadhar document ↗</a></p><?php endif; ?>
    <input id="aadhar_doc" name="aadhar_doc" type="file" accept="image/*,.pdf">
  </div>
  <div>
    <label for="gst_doc">GST Certificate (optional)</label>
    <?php if ($vendor['gst_doc']): ?><p><a class="doc-link" href="<?= e(img_url($vendor['gst_doc'])) ?>" target="_blank">View current GST document ↗</a></p><?php endif; ?>
    <input id="gst_doc" name="gst_doc" type="file" accept="image/*,.pdf">
  </div>
  <p class="muted">Leave a document field blank to keep what's already on file. Uploading a new document while your account is in "Rejected" status will send it back for review.</p>

  <h2>Bank Details (for monthly settlement)</h2>
  <div>
    <label for="bank_account_name">Account Holder Name</label>
    <input id="bank_account_name" name="bank_account_name" value="<?= e($vendor['bank_account_name'] ?? '') ?>">
  </div>
  <div>
    <label for="bank_account_number">Account Number</label>
    <input id="bank_account_number" name="bank_account_number" value="<?= e($vendor['bank_account_number'] ?? '') ?>">
  </div>
  <div>
    <label for="bank_ifsc">IFSC Code</label>
    <input id="bank_ifsc" name="bank_ifsc" value="<?= e($vendor['bank_ifsc'] ?? '') ?>">
  </div>
  <div>
    <label for="bank_name">Bank Name</label>
    <input id="bank_name" name="bank_name" value="<?= e($vendor['bank_name'] ?? '') ?>">
  </div>

  <button class="btn primary" type="submit">Save Changes</button>
</form>
<?php require_once __DIR__ . '/includes/vendor_footer.php'; ?>
