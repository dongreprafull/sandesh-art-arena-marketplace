<?php
/**
 * Shared config: same DB + same session domain as the Personal site and Foundation,
 * so one login (users table) works across every artarenastudio.com subdomain.
 */

$cookieDomain = '.artarenastudio.com';
session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 14,
    'path'     => '/',
    'domain'   => $cookieDomain,
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('ARTARENA_SESSION');
session_start();

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'u609460025_artarena_core');
define('DB_USER', 'u609460025_artarena_adm');
define('DB_PASS', 'Snd3sh#ArtVault92q');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return is_logged_in() && !empty($_SESSION['is_admin']);
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $u = null;
    if ($u === null) {
        $stmt = db()->prepare('SELECT id, name, email, role, is_admin FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $u = $stmt->fetch() ?: null;
    }
    return $u;
}

/** Singleton marketplace settings row (id=1): UPI/Razorpay config, COD toggle, shipping note. */
function msettings(): array {
    static $s = null;
    if ($s === null) {
        $s = db()->query("SELECT * FROM marketplace_settings WHERE id = 1")->fetch() ?: [];
    }
    return $s;
}

/** Cart lives in the shared session under its own key, so it never collides with login/other portals. */
function cart(): array {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function cart_count(): int {
    return array_sum(cart());
}

define('ASSET_VERSION', '1');
