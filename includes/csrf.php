<?php
/**
 * Art on Denim — CSRF protection
 */

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

function csrf_token(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    $sessionToken = $_SESSION[CSRF_TOKEN_NAME] ?? '';

    if ($token === '' || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function require_csrf(): void
{
    if (!csrf_verify()) {
        flash('error', 'Invalid security token. Please try again.');
        redirect('index.php');
    }
}

/**
 * Allow only safe relative app URLs (blocks javascript:, //, external http).
 */
function safe_app_url(?string $path, string $fallback = 'pages/shop.php'): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return $fallback;
    }

    $lower = strtolower($path);
    if (
        strpos($lower, 'javascript:') === 0
        || strpos($lower, 'data:') === 0
        || strpos($path, '//') === 0
        || preg_match('#^[a-z][a-z0-9+.-]*:#i', $path)
    ) {
        return $fallback;
    }

    // Strip leading slashes / base url then rebuild relatively
    $path = ltrim($path, '/');
    $base = ltrim(BASE_URL, '/');
    if ($base !== '' && strpos($path, $base . '/') === 0) {
        $path = substr($path, strlen($base) + 1);
    }

    return $path !== '' ? $path : $fallback;
}
