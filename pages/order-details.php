<?php
/**
 * Art on Denim — Order details
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$number = trim((string) ($_GET['number'] ?? ''));
$order = $number !== '' ? get_order_by_number($number) : null;

if (!$order || !can_view_order($order)) {
    if (!is_logged_in()) {
        require_login();
    }
    http_response_code(404);
    flash('error', 'Order not found.');
    redirect('pages/orders.php');
}

$items = get_order_items((int) $order['id']);
$history = get_order_history((int) $order['id']);

$pageTitle = 'Order ' . $order['order_number'];
$pageDescription = 'Order details for ' . $order['order_number'];
$bodyClass = 'page-order-details';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container">
        <header class="section-header">
            <div>
                <p class="eyebrow">Order details</p>
                <h1><?= e($order['order_number']) ?></h1>
                <p>Placed <?= e(date('d M Y, H:i', strtotime($order['placed_at']))) ?></p>
            </div>
            <div class="header-actions-inline">
                <a class="section-link" href="<?= e(url('pages/tracking.php?number=' . urlencode($order['order_number']))) ?>">Track</a>
                <?php if (is_logged_in()): ?>
                    <a class="section-link" href="<?= e(url('pages/orders.php')) ?>">All orders</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="confirmation-grid">
            <section class="confirm-card">
                <h2>Status</h2>
                <p>
                    <span class="status-pill status-pill--<?= e($order['status']) ?>">
                        <?= e(order_status_label($order['status'])) ?>
                    </span>
                </p>
                <p class="muted">
                    Payment <?= e(ucfirst((string) $order['payment_status'])) ?>
                    · <?= e(strtoupper((string) $order['payment_method'])) ?>
                </p>
                <?php if (!empty($order['tracking_number'])): ?>
                    <p>
                        Tracking: <?= e($order['tracking_carrier'] ?: 'Carrier') ?>
                        · <?= e($order['tracking_number']) ?>
                    </p>
                <?php endif; ?>
            </section>

            <section class="confirm-card">
                <h2>Shipping</h2>
                <p>
                    <?= e($order['shipping_full_name']) ?><br>
                    <?= e($order['shipping_address_line1']) ?><br>
                    <?php if (!empty($order['shipping_address_line2'])): ?>
                        <?= e($order['shipping_address_line2']) ?><br>
                    <?php endif; ?>
                    <?= e($order['shipping_city']) ?>, <?= e($order['shipping_province']) ?> <?= e($order['shipping_postal_code']) ?><br>
                    <?= e($order['customer_phone']) ?><br>
                    <?= e($order['customer_email']) ?>
                </p>
            </section>
        </div>

        <section class="confirm-card">
            <h2>Items</h2>
            <ul class="checkout-items">
                <?php foreach ($items as $item): ?>
                    <li>
                        <div>
                            <strong><?= e($item['product_name']) ?></strong>
                            <span>
                                SKU <?= e($item['product_sku']) ?> · Size <?= e($item['size']) ?> · Qty <?= (int) $item['quantity'] ?>
                            </span>
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

        <?php if ($history): ?>
            <section class="confirm-card">
                <h2>Timeline</h2>
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
            </section>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
