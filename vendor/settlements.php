<?php
$pageTitle = 'Settlements';
require_once __DIR__ . '/includes/vendor_header.php';
$pdo = db();
$vid = (int) $vendor['id'];

$stmt = $pdo->prepare("SELECT * FROM vendor_settlements WHERE vendor_id = ? ORDER BY period_year DESC, period_month DESC");
$stmt->execute([$vid]);
$settlements = $stmt->fetchAll();

$monthNames = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
?>
<p class="muted" style="margin-bottom:1rem">The admin generates settlements for each completed month on the 7th. Once marked "Paid", the amount has been transferred to your registered bank account.</p>
<table class="admin-table">
  <thead><tr><th>Period</th><th>Gross Sales</th><th>Commission</th><th>Net Payable</th><th>Status</th><th>Paid On</th><th>Reference</th></tr></thead>
  <tbody>
  <?php foreach ($settlements as $s): ?>
    <tr>
      <td><?= e($monthNames[(int) $s['period_month']] ?? $s['period_month']) ?> <?= (int) $s['period_year'] ?></td>
      <td>₹<?= number_format((float) $s['gross_sales'], 2) ?></td>
      <td>₹<?= number_format((float) $s['commission_amount'], 2) ?></td>
      <td><strong>₹<?= number_format((float) $s['net_payable'], 2) ?></strong></td>
      <td><span class="status-pill status-<?= e($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span></td>
      <td><?= $s['paid_at'] ? e(date('d M Y', strtotime($s['paid_at']))) : '—' ?></td>
      <td><?= e($s['payment_ref'] ?? '—') ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$settlements): ?><tr><td colspan="7">No settlements generated yet.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/includes/vendor_footer.php'; ?>
