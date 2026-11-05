<?php
require_once __DIR__ . '/../config.php';

if (is_admin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash']) && $user['is_admin']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = (bool) $user['is_admin'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid email/password, or this account is not an admin.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login — Sandesh Art Marketplace</title>
<link rel="stylesheet" href="https://artarenastudio.com/assets/style.css">
<link rel="stylesheet" href="https://artarenastudio.com/assets/artful-theme.css">
<link rel="stylesheet" href="/assets/marketplace.css?v=<?= ASSET_VERSION ?>">
</head>
<body class="login-body">
  <div class="login-box">
    <a class="brand" href="/index.php" style="justify-content:center;margin-bottom:1rem">
      <img src="https://artarenastudio.com/assets/sandesh-art-arena-logo.jpg" alt="Sandesh Art Arena">
    </a>
    <h1>Marketplace Admin</h1>
    <p class="muted">Sign in to manage products, orders and payments.</p>
    <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
    <form class="login-form" method="post">
      <div>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
      </div>
      <button class="btn primary login-submit" type="submit">Log In →</button>
    </form>
    <p class="muted" style="margin-top:1.5rem"><a href="/index.php">← Back to site</a></p>
  </div>
</body>
</html>
