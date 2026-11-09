<?php
/**
 * Art on Denim — Admin authentication
 */

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

function require_admin(): void
{
    $user = refresh_session_user();

    if ($user === null) {
        flash('error', 'Please sign in as an administrator.');
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('admin/index.php');
        redirect('admin/login.php');
    }

    if (($user['role'] ?? '') !== 'admin' || ($user['status'] ?? '') !== 'active') {
        flash('error', 'Administrator access required.');
        redirect('index.php');
    }
}

function require_admin_guest(): void
{
    $user = refresh_session_user();
    if ($user !== null && ($user['role'] ?? '') === 'admin') {
        redirect('admin/index.php');
    }
}

/**
 * Attempt admin-only login.
 */
function attempt_admin_login(string $email, string $password): ?string
{
    $error = attempt_login($email, $password);
    if ($error) {
        return $error;
    }

    $user = refresh_session_user();
    if ($user === null || ($user['role'] ?? '') !== 'admin') {
        logout_user();
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
        return 'Administrator access required.';
    }

    return null;
}
