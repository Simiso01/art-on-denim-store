<?php
/**
 * Art on Denim — Order confirmation
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$number = trim((string) ($_GET['number'] ?? ''));
$order = $number !== '' ? get_order_by_number($number) : null;

if (!$order || !can_view_order($order)) {
    http_response_code(404);
    flash('error', 'Order not found.');
    redirect(is_logged_in() ? 'pages/orders.php' : 'index.php');
}

$items = get_order_items((int) $order['id']);

$pageTitle = 'Order Confirmation';
$pageDescription = 'Your Art on Denim order confirmation.';
$bodyClass = 'page-confirmation';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container confirmation">
        <header class="section-header">
            <div>
                <p class="eyebrow">Thank you</p>
                <h1>Order confirmed</h1>
                <p>
                    Your order <strong><?= e($order['order_number']) ?></strong> has been placed.
                    A confirmation was recorded for <?= e($order['customer_email']) ?>.
                </p>
            </div>
        </header>

        <div class="confirmation-grid">
            <section class="confirm-card" aria-labelledby="order-status-heading">
                <h2 id="order-status-heading">Status</h2>
                <p>
                    <span class="status-pill status-pill--<?= e($order['status']) ?>">
                        <?= e(order_status_label($order['status'])) ?>
                    </span>
                </p>
                <p class="muted">
                    Payment: <?= e(ucfirst((string) $order['payment_status'])) ?>
                    via <?= e(strtoupper((string) $order['payment_method'])) ?>
                </p>
                <?php if ($order['payment_method'] === 'eft' && $order['payment_status'] === 'pending'): ?>
                    <p>Please use your order number as the EFT reference. We will update your order once payment is verified.</p>
                <?php elseif ($order['payment_method'] === 'payfast' && $order['payment_status'] === 'pending'): ?>
                    <p>PayFast redirect integration is architected for production. This demo records a pending PayFast payment.</p>
                <?php endif; ?>
            </section>

            <section class="confirm-card" aria-labelledby="ship-heading">
                <h2 id="ship-heading">Shipping to</h2>
                <p>
                    <?= e($order['shipping_full_name']) ?><br>
                    <?= e($order['shipping_address_line1']) ?><br>
                    <?php if (!empty($order['shipping_address_line2'])): ?>
                        <?= e($order['shipping_address_line2']) ?><br>
                    <?php endif; ?>
                    <?= e($order['shipping_city']) ?>, <?= e($order['shipping_province']) ?><br>
                    <?= e($order['shipping_postal_code']) ?>
                </p>
            </section>
        </div>

        <section class="confirm-card" aria-labelledby="items-heading">
            <h2 id="items-heading">Items</h2>
            <ul class="checkout-items">
                <?php foreach ($items as $item): ?>
                    <li>
                        <div>
                            <strong><?= e($item['product_name']) ?></strong>
                            <span>Size <?= e($item['size']) ?> · Qty <?= (int) $item['quantity'] ?></span>
                        </div>
                        <span><?= e(money((float) $item['line_total'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <dl class="summary-list">
                <div><dt>Subtotal</dt><dd><?= e(money((float) $order['subtotal'])) ?></dd></div>
                <div><dt>Shipping</dt><dd><?= (float) $order['shipping_amount'] <= 0 ? 'Complimentary' : e(money((float) $order['shipping_amount'])) ?></dd></div>
                <div class="summary-list__total"><dt>Total</dt><dd><?= e(money((float) $order['total'])) ?></dd></div>
            </dl>
        </section>

        <div class="confirmation-actions">
            <a class="btn btn--inverse" href="<?= e(url('pages/order-details.php?number=' . urlencode($order['order_number']))) ?>">
                View order details
            </a>
            <a class="btn btn--ghost-dark" href="<?= e(url('pages/tracking.php?number=' . urlencode($order['order_number']))) ?>">
                Track order
            </a>
            <a class="btn btn--ghost-dark" href="<?= e(url('pages/shop.php')) ?>">Continue shopping</a>
        </div>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
