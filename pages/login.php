<?php
/**
 * Art on Denim — Login
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_guest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $error = attempt_login($email, $password);

        if ($error) {
            $errors[] = $error;
            store_old(['email' => $email]);
        } else {
            clear_old();
            flash('success', 'Welcome back to Art on Denim.');

            $redirect = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);

            $basePrefix = rtrim(BASE_URL, '/') . '/';
            if ($redirect && strpos($redirect, $basePrefix) === 0) {
                header('Location: ' . $redirect);
                exit;
            }

            if (is_admin()) {
                redirect('admin/index.php');
            }

            redirect('pages/account.php');
        }
    }
}

$pageTitle = 'Sign In';
$pageDescription = 'Sign in to your Art on Denim account.';
$bodyClass = 'page-auth';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section auth-section">
    <div class="container auth-layout">
        <header class="auth-intro">
            <p class="eyebrow">Account</p>
            <h1>Sign in</h1>
            <p>Access your orders, wishlist, and saved details.</p>
        </header>

        <form class="auth-form" method="post" action="<?= e(url('pages/login.php')) ?>" novalidate>
            <?= csrf_field() ?>

            <?php if ($errors): ?>
                <div class="form-errors" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="form-field">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= old('email') ?>"
                    required
                    autocomplete="email"
                    autofocus
                >
            </div>

            <div class="form-field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    minlength="8"
                >
            </div>

            <button type="submit" class="btn btn--inverse btn--block">Sign in</button>

            <?php if (google_is_configured()): ?>
                <a class="btn btn--brass btn--block" href="<?= e(url('pages/google-start.php')) ?>">Continue with Google</a>
            <?php else: ?>
                <p class="auth-switch">Google sign-in is ready once <code>config/google.local.php</code> has a client id and secret.</p>
            <?php endif; ?>

            <p class="auth-switch">
                New to Art on Denim?
                <a href="<?= e(url('pages/register.php')) ?>">Create an account</a>
            </p>
        </form>
    </div>
</main>

<?php
clear_old();
require dirname(__DIR__) . '/includes/footer.php';
?>
