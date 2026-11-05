<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Login';

if (is_logged_in()) {
    header('Location: account.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = (bool) $user['is_admin'];
        header('Location: account.php');
        exit;
    }
    $error = 'Invalid email or password.';
}

include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <p class="eyebrow">Welcome Back</p>
  <h1><em>Login</em></h1>
</section>
<section class="section-pad">
  <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
  <form class="site-form" method="post" style="max-width:26rem">
    <div>
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
    </div>
    <div>
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>
    </div>
    <button class="button" type="submit">Log In <b>→</b></button>
  </form>
  <p class="form-note">New here? <a href="/register.php">Create an account</a>. Selling your own art? <a href="/vendor/login.php">Vendor Login</a>.</p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
