<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

try {
    [$user, $error] = finish_google_login(
        (string) ($_GET['code'] ?? ''),
        (string) ($_GET['state'] ?? '')
    );
} catch (Throwable $e) {
    error_log('AOD Google: ' . $e->getMessage());
    $user = null;
    $error = 'Google sign-in failed.';
}

if (!$user) {
    flash('error', $error ?: 'Google sign-in failed.');
    redirect('pages/login.php');
}

login_user($user);
if (!empty($_SESSION['google_handoff'])) {
    unset($_SESSION['google_handoff']);
    $code = issue_google_handoff((int) $user['id']);
    header('Location: aod://auth?code=' . rawurlencode($code));
    exit;
}

flash('success', 'Signed in with Google.');
redirect(is_admin() ? 'admin/index.php' : 'pages/account.php');
