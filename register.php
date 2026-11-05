<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Create Account';

if (is_logged_in()) {
    header('Location: account.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in your name, email and password.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists. Try logging in instead.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, role, is_admin) VALUES (?, ?, ?, ?, ?, 0)')
                ->execute([$name, $email, $phone ?: null, $hash, 'customer']);
            $userId = (int) $pdo->lastInsertId();
            $_SESSION['user_id'] = $userId;
            $_SESSION['is_admin'] = false;
            header('Location: account.php');
            exit;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <p class="eyebrow">Join Us</p>
  <h1>Create <em>Account</em></h1>
  <p>Register to track your orders and check out faster next time.</p>
</section>
<section class="section-pad">
  <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
  <form class="site-form" method="post" style="max-width:28rem">
    <div>
      <label for="name">Full Name</label>
      <input id="name" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
    </div>
    <div>
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>">
    </div>
    <div>
      <label for="phone">Phone</label>
      <input id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
    </div>
    <div>
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required minlength="6">
    </div>
    <button class="button" type="submit">Create Account <b>→</b></button>
  </form>
  <p class="form-note">Already have an account? <a href="/login.php">Log in</a>. Selling your own art? <a href="/vendor/register.php">Register as a Vendor</a> instead.</p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
