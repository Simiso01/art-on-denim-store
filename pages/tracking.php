<?php
/**
 * Art on Denim — Order tracking
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$number = trim((string) ($_GET['number'] ?? ($_POST['number'] ?? '')));
$email = trim((string) ($_GET['email'] ?? ($_POST['email'] ?? '')));
$order = null;
$history = [];
$items = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid security token. Please try again.';
    }
}

if ($number !== '' && $error === null) {
    $found = get_order_by_number($number);

    if (!$found) {
        $error = 'We could not find an order with that number.';
    } elseif (can_view_order($found)) {
        $order = $found;
    } elseif ($email !== '' && strcasecmp($email, (string) $found['customer_email']) === 0) {
        remember_guest_order($found['order_number']);
        $order = $found;
    } elseif (is_logged_in() && (int) ($found['user_id'] ?? 0) === (int) current_user()['id']) {
        $order = $found;
    } else {
        // Allow lookup with matching email for guests
        if ($email === '') {
            $error = 'Enter the email used at checkout to view this order.';
        } else {
            $error = 'Order number and email do not match.';
        }
    }

    if ($order) {
        $history = get_order_history((int) $order['id']);
        $items = get_order_items((int) $order['id']);
    }
}

$pageTitle = 'Order Tracking';
$pageDescription = 'Track your Art on Denim order.';
$bodyClass = 'page-tracking';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container" style="max-width:860px;">
        <header class="section-header">
            <div>
                <p class="eyebrow">Shipments</p>
                <h1>Order tracking</h1>
                <p>Enter your order number to view the latest status.</p>
            </div>
        </header>

        <form class="auth-form" method="get" action="<?= e(url('pages/tracking.php')) ?>">
            <div class="form-field">
                <label for="number">Order number</label>
                <input type="text" id="number" name="number" required
                       placeholder="AOD-2026-000001"
                       value="<?= e($number) ?>"
                       autocomplete="off">
            </div>
            <?php if (!is_logged_in()): ?>
                <div class="form-field">
                    <label for="email">Email used at checkout</label>
                    <input type="email" id="email" name="email" required value="<?= e($email) ?>" autocomplete="email">
                </div>
            <?php endif; ?>
            <button type="submit" class="btn btn--inverse">Track order</button>
        </form>

        <?php if ($error): ?>
            <div class="form-errors" role="alert" style="margin-top:1.5rem;"><p><?= e($error) ?></p></div>
        <?php endif; ?>

        <?php if ($order): ?>
            <section class="confirm-card" style="margin-top:2rem;">
                <h2><?= e($order['order_number']) ?></h2>
                <p>
                    <span class="status-pill status-pill--<?= e($order['status']) ?>">
                        <?= e(order_status_label($order['status'])) ?>
                    </span>
                </p>
                <?php if (!empty($order['tracking_number'])): ?>
                    <p>
                        Carrier: <?= e($order['tracking_carrier'] ?: '—') ?><br>
                        Tracking number: <strong><?= e($order['tracking_number']) ?></strong>
                    </p>
                <?php else: ?>
                    <p class="muted">A carrier tracking number will appear here once your order ships.</p>
                <?php endif; ?>

                <?php if ($history): ?>
                    <h3>Timeline</h3>
                    <ol class="timeline">
                        <?php foreach ($history as $event): ?>
                            <li>
                                <strong><?= e(order_status_label($event['status'])) ?></strong>
                                <span><?= e(date('d M Y, H:i', strtotime($event['created_at']))) ?></span>
                                <?php if (!empty($event['note'])): ?>
                                    <p><?= e($event['note']) ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <p style="margin-top:1.5rem;">
                    <a class="btn btn--ghost-dark" href="<?= e(url('pages/order-details.php?number=' . urlencode($order['order_number']))) ?>">
                        View full order details
                    </a>
                </p>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
