<?php
/**
 * Art on Denim — Checkout
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

try {
    $totals = cart_totals();
} catch (Throwable $e) {
    $totals = ['items' => [], 'subtotal' => 0, 'discount' => 0, 'coupon' => null, 'shipping' => 0, 'total' => 0];
}

if ($totals['items'] === []) {
    flash('error', 'Your cart is empty.');
    redirect('pages/cart.php');
}

$user = current_user();
$profile = $user ? find_user_by_id((int) $user['id']) : null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors['form'] = 'Invalid security token. Please try again.';
        store_old($_POST);
    } else {
        $errors = validate_checkout($_POST);

        if ($errors === []) {
            [$error, $orderId, $orderNumber] = place_order($_POST);

            if ($error) {
                $errors['form'] = $error;
                store_old($_POST);
            } else {
                clear_old();
                if (!$user && $orderNumber) {
                    remember_guest_order($orderNumber);
                }
                flash('success', 'Your order has been successfully placed.');
                redirect('pages/order-confirmation.php?number=' . urlencode((string) $orderNumber));
            }
        } else {
            store_old($_POST);
        }
    }
}

$defaults = [
    'full_name'      => trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')),
    'email'          => $profile['email'] ?? ($user['email'] ?? ''),
    'phone'          => $profile['phone'] ?? '',
    'address_line1'  => $profile['address_line1'] ?? '',
    'address_line2'  => $profile['address_line2'] ?? '',
    'city'           => $profile['city'] ?? '',
    'province'       => $profile['province'] ?? '',
    'postal_code'     => $profile['postal_code'] ?? '',
    'payment_method' => 'payfast',
    'notes'          => '',
];

$pageTitle = 'Checkout';
$pageDescription = 'Secure checkout at Art on Denim.';
$bodyClass = 'page-checkout';
$extraCss = [];

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container">
        <header class="section-header">
            <div>
                <p class="eyebrow">Secure checkout</p>
                <h1>Checkout</h1>
                <p>Review your details and complete your order. Card numbers are never stored.</p>
            </div>
        </header>

        <div class="checkout-layout">
            <form
                class="checkout-form auth-form"
                method="post"
                action="<?= e(url('pages/checkout.php')) ?>"
                id="checkout-form"
                novalidate
            >
                <?= csrf_field() ?>

                <?php if (!empty($errors['form'])): ?>
                    <div class="form-errors" role="alert"><p><?= e($errors['form']) ?></p></div>
                <?php endif; ?>

                <fieldset class="checkout-fieldset">
                    <legend>Customer information</legend>

                    <div class="form-field">
                        <label for="full_name">Full name</label>
                        <input type="text" id="full_name" name="full_name" required maxlength="160"
                               value="<?= old('full_name', $defaults['full_name']) ?>" autocomplete="name">
                        <?php if (!empty($errors['full_name'])): ?><p class="field-error"><?= e($errors['full_name']) ?></p><?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required maxlength="190"
                                   value="<?= old('email', $defaults['email']) ?>" autocomplete="email">
                            <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" required maxlength="30"
                                   value="<?= old('phone', (string) $defaults['phone']) ?>" autocomplete="tel">
                            <?php if (!empty($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="checkout-fieldset">
                    <legend>Shipping address</legend>

                    <div class="form-field">
                        <label for="address_line1">Address line 1</label>
                        <input type="text" id="address_line1" name="address_line1" required maxlength="255"
                               value="<?= old('address_line1', (string) $defaults['address_line1']) ?>" autocomplete="address-line1">
                        <?php if (!empty($errors['address_line1'])): ?><p class="field-error"><?= e($errors['address_line1']) ?></p><?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="address_line2">Address line 2 <span class="optional">(optional)</span></label>
                        <input type="text" id="address_line2" name="address_line2" maxlength="255"
                               value="<?= old('address_line2', (string) $defaults['address_line2']) ?>" autocomplete="address-line2">
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" required maxlength="100"
                                   value="<?= old('city', (string) $defaults['city']) ?>" autocomplete="address-level2">
                            <?php if (!empty($errors['city'])): ?><p class="field-error"><?= e($errors['city']) ?></p><?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="province">Province</label>
                            <select id="province" name="province" required>
                                <option value="">Select province</option>
                                <?php
                                $selectedProvince = $_SESSION['_old']['province'] ?? $defaults['province'];
                                foreach (sa_provinces() as $province):
                                ?>
                                    <option value="<?= e($province) ?>" <?= $selectedProvince === $province ? 'selected' : '' ?>>
                                        <?= e($province) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['province'])): ?><p class="field-error"><?= e($errors['province']) ?></p><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="postal_code">Postal code</label>
                        <input type="text" id="postal_code" name="postal_code" required maxlength="20"
                               value="<?= old('postal_code', (string) $defaults['postal_code']) ?>" autocomplete="postal-code">
                        <?php if (!empty($errors['postal_code'])): ?><p class="field-error"><?= e($errors['postal_code']) ?></p><?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="notes">Order notes <span class="optional">(optional)</span></label>
                        <textarea id="notes" name="notes" rows="3"><?= old('notes') ?></textarea>
                    </div>
                </fieldset>

                <fieldset class="checkout-fieldset">
                    <legend>Payment</legend>
                    <p class="muted">Choose how you want to pay. Raw card details are never stored on our servers.</p>

                    <?php
                    $selectedMethod = $_SESSION['_old']['payment_method'] ?? $defaults['payment_method'];
                    $methods = [
                        'payfast' => ['PayFast', 'Redirect to PayFast (production-ready architecture). Pending until confirmed.'],
                        'eft'     => ['EFT', 'Pay via bank transfer. Order stays pending until payment is verified.'],
                        'card'    => ['Card (demo)', 'Local demo simulation only — no card fields collected.'],
                    ];
                    ?>
                    <div class="payment-options">
                        <?php foreach ($methods as $value => $meta): ?>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="<?= e($value) ?>"
                                    <?= $selectedMethod === $value ? 'checked' : '' ?> required>
                                <span>
                                    <strong><?= e($meta[0]) ?></strong>
                                    <small><?= e($meta[1]) ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($errors['payment_method'])): ?>
                        <p class="field-error"><?= e($errors['payment_method']) ?></p>
                    <?php endif; ?>
                </fieldset>

                <button type="submit" class="btn btn--inverse btn--block" id="place-order-btn">
                    Place order · <?= e(money($totals['total'])) ?>
                </button>
            </form>

            <aside class="cart-summary checkout-summary" aria-label="Order summary">
                <h2>Order summary</h2>
                <ul class="checkout-items">
                    <?php foreach ($totals['items'] as $item): ?>
                        <li>
                            <div>
                                <strong><?= e($item['name']) ?></strong>
                                <span>Size <?= e($item['size']) ?> · Qty <?= (int) $item['quantity'] ?></span>
                            </div>
                            <span><?= e(money((float) $item['unit_price'] * (int) $item['quantity'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <dl class="summary-list">
                    <div>
                        <dt>Subtotal</dt>
                        <dd><?= e(money($totals['subtotal'])) ?></dd>
                    </div>
                    <?php if (($totals['discount'] ?? 0) > 0): ?>
                    <div>
                        <dt>Coupon<?= !empty($totals['coupon']['coupon_code']) ? ' ' . e($totals['coupon']['coupon_code']) : '' ?></dt>
                        <dd>−<?= e(money($totals['discount'])) ?></dd>
                    </div>
                    <?php endif; ?>
                    <div>
                        <dt>Shipping</dt>
                        <dd><?= $totals['shipping'] <= 0 ? 'Complimentary' : e(money($totals['shipping'])) ?></dd>
                    </div>
                    <div class="summary-list__total">
                        <dt>Total</dt>
                        <dd><?= e(money($totals['total'])) ?></dd>
                    </div>
                </dl>
                <a class="link-btn" href="<?= e(url('pages/cart.php')) ?>">Edit cart</a>
            </aside>
        </div>
    </div>
</main>

<script src="<?= e(asset('assets/js/checkout.js')) ?>" defer></script>
<?php
clear_old();
require dirname(__DIR__) . '/includes/footer.php';
?>
