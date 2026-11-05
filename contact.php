<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Contact';
$pdo = db();
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $message === '') {
        $error = 'Name and message are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO contact_enquiries (name, phone, email, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $phone ?: null, $email ?: null, $message]);
        $success = true;
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <p class="eyebrow">Get in touch</p>
  <h1>Contact <em>Us</em></h1>
</section>
<section class="section-pad">
  <?php if ($success): ?>
    <div class="flash ok">Thanks for reaching out — we'll get back to you soon.</div>
  <?php else: ?>
    <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
    <form class="site-form" method="post">
      <div>
        <label for="name">Name</label>
        <input id="name" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
      </div>
      <div>
        <label for="phone">Phone (optional)</label>
        <input id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
      </div>
      <div>
        <label for="email">Email (optional)</label>
        <input id="email" name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div>
        <label for="message">Message</label>
        <textarea id="message" name="message" required></textarea>
      </div>
      <button class="button" type="submit">Send <b>→</b></button>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
