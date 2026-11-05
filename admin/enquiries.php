<?php
$pageTitle = 'Enquiries';
require_once __DIR__ . '/includes/admin_header.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id && ($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM contact_enquiries WHERE id = ?")->execute([$id]);
    }
    header('Location: enquiries.php');
    exit;
}

$enquiries = $pdo->query("SELECT * FROM contact_enquiries ORDER BY created_at DESC")->fetchAll();
?>
<p class="muted" style="margin-bottom:1rem">This list is shared across all portals — enquiries from any site's contact form appear here.</p>
<table class="admin-table">
  <thead><tr><th>Date</th><th>Name</th><th>Phone</th><th>Email</th><th>Message</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($enquiries as $en): ?>
    <tr>
      <td><?= e(date('d M Y', strtotime($en['created_at']))) ?></td>
      <td><?= e($en['name']) ?></td>
      <td><?= e($en['phone'] ?? '—') ?></td>
      <td><?= e($en['email'] ?? '—') ?></td>
      <td><?= e($en['message']) ?></td>
      <td>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete this enquiry?')">
          <input type="hidden" name="id" value="<?= (int) $en['id'] ?>">
          <input type="hidden" name="action" value="delete">
          <button class="btn danger" type="submit">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$enquiries): ?><tr><td colspan="6">No enquiries yet.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
