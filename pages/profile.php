<?php
/**
 * Art on Denim — Profile & address settings
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$userId = (int) current_user()['id'];
$profile = find_user_by_id($userId);
$errors = [];
$passwordErrors = [];

if (!$profile) {
    flash('error', 'Account not found.');
    redirect('pages/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors['form'] = 'Invalid security token. Please try again.';
    } else {
        $form = $_POST['form'] ?? 'profile';

        if ($form === 'password') {
            $passwordErrors = change_password(
                $userId,
                (string) ($_POST['current_password'] ?? ''),
                (string) ($_POST['new_password'] ?? ''),
                (string) ($_POST['new_password_confirm'] ?? '')
            );
            if ($passwordErrors === []) {
                flash('success', 'Your password has been updated. Please sign in again on other devices.');
                redirect('pages/profile.php');
            }
        } else {
            $errors = update_customer_profile($userId, $_POST);
            if ($errors === []) {
                flash('success', 'Your profile has been updated.');
                redirect('pages/profile.php');
            }
            $profile = array_merge($profile, [
                'first_name'    => $_POST['first_name'] ?? $profile['first_name'],
                'last_name'     => $_POST['last_name'] ?? $profile['last_name'],
                'phone'         => $_POST['phone'] ?? $profile['phone'],
                'address_line1' => $_POST['address_line1'] ?? $profile['address_line1'],
                'address_line2' => $_POST['address_line2'] ?? $profile['address_line2'],
                'city'          => $_POST['city'] ?? $profile['city'],
                'province'      => $_POST['province'] ?? $profile['province'],
                'postal_code'    => $_POST['postal_code'] ?? $profile['postal_code'],
            ]);
        }
    }
}

$pageTitle = 'Profile';
$pageDescription = 'Manage your Art on Denim profile.';
$bodyClass = 'page-profile';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container" style="max-width:760px;">
        <header class="section-header">
            <div>
                <p class="eyebrow">Account</p>
                <h1>Profile</h1>
                <p>Update your personal details, shipping address, and password.</p>
            </div>
            <a class="section-link" href="<?= e(url('pages/account.php')) ?>">Back to account</a>
        </header>

        <form class="auth-form" method="post" action="<?= e(url('pages/profile.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="profile">

            <?php if (!empty($errors['form'])): ?>
                <div class="form-errors" role="alert"><p><?= e($errors['form']) ?></p></div>
            <?php endif; ?>

            <fieldset class="checkout-fieldset">
                <legend>Personal details</legend>
                <div class="form-row">
                    <div class="form-field">
                        <label for="first_name">First name</label>
                        <input type="text" id="first_name" name="first_name" required maxlength="80"
                               value="<?= e((string) $profile['first_name']) ?>" autocomplete="given-name">
                        <?php if (!empty($errors['first_name'])): ?><p class="field-error"><?= e($errors['first_name']) ?></p><?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="last_name">Last name</label>
                        <input type="text" id="last_name" name="last_name" required maxlength="80"
                               value="<?= e((string) $profile['last_name']) ?>" autocomplete="family-name">
                        <?php if (!empty($errors['last_name'])): ?><p class="field-error"><?= e($errors['last_name']) ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="form-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?= e((string) $profile['email']) ?>" disabled>
                    <p class="muted" style="margin:0;">Email cannot be changed here.</p>
                </div>

                <div class="form-field">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" maxlength="30"
                           value="<?= e((string) ($profile['phone'] ?? '')) ?>" autocomplete="tel">
                    <?php if (!empty($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>
                </div>
            </fieldset>

            <fieldset class="checkout-fieldset">
                <legend>Default address</legend>
                <div class="form-field">
                    <label for="address_line1">Address line 1</label>
                    <input type="text" id="address_line1" name="address_line1" maxlength="255"
                           value="<?= e((string) ($profile['address_line1'] ?? '')) ?>" autocomplete="address-line1">
                </div>
                <div class="form-field">
                    <label for="address_line2">Address line 2</label>
                    <input type="text" id="address_line2" name="address_line2" maxlength="255"
                           value="<?= e((string) ($profile['address_line2'] ?? '')) ?>" autocomplete="address-line2">
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" maxlength="100"
                               value="<?= e((string) ($profile['city'] ?? '')) ?>" autocomplete="address-level2">
                    </div>
                    <div class="form-field">
                        <label for="province">Province</label>
                        <select id="province" name="province">
                            <option value="">Select province</option>
                            <?php foreach (sa_provinces() as $province): ?>
                                <option value="<?= e($province) ?>" <?= ($profile['province'] ?? '') === $province ? 'selected' : '' ?>>
                                    <?= e($province) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['province'])): ?><p class="field-error"><?= e($errors['province']) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="form-field">
                    <label for="postal_code">Postal code</label>
                    <input type="text" id="postal_code" name="postal_code" maxlength="20"
                           value="<?= e((string) ($profile['postal_code'] ?? '')) ?>" autocomplete="postal-code">
                </div>
            </fieldset>

            <button type="submit" class="btn btn--inverse btn--block">Save profile</button>
        </form>

        <form class="auth-form" method="post" action="<?= e(url('pages/profile.php')) ?>" style="margin-top:2rem;" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="password">
            <fieldset class="checkout-fieldset">
                <legend>Change password</legend>
                <?php if (!empty($passwordErrors['form'])): ?>
                    <div class="form-errors" role="alert"><p><?= e($passwordErrors['form']) ?></p></div>
                <?php endif; ?>
                <div class="form-field">
                    <label for="current_password">Current password</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    <?php if (!empty($passwordErrors['current_password'])): ?><p class="field-error"><?= e($passwordErrors['current_password']) ?></p><?php endif; ?>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8" maxlength="<?= (int) PASSWORD_MAX_LENGTH ?>" autocomplete="new-password">
                        <?php if (!empty($passwordErrors['new_password'])): ?><p class="field-error"><?= e($passwordErrors['new_password']) ?></p><?php endif; ?>
                    </div>
                    <div class="form-field">
                        <label for="new_password_confirm">Confirm new password</label>
                        <input type="password" id="new_password_confirm" name="new_password_confirm" required minlength="8" maxlength="<?= (int) PASSWORD_MAX_LENGTH ?>" autocomplete="new-password">
                        <?php if (!empty($passwordErrors['new_password_confirm'])): ?><p class="field-error"><?= e($passwordErrors['new_password_confirm']) ?></p><?php endif; ?>
                    </div>
                </div>
            </fieldset>
            <button type="submit" class="btn btn--ghost-dark btn--block">Update password</button>
        </form>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
