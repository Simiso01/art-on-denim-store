<?php
/**
 * Admin — Order details / status update
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$order = get_order_by_id($id);

if (!$order) {
    flash('error', 'Order not found.');
    redirect('admin/orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token.');
        redirect('admin/order-details.php?id=' . $id);
    }

    $err = admin_update_order_status(
        $id,
        (string) ($_POST['status'] ?? ''),
        trim((string) ($_POST['note'] ?? '')),
        trim((string) ($_POST['tracking_number'] ?? '')) ?: null,
        trim((string) ($_POST['tracking_carrier'] ?? '')) ?: null
    );

    flash($err ? 'error' : 'success', $err ?: 'Order status updated.');
    redirect('admin/order-details.php?id=' . $id);
}

$items = get_order_items($id);
$history = get_order_history($id);

$pageTitle = 'Order ' . $order['order_number'];
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-grid-2">
    <section class="admin-panel">
        <h2>Order summary</h2>
        <p><strong><?= e($order['order_number']) ?></strong></p>
        <p class="muted">Placed <?= e(date('d M Y, H:i', strtotime($order['placed_at']))) ?></p>
        <p>
            Status: <span class="badge"><?= e(order_status_label($order['status'])) ?></span><br>
            Payment: <?= e(ucfirst((string) $order['payment_status'])) ?> via <?= e(strtoupper((string) $order['payment_method'])) ?>
        </p>
        <p>
            <?= e($order['shipping_full_name']) ?><br>
            <?= e($order['shipping_address_line1']) ?><br>
            <?php if ($order['shipping_address_line2']): ?><?= e($order['shipping_address_line2']) ?><br><?php endif; ?>
            <?= e($order['shipping_city']) ?>, <?= e($order['shipping_province']) ?> <?= e($order['shipping_postal_code']) ?><br>
            <?= e($order['customer_email']) ?> · <?= e((string) $order['customer_phone']) ?>
        </p>

        <h3 style="margin-top:1.5rem;font-size:1rem;">Items</h3>
        <ul class="muted">
            <?php foreach ($items as $item): ?>
                <li style="margin-bottom:.5rem;color:var(--admin-text);">
                    <?= e($item['product_name']) ?> — <?= e($item['size']) ?> × <?= (int) $item['quantity'] ?>
                    · <?= e(money((float) $item['line_total'])) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <p><strong>Total: <?= e(money((float) $order['total'])) ?></strong></p>
    </section>

    <section class="admin-panel">
        <h2>Update status</h2>
        <form method="post" class="admin-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="admin-field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <?php foreach (['pending','paid','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
                        <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(order_status_label($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-field">
                <label for="tracking_carrier">Carrier</label>
                <input type="text" id="tracking_carrier" name="tracking_carrier" value="<?= e((string) ($order['tracking_carrier'] ?? '')) ?>">
            </div>
            <div class="admin-field">
                <label for="tracking_number">Tracking number</label>
                <input type="text" id="tracking_number" name="tracking_number" value="<?= e((string) ($order['tracking_number'] ?? '')) ?>">
            </div>
            <div class="admin-field">
                <label for="note">Note</label>
                <textarea id="note" name="note" rows="3"></textarea>
            </div>
            <button type="submit" class="admin-btn">Save status</button>
        </form>

        <h3 style="margin-top:1.75rem;font-size:1rem;">Timeline</h3>
        <?php if (!$history): ?>
            <p class="muted">No history yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($history as $event): ?>
                    <li style="margin-bottom:.75rem;">
                        <strong><?= e(order_status_label($event['status'])) ?></strong>
                        <span class="muted"> · <?= e(date('d M Y H:i', strtotime($event['created_at']))) ?></span>
                        <?php if ($event['note']): ?><br><span class="muted"><?= e($event['note']) ?></span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
