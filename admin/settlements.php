<?php
$pageTitle = 'Vendor Settlements';
require_once __DIR__ . '/includes/admin_header.php';
$pdo = db();

$monthNames = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate') {
        $month = (int) ($_POST['month'] ?? 0);
        $year = (int) ($_POST['year'] ?? 0);

        // All realized, not-yet-settled items, grouped by vendor.
        $stmt = $pdo->query("
          SELECT oi.vendor_id,
                 SUM(oi.price * oi.qty) AS gross_sales,
                 SUM(oi.commission_amount) AS commission_amount,
                 SUM(oi.vendor_amount) AS net_payable,
                 COUNT(*) AS item_count
          FROM order_items oi
          JOIN orders o ON o.id = oi.order_id
          WHERE oi.vendor_id IS NOT NULL
            AND oi.settlement_id IS NULL
            AND (o.payment_status = 'paid' OR (o.payment_method = 'cod' AND o.order_status = 'delivered'))
          GROUP BY oi.vendor_id
        ");
        $groups = $stmt->fetchAll();

        $upsert = $pdo->prepare("
          INSERT INTO vendor_settlements (vendor_id, period_month, period_year, gross_sales, commission_amount, net_payable, item_count, status)
          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
          ON DUPLICATE KEY UPDATE
            gross_sales = gross_sales + VALUES(gross_sales),
            commission_amount = commission_amount + VALUES(commission_amount),
            net_payable = net_payable + VALUES(net_payable),
            item_count = item_count + VALUES(item_count)
        ");
        $markItems = $pdo->prepare("
          UPDATE order_items oi JOIN orders o ON o.id = oi.order_id
          SET oi.settlement_id = (SELECT id FROM vendor_settlements WHERE vendor_id = ? AND period_month = ? AND period_year = ?)
          WHERE oi.vendor_id = ? AND oi.settlement_id IS NULL
            AND (o.payment_status = 'paid' OR (o.payment_method = 'cod' AND o.order_status = 'delivered'))
        ");

        foreach ($groups as $g) {
            $upsert->execute([$g['vendor_id'], $month, $year, $g['gross_sales'], $g['commission_amount'], $g['net_payable'], $g['item_count']]);
            $markItems->execute([$g['vendor_id'], $month, $year, $g['vendor_id']]);
        }
        header('Location: settlements.php?generated=' . count($groups));
        exit;
    }

    if ($action === 'mark_paid') {
        $id = (int) ($_POST['id'] ?? 0);
        $ref = trim($_POST['payment_ref'] ?? '');
        $pdo->prepare("UPDATE vendor_settlements SET status = 'paid', paid_at = NOW(), payment_ref = ? WHERE id = ?")->execute([$ref ?: null, $id]);
        header('Location: settlements.php');
        exit;
    }
}

$prevMonth = (int) date('n') - 1;
$prevYear = (int) date('Y');
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$pendingUnsettled = (float) $pdo->query("
  SELECT COALESCE(SUM(oi.vendor_amount),0) FROM order_items oi JOIN orders o ON o.id = oi.order_id
  WHERE oi.vendor_id IS NOT NULL AND oi.settlement_id IS NULL
    AND (o.payment_status = 'paid' OR (o.payment_method = 'cod' AND o.order_status = 'delivered'))
")->fetchColumn();

$stmt = $pdo->query("
  SELECT vs.*, v.business_name
  FROM vendor_settlements vs JOIN vendors v ON v.id = vs.vendor_id
  ORDER BY vs.period_year DESC, vs.period_month DESC, v.business_name
");
$settlements = $stmt->fetchAll();
?>
<?php if (isset($_GET['generated'])): ?><div class="flash ok">Settlement generated for <?= (int) $_GET['generated'] ?> vendor(s).</div><?php endif; ?>

<div class="admin-cards">
  <div class="admin-card">
    <p class="admin-card-label">Unsettled Vendor Earnings (realized, not yet in any settlement)</p>
    <p class="admin-card-value">₹<?= number_format($pendingUnsettled, 2) ?></p>
  </div>
</div>

<h3 style="margin-top:1.5rem">Generate Settlement</h3>
<p class="muted">Run this on or after the 7th of the month, for the previous completed month. Re-running for the same month safely adds only newly-realized sales (nothing is double-counted).</p>
<form method="post" style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap">
  <input type="hidden" name="action" value="generate">
  <select name="month" style="padding:.6rem .7rem;border:1px solid var(--line, rgba(23,24,32,.16));border-radius:6px">
    <?php foreach ($monthNames as $num => $label): ?>
      <option value="<?= $num ?>" <?= $num === $prevMonth ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="number" name="year" value="<?= $prevYear ?>" style="width:7rem;padding:.6rem .7rem;border:1px solid var(--line, rgba(23,24,32,.16));border-radius:6px">
  <button class="btn primary" type="submit" onclick="return confirm('Generate settlement for all vendors with realized, unsettled sales?')">Generate Settlement</button>
</form>

<h3 style="margin-top:2rem">Settlement History</h3>
<table class="admin-table">
  <thead><tr><th>Period</th><th>Vendor</th><th>Gross Sales</th><th>Commission</th><th>Net Payable</th><th>Status</th><th>Paid On / Ref</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($settlements as $s): ?>
    <tr>
      <td><?= e($monthNames[(int) $s['period_month']] ?? $s['period_month']) ?> <?= (int) $s['period_year'] ?></td>
      <td><?= e($s['business_name']) ?></td>
      <td>₹<?= number_format((float) $s['gross_sales'], 2) ?></td>
      <td>₹<?= number_format((float) $s['commission_amount'], 2) ?></td>
      <td><strong>₹<?= number_format((float) $s['net_payable'], 2) ?></strong></td>
      <td><span class="status-pill status-<?= e($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span></td>
      <td><?= $s['paid_at'] ? e(date('d M Y', strtotime($s['paid_at']))) . ($s['payment_ref'] ? ' · ' . e($s['payment_ref']) : '') : '—' ?></td>
      <td>
        <?php if ($s['status'] === 'pending'): ?>
          <form method="post" style="display:flex;gap:.3rem">
            <input type="hidden" name="action" value="mark_paid">
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <input name="payment_ref" placeholder="UTR / ref no." style="width:8rem;padding:.4rem .5rem;border:1px solid var(--line, rgba(23,24,32,.16));border-radius:6px;font-size:.78rem">
            <button class="btn primary" type="submit" style="padding:.4rem .7rem;font-size:.75rem" onclick="return confirm('Mark this settlement as paid? Only do this after transferring the amount to the vendor\'s bank account.')">Mark Paid</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$settlements): ?><tr><td colspan="8">No settlements generated yet.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
