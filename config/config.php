<?php
declare(strict_types=1);

// ── Site identity ─────────────────────────────────────────────────────────
define('SITE_NAME', 'Steven Picknwear');
define('SITE_TAGLINE', 'The International Brand Outlet');
define('SITE_EMAIL', 'info@stevenpickandwear.co.za');
define('CURRENCY', 'R');
define('CURRENCY_CODE', 'ZAR');

// ── Environment detection ────────────────────────────────────────────────
// Local = Windows/XAMPP dev machine or a localhost hostname; anything else
// (the Linux cPanel host) is production. Works for both web and CLI.
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
define('IS_PRODUCTION', !(
    PHP_OS_FAMILY === 'Windows'
    || str_starts_with($httpHost, 'localhost')
    || str_starts_with($httpHost, '127.0.0.1')
));

// ── Database ─────────────────────────────────────────────────────────────
// Production values live in config/config.local.php on the server (never in Git).
// It just needs the define()s below; see README "Going live".
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
if (IS_PRODUCTION) {
    defined('DB_HOST') || define('DB_HOST', 'localhost');
    defined('DB_NAME') || define('DB_NAME', 'spw_store');
    defined('DB_USER') || define('DB_USER', 'spw_user');
    defined('DB_PASS') || define('DB_PASS', '');
} else {
    defined('DB_HOST') || define('DB_HOST', 'localhost');
    defined('DB_NAME') || define('DB_NAME', 'stevenpickandwear');
    defined('DB_USER') || define('DB_USER', 'root');
    defined('DB_PASS') || define('DB_PASS', '');
}
define('DB_CHARSET', 'utf8mb4');

// ── Auth / JWT ───────────────────────────────────────────────────────────
// DB_PASS is mixed in so the secret can't be derived from readable source alone.
define('JWT_SECRET', 'spw_' . hash('sha256', 'steven-picknwear-2026|' . DB_PASS . '|' . (defined('APP_SECRET') ? APP_SECRET : '')));
define('JWT_EXPIRY', 60 * 60 * 24 * 7); // 7 days
define('JWT_COOKIE_NAME', 'spw_token');

// ── Uploads ──────────────────────────────────────────────────────────────
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 8 * 1024 * 1024); // 8MB (high-res product photography)
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_POP_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);

// ── Catalog ──────────────────────────────────────────────────────────────
define('PRODUCTS_PER_PAGE', 24);

// ── Mail (SMTP) — real values go in config/config.local.php ──────────────
// If the host requires a different SMTP server, change SMTP_HOST to the value
// shown in cPanel → Email Accounts → Connect Devices (often mail.<domain> or
// the server hostname). Port 587 = STARTTLS; use 465 if the host wants SSL.
defined('SMTP_HOST') || define('SMTP_HOST', '');
defined('SMTP_PORT') || define('SMTP_PORT', 587);
defined('SMTP_USER') || define('SMTP_USER', '');
defined('SMTP_PASS') || define('SMTP_PASS', '');
defined('SMTP_FROM_EMAIL') || define('SMTP_FROM_EMAIL', SITE_EMAIL); // must be verified in Brevo
define('SMTP_FROM_NAME', SITE_NAME);

// Where contact-form enquiries and order notifications are sent.
defined('ADMIN_EMAIL') || define('ADMIN_EMAIL', 'info@stevenpickandwear.co.za');

define('DEBUG_MODE', !IS_PRODUCTION);

// ── SITE_URL auto-detection (works unchanged on localhost/XAMPP subfolders
//    and on a live vhost root) ───────────────────────────────────────────
$protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$docRootDir = str_replace('\\', '/', dirname(__DIR__));
$basePath   = '';
if (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') {
    $documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $basePath     = str_replace($documentRoot, '', $docRootDir);
}
define('SITE_URL', rtrim($protocol . $host . $basePath, '/'));

// ── Session ──────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => JWT_EXPIRY,
        'path'     => '/',
        'httponly' => true,
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

error_reporting(DEBUG_MODE ? E_ALL : 0);
ini_set('display_errors', DEBUG_MODE ? '1' : '0');
date_default_timezone_set('Africa/Johannesburg');
