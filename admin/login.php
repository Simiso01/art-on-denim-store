<?php
/**
 * Admin login
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin_guest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $error = attempt_admin_login($email, $password);
        if ($error) {
            $errors[] = $error;
            store_old(['email' => $email]);
        } else {
            clear_old();
            flash('success', 'Welcome to the AOD admin panel.');
            $redirect = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            $adminPrefix = rtrim(BASE_URL, '/') . '/admin/';
            if ($redirect && strpos($redirect, $adminPrefix) === 0) {
                header('Location: ' . $redirect);
                exit;
            }
            redirect('admin/index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Admin Sign In | Art on Denim</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
<main class="admin-login">
    <div class="admin-login__card">
        <p class="admin-topbar__eyebrow">Art on Denim</p>
        <h1>Admin sign in</h1>
        <p class="muted">Restricted access for store administrators.</p>

        <?php if ($errors): ?>
            <div class="admin-panel" style="margin:1rem 0;border-color:rgba(196,92,72,.4);">
                <?php foreach ($errors as $error): ?>
                    <p class="field-error"><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('admin/login.php')) ?>" class="admin-form" novalidate>
            <?= csrf_field() ?>
            <div class="admin-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= old('email') ?>" autocomplete="username">
            </div>
            <div class="admin-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="admin-btn">Sign in</button>
        </form>
        <p class="muted" style="margin-top:1.25rem;">
            <a href="<?= e(url('index.php')) ?>">Back to storefront</a>
        </p>
    </div>
</main>
<?php clear_old(); ?>
</body>
</html>
