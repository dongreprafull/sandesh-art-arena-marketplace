<?php
require_once __DIR__ . '/../../config.php';
require_admin();
$u = current_user();
$current = basename($_SERVER['PHP_SELF']);

function nav_is_active(string $href, string $current, string $table = ''): bool {
    if ($table !== '') {
        return $current === 'content.php' && ($_GET['table'] ?? '') === $table;
    }
    return $current === $href;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — Marketplace Admin' : 'Marketplace Admin' ?></title>
<link rel="stylesheet" href="https://artarenastudio.com/assets/style.css">
<link rel="stylesheet" href="https://artarenastudio.com/assets/artful-theme.css">
<link rel="stylesheet" href="/assets/marketplace.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
<div class="admin-topbar">
  <a class="brand" href="dashboard.php">
    <img src="https://artarenastudio.com/assets/sandesh-art-arena-logo.jpg" alt="Sandesh Art Arena">
    <i>Marketplace Admin</i>
  </a>
  <div class="admin-topbar-actions">
    <a class="btn ghost" href="https://market.artarenastudio.com/" target="_blank" rel="noopener">View Site ↗</a>
    <a class="btn danger" href="logout.php">Logout (<?= e($u['name'] ?? 'Admin') ?>)</a>
  </div>
</div>
<div class="admin-shell">
  <aside class="admin-side">
    <p class="admin-side-group">Overview</p>
    <a href="dashboard.php" class="<?= nav_is_active('dashboard.php', $current) ? 'active' : '' ?>">Dashboard</a>

    <p class="admin-side-group">Sales</p>
    <a href="orders.php" class="<?= nav_is_active('orders.php', $current) ? 'active' : '' ?>">Orders</a>
    <a href="enquiries.php" class="<?= nav_is_active('enquiries.php', $current) ? 'active' : '' ?>">Enquiries</a>

    <p class="admin-side-group">Catalog</p>
    <a href="content.php?table=products" class="<?= nav_is_active('', $current, 'products') ? 'active' : '' ?>">Products</a>

    <p class="admin-side-group">Settings</p>
    <a href="settings.php" class="<?= nav_is_active('settings.php', $current) ? 'active' : '' ?>">Store Settings</a>
  </aside>
  <div class="admin-wrap">
