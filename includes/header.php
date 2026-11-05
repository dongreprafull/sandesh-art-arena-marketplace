<?php
require_once __DIR__ . '/../config.php';
$current = basename($_SERVER['PHP_SELF']);
$nav = [
    'index.php' => 'Home',
    'shop.php' => 'Shop',
    'track-order.php' => 'Track Order',
    'contact.php' => 'Contact',
];
$cartQty = cart_count();
$loggedInUser = current_user();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — Sandesh Art Marketplace' : 'Sandesh Art Marketplace' ?></title>
<meta name="description" content="<?= e($pageDescription ?? 'Sandesh Art Marketplace — original paintings, prints and handmade crafts from Sandesh Art Arena\'s studio and partner artisans.') ?>">
<link rel="stylesheet" href="https://artarenastudio.com/assets/style.css">
<link rel="stylesheet" href="https://artarenastudio.com/assets/artful-theme.css">
<link rel="stylesheet" href="/assets/marketplace.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="header">
  <a class="brand" href="/index.php">
    <img src="https://artarenastudio.com/assets/sandesh-art-arena-logo.jpg" alt="Sandesh Art Arena">
    <i>Art Marketplace</i>
  </a>
  <nav id="siteNav">
    <?php foreach ($nav as $file => $label): ?>
      <a href="/<?= $file ?>" class="<?= $current === $file ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <?php if ($loggedInUser): ?>
      <a href="/account.php" class="<?= $current === 'account.php' ? 'active' : '' ?>">My Account</a>
    <?php else: ?>
      <a href="/login.php" class="<?= $current === 'login.php' ? 'active' : '' ?>">Login</a>
      <a href="/vendor/register.php">Sell With Us</a>
    <?php endif; ?>
    <a href="/cart.php" class="nav-cta">Cart<?= $cartQty > 0 ? ' (' . $cartQty . ')' : '' ?></a>
  </nav>
  <button class="menu-button" id="menuBtn" aria-label="Menu">Menu</button>
</header>
<main id="main">
