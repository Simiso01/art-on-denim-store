<?php
/**
 * Art on Denim — Application Configuration
 */

declare(strict_types=1);

// Prevent direct access
if (basename($_SERVER['PHP_SELF'] ?? '') === 'config.php') {
    http_response_code(403);
    exit('Forbidden');
}

// Environment — set APP_ENV=production on live servers (or via env vars)
define('APP_NAME', 'Art on Denim');
define('APP_SHORT', 'AOD');
define('APP_TAGLINE', 'Premium Streetwear & Designer Fashion');

$host = $_SERVER['HTTP_HOST'] ?? '';
$isHosted = $host !== '' && !preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host);

$appEnv = getenv('APP_ENV');
if ($appEnv === false || $appEnv === '') {
    $appEnv = $isHosted ? 'production' : 'development';
}
define('APP_ENV', $appEnv); // development | production
define('APP_DEBUG', APP_ENV === 'development');

// Base URL: '' at domain root (hosted); '/art-on-denim' for local WAMP/XAMPP
$baseUrlEnv = getenv('BASE_URL');
if ($baseUrlEnv !== false) {
    define('BASE_URL', $baseUrlEnv);
} else {
    define('BASE_URL', $isHosted ? '' : '/art-on-denim');
}
define('BASE_PATH', dirname(__DIR__));

// Session
define('SESSION_NAME', 'aod_session');
define('SESSION_LIFETIME', 7200); // 2 hours

// Security
define('CSRF_TOKEN_NAME', 'aod_csrf');
define('PASSWORD_ALGO', PASSWORD_DEFAULT);
define('PASSWORD_MAX_LENGTH', 72);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 900); // 15 minutes

// Uploads
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_MIME', [
    'image/jpeg',
    'image/png',
    'image/webp',
]);
define('UPLOAD_ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('UPLOAD_PRODUCTS_DIR', BASE_PATH . '/uploads/products');

// Commerce
define('CURRENCY_SYMBOL', 'R');
define('CURRENCY_CODE', 'ZAR');
define('DEFAULT_SHIPPING', 99.00);
define('FREE_SHIPPING_THRESHOLD', 1500.00);
define('LOW_STOCK_THRESHOLD', 5);
define('ORDER_PREFIX', 'AOD');
define('TAX_RATE', 0.15); // VAT 15% (included display; adjust as needed)

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);

// Timezone
date_default_timezone_set('Africa/Johannesburg');

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if ($https) {
        ini_set('session.cookie_secure', '1');
    }

    session_name(SESSION_NAME);
    session_start([
        'cookie_lifetime' => SESSION_LIFETIME,
        'gc_maxlifetime'  => SESSION_LIFETIME,
        'cookie_secure'   => $https,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}
