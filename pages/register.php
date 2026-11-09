<?php
/**
 * Art on Denim — Registration
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_guest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors['form'] = 'Invalid security token. Please try again.';
        store_old($_POST);
    } else {
        [$fieldErrors, $userId] = register_customer($_POST);
        $errors = $fieldErrors;

        if ($errors === [] && $userId) {
            $user = find_user_by_id($userId);
            if ($user) {
                login_user($user);
                clear_old();
                flash('success', 'Your account has been created successfully.');
                redirect('pages/account.php');
            }
        }

        store_old($_POST);
    }
}

$pageTitle = 'Create Account';
$pageDescription = 'Register for an Art on Denim account.';
$bodyClass = 'page-auth';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section auth-section">
    <div class="container auth-layout">
        <header class="auth-intro">
            <p class="eyebrow">Join AOD</p>
            <h1>Create account</h1>
            <p>Track orders, save favourites, and checkout faster.</p>
        </header>

        <form class="auth-form" method="post" action="<?= e(url('pages/register.php')) ?>" novalidate>
            <?= csrf_field() ?>

            <?php if (!empty($errors['form'])): ?>
                <div class="form-errors" role="alert">
                    <p><?= e($errors['form']) ?></p>
                </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-field">
                    <label for="first_name">First name</label>
                    <input type="text" id="first_name" name="first_name" value="<?= old('first_name') ?>" required maxlength="80" autocomplete="given-name">
                    <?php if (!empty($errors['first_name'])): ?>
                        <p class="field-error"><?= e($errors['first_name']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="last_name">Last name</label>
                    <input type="text" id="last_name" name="last_name" value="<?= old('last_name') ?>" required maxlength="80" autocomplete="family-name">
                    <?php if (!empty($errors['last_name'])): ?>
                        <p class="field-error"><?= e($errors['last_name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-field">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" required maxlength="190" autocomplete="email">
                <?php if (!empty($errors['email'])): ?>
                    <p class="field-error"><?= e($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="phone">Phone <span class="optional">(optional)</span></label>
                <input type="tel" id="phone" name="phone" value="<?= old('phone') ?>" maxlength="30" autocomplete="tel">
                <?php if (!empty($errors['phone'])): ?>
                    <p class="field-error"><?= e($errors['phone']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">
                    <?php if (!empty($errors['password'])): ?>
                        <p class="field-error"><?= e($errors['password']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="password_confirm">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8" maxlength="72" autocomplete="new-password">
                    <?php if (!empty($errors['password_confirm'])): ?>
                        <p class="field-error"><?= e($errors['password_confirm']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn--inverse btn--block">Create account</button>

            <p class="auth-switch">
                Already have an account?
                <a href="<?= e(url('pages/login.php')) ?>">Sign in</a>
            </p>
        </form>
    </div>
</main>

<?php
clear_old();
require dirname(__DIR__) . '/includes/footer.php';
?>
