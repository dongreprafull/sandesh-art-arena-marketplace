<?php
require_once __DIR__ . '/../../config.php';
$vendor = require_vendor();
$u = current_user();
$current = basename($_SERVER['PHP_SELF']);

function vnav_is_active(string $href, string $current): bool {
    return $current === $href;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — Vendor Dashboard' : 'Vendor Dashboard' ?></title>
<link rel="stylesheet" href="https://artarenastudio.com/assets/style.css">
<link rel="stylesheet" href="https://artarenastudio.com/assets/artful-theme.css">
<link rel="stylesheet" href="/assets/marketplace.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
<div class="admin-topbar">
  <a class="brand" href="dashboard.php">
    <img src="https://artarenastudio.com/assets/sandesh-art-arena-logo.jpg" alt="Sandesh Art Arena">
    <i>Vendor Dashboard</i>
  </a>
  <div class="admin-topbar-actions">
    <a class="btn ghost" href="https://market.artarenastudio.com/" target="_blank" rel="noopener">View Site ↗</a>
    <a class="btn danger" href="logout.php">Logout (<?= e($u['name'] ?? $vendor['business_name']) ?>)</a>
  </div>
</div>
<div class="admin-shell">
  <aside class="admin-side">
    <p class="admin-side-group">Overview</p>
    <a href="dashboard.php" class="<?= vnav_is_active('dashboard.php', $current) ? 'active' : '' ?>">Dashboard</a>

    <p class="admin-side-group">Catalog</p>
    <a href="products.php" class="<?= vnav_is_active('products.php', $current) ? 'active' : '' ?>">My Products</a>

    <p class="admin-side-group">Sales</p>
    <a href="orders.php" class="<?= vnav_is_active('orders.php', $current) ? 'active' : '' ?>">My Orders</a>
    <a href="settlements.php" class="<?= vnav_is_active('settlements.php', $current) ? 'active' : '' ?>">Settlements</a>

    <p class="admin-side-group">Account</p>
    <a href="profile.php" class="<?= vnav_is_active('profile.php', $current) ? 'active' : '' ?>">Profile &amp; Bank Details</a>
  </aside>
  <div class="admin-wrap">
    <?php if ($vendor['status'] === 'pending'): ?>
      <div class="flash err" style="background:#fff3da;color:#8a5a00;border-color:#ffe3a3">Your vendor account is <strong>pending approval</strong>. You can fill in your profile and bank details, but your products won't go live until the admin approves your KYC documents.</div>
    <?php elseif ($vendor['status'] === 'rejected'): ?>
      <div class="flash err">Your vendor application was <strong>rejected</strong><?= $vendor['rejection_reason'] ? ': ' . e($vendor['rejection_reason']) : '.' ?> Please update your documents on the <a href="profile.php">Profile</a> page and we'll review again.</div>
    <?php endif; ?>
