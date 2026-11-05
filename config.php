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
        $stmt = db()->prepare('SELECT id, name, email, phone, role, is_admin FROM users WHERE id = ?');
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

/**
 * Handles an optional image file upload for a form field.
 * Returns the stored relative path (e.g. "uploads/products/img_xxx.jpg"), or keeps
 * $existing unchanged if no new valid file was uploaded (so edits don't wipe the image).
 */
function handle_upload(string $field, ?string $existing, string $subdir = 'uploads/products'): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return $existing;
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return $existing;
    }
    $dir = __DIR__ . '/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = 'img_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $name)) {
        return $existing;
    }
    return $subdir . '/' . $name;
}

/** Same as handle_upload() but also allows PDFs, for KYC/ID documents. */
function handle_doc_upload(string $field, ?string $existing, string $subdir = 'uploads/vendor_docs'): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return $existing;
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return $existing;
    }
    $dir = __DIR__ . '/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = 'doc_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $name)) {
        return $existing;
    }
    return $subdir . '/' . $name;
}

/** The vendor profile row for the logged-in user, or null if not a vendor. */
function current_vendor(): ?array {
    if (!is_logged_in()) return null;
    static $v = null;
    static $loaded = false;
    if (!$loaded) {
        $stmt = db()->prepare('SELECT * FROM vendors WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $v = $stmt->fetch() ?: null;
        $loaded = true;
    }
    return $v;
}

function is_vendor(): bool {
    return current_vendor() !== null;
}

function require_vendor(): array {
    $v = current_vendor();
    if (!$v) {
        header('Location: /vendor/login.php');
        exit;
    }
    return $v;
}

/** Renders a stored image path correctly whether it's an old full URL or a new relative upload path. */
function img_url(?string $path): string {
    if (!$path) return '';
    if (preg_match('#^https?://#i', $path)) return $path;
    return '/' . ltrim($path, '/');
}

/** Commission % that applies to a given vendor row (its own override, else the store default). */
function vendor_commission_pct(array $vendor): float {
    if ($vendor['commission_pct'] !== null) {
        return (float) $vendor['commission_pct'];
    }
    $s = msettings();
    return (float) ($s['default_commission_pct'] ?? 10);
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
