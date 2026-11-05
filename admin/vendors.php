<?php
$pageTitle = 'Vendors';
require_once __DIR__ . '/includes/admin_header.php';
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($postId && $action === 'approve') {
        $pdo->prepare("UPDATE vendors SET status = 'approved', rejection_reason = NULL WHERE id = ?")->execute([$postId]);
    } elseif ($postId && $action === 'reject') {
        $reason = trim($_POST['reason'] ?? '') ?: 'Documents could not be verified.';
        $pdo->prepare("UPDATE vendors SET status = 'rejected', rejection_reason = ? WHERE id = ?")->execute([$reason, $postId]);
    } elseif ($postId && $action === 'set_commission') {
        $pct = trim($_POST['commission_pct'] ?? '');
        $pdo->prepare("UPDATE vendors SET commission_pct = ? WHERE id = ?")->execute([$pct === '' ? null : (float) $pct, $postId]);
    }
    header('Location: vendors.php' . ($id ? '?id=' . $id : ''));
    exit;
}

if ($id) {
    $stmt = $pdo->prepare("SELECT v.*, u.name AS owner_name, u.email AS owner_email FROM vendors v JOIN users u ON u.id = v.user_id WHERE v.id = ?");
    $stmt->execute([$id]);
    $v = $stmt->fetch();
    if (!$v) {
        echo '<p>Vendor not found. <a href="vendors.php">Back to Vendors</a></p>';
        require_once __DIR__ . '/includes/admin_footer.php';
        exit;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE vendor_id = ?");
    $stmt->execute([$id]);
    $productCount = (int) $stmt->fetchColumn();
    $s = db()->query("SELECT default_commission_pct FROM marketplace_settings WHERE id = 1")->fetch();
    ?>
    <p><a href="vendors.php">← Back to Vendors</a></p>
    <h2><?= e($v['business_name']) ?> <span class="status-pill status-<?= e($v['status']) ?>"><?= e(ucfirst($v['status'])) ?></span></h2>

    <div class="admin-cards">
      <div class="admin-card">
        <p class="admin-card-label">Contact</p>
        <p style="font-weight:700"><?= e($v['owner_name']) ?></p>
        <p class="muted"><?= e($v['owner_email']) ?> · <?= e($v['phone']) ?></p>
      </div>
      <div class="admin-card">
        <p class="admin-card-label">Address</p>
        <p><?= e($v['address'] ?: '—') ?></p>
      </div>
      <div class="admin-card">
        <p class="admin-card-label">Products Listed</p>
        <p class="admin-card-value"><?= $productCount ?></p>
      </div>
    </div>

    <h3 style="margin-top:1.5rem">KYC Documents</h3>
    <p>
      <?php if ($v['pan_doc']): ?><a class="btn ghost" href="../<?= e($v['pan_doc']) ?>" target="_blank">View PAN ↗</a><?php endif; ?>
      <?php if ($v['aadhar_doc']): ?><a class="btn ghost" href="../<?= e($v['aadhar_doc']) ?>" target="_blank">View Aadhar ↗</a><?php endif; ?>
      <?php if ($v['gst_doc']): ?><a class="btn ghost" href="../<?= e($v['gst_doc']) ?>" target="_blank">View GST ↗</a><?php endif; ?>
      <?php if (!$v['pan_doc'] && !$v['aadhar_doc'] && !$v['gst_doc']): ?><span class="muted">No documents uploaded.</span><?php endif; ?>
    </p>

    <h3 style="margin-top:1.5rem">Bank Details</h3>
    <table class="admin-table" style="max-width:520px">
      <tr><td>Account Holder</td><td><?= e($v['bank_account_name'] ?? '—') ?></td></tr>
      <tr><td>Account Number</td><td><?= e($v['bank_account_number'] ?? '—') ?></td></tr>
      <tr><td>IFSC</td><td><?= e($v['bank_ifsc'] ?? '—') ?></td></tr>
      <tr><td>Bank</td><td><?= e($v['bank_name'] ?? '—') ?></td></tr>
    </table>

    <?php if ($v['status'] === 'pending'): ?>
      <h3 style="margin-top:1.5rem">Review Application</h3>
      <form method="post" style="display:inline-block;margin-right:.6rem">
        <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
        <input type="hidden" name="action" value="approve">
        <button class="btn primary" type="submit" onclick="return confirm('Approve this vendor? Their products will start appearing in the shop.')">Approve Vendor</button>
      </form>
      <form method="post" style="display:inline-flex;gap:.4rem;align-items:center">
        <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
        <input type="hidden" name="action" value="reject">
        <input name="reason" placeholder="Reason for rejection (optional)" style="padding:.5rem .6rem;border:1px solid var(--line, rgba(23,24,32,.16));border-radius:6px">
        <button class="btn danger" type="submit" onclick="return confirm('Reject this vendor application?')">Reject</button>
      </form>
    <?php elseif ($v['status'] === 'rejected'): ?>
      <p class="muted">Rejected: <?= e($v['rejection_reason'] ?? '') ?></p>
      <form method="post" style="display:inline">
        <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
        <input type="hidden" name="action" value="approve">
        <button class="btn primary" type="submit">Approve Anyway</button>
      </form>
    <?php endif; ?>

    <h3 style="margin-top:1.5rem">Commission Override</h3>
    <form method="post" style="display:flex;gap:.6rem;align-items:center">
      <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
      <input type="hidden" name="action" value="set_commission">
      <input name="commission_pct" type="number" step="0.01" min="0" max="100" value="<?= e($v['commission_pct'] !== null ? (string) $v['commission_pct'] : '') ?>" placeholder="Default (<?= e((string) ($s['default_commission_pct'] ?? 10)) ?>%)" style="max-width:200px;padding:.6rem .7rem;border:1px solid var(--line, rgba(23,24,32,.16));border-radius:6px">
      <button class="btn primary" type="submit">Save %</button>
    </form>
    <p class="muted">Leave blank to use the store default (<?= e((string) ($s['default_commission_pct'] ?? 10)) ?>%, set on Store Settings).</p>
    <?php
} else {
    $statusFilter = $_GET['status'] ?? '';
    $sql = "SELECT v.*, u.name AS owner_name, u.email AS owner_email FROM vendors v JOIN users u ON u.id = v.user_id";
    $params = [];
    if (in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
        $sql .= " WHERE v.status = ?";
        $params[] = $statusFilter;
    }
    $sql .= " ORDER BY v.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vendors = $stmt->fetchAll();
    ?>
    <div class="admin-topbar-actions" style="margin-bottom:1rem">
      <a class="btn <?= $statusFilter === '' ? 'primary' : 'ghost' ?>" href="vendors.php">All</a>
      <a class="btn <?= $statusFilter === 'pending' ? 'primary' : 'ghost' ?>" href="vendors.php?status=pending">Pending Approval</a>
      <a class="btn <?= $statusFilter === 'approved' ? 'primary' : 'ghost' ?>" href="vendors.php?status=approved">Approved</a>
      <a class="btn <?= $statusFilter === 'rejected' ? 'primary' : 'ghost' ?>" href="vendors.php?status=rejected">Rejected</a>
    </div>
    <table class="admin-table">
      <thead><tr><th>Business</th><th>Contact</th><th>Phone</th><th>Commission</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($vendors as $vv): ?>
        <tr>
          <td><?= e($vv['business_name']) ?></td>
          <td><?= e($vv['owner_name']) ?><br><small class="muted"><?= e($vv['owner_email']) ?></small></td>
          <td><?= e($vv['phone']) ?></td>
          <td><?= $vv['commission_pct'] !== null ? e((string) $vv['commission_pct']) . '%' : 'Default' ?></td>
          <td><span class="status-pill status-<?= e($vv['status']) ?>"><?= e(ucfirst($vv['status'])) ?></span></td>
          <td><a class="btn ghost" href="vendors.php?id=<?= (int) $vv['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$vendors): ?><tr><td colspan="6">No vendors found.</td></tr><?php endif; ?>
      </tbody>
    </table>
    <?php
}
require_once __DIR__ . '/includes/admin_footer.php';
