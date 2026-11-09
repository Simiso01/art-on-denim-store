<?php
/**
 * Admin dashboard
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

try {
    $stats = admin_dashboard_stats();
    $recent = admin_recent_orders(8);
    $lowStock = admin_low_stock(8);
} catch (Throwable $e) {
    $stats = [
        'sales_total' => 0, 'orders_today' => 0, 'orders_total' => 0,
        'customers' => 0, 'products' => 0, 'low_stock' => 0, 'new_messages' => 0,
    ];
    $recent = [];
    $lowStock = [];
    error_log('AOD admin dashboard: ' . $e->getMessage());
}

$pageTitle = 'Dashboard';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<section class="admin-stats" aria-label="Key metrics">
    <article class="stat-card stat-card--accent">
        <p class="stat-card__label">Total sales</p>
        <p class="stat-card__value"><?= e(money((float) $stats['sales_total'])) ?></p>
    </article>
    <article class="stat-card">
        <p class="stat-card__label">Orders today</p>
        <p class="stat-card__value"><?= (int) $stats['orders_today'] ?></p>
    </article>
    <article class="stat-card">
        <p class="stat-card__label">Customers</p>
        <p class="stat-card__value"><?= (int) $stats['customers'] ?></p>
    </article>
    <article class="stat-card <?= $stats['low_stock'] > 0 ? 'stat-card--warn' : '' ?>">
        <p class="stat-card__label">Low stock SKUs</p>
        <p class="stat-card__value"><?= (int) $stats['low_stock'] ?></p>
    </article>
</section>

<div class="admin-stats" style="grid-template-columns: repeat(3, 1fr);">
    <article class="stat-card">
        <p class="stat-card__label">Total orders</p>
        <p class="stat-card__value"><?= (int) $stats['orders_total'] ?></p>
    </article>
    <article class="stat-card">
        <p class="stat-card__label">Active products</p>
        <p class="stat-card__value"><?= (int) $stats['products'] ?></p>
    </article>
    <article class="stat-card">
        <p class="stat-card__label">New messages</p>
        <p class="stat-card__value"><?= (int) $stats['new_messages'] ?></p>
    </article>
</div>

<div class="admin-grid-2">
    <section class="admin-panel">
        <div class="admin-toolbar">
            <h2>Recent orders</h2>
            <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= e(url('admin/orders.php')) ?>">View all</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$recent): ?>
                        <tr><td colspan="4" class="muted">No orders yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent as $order): ?>
                            <tr>
                                <td>
                                    <a href="<?= e(url('admin/order-details.php?id=' . (int) $order['id'])) ?>">
                                        <?= e($order['order_number']) ?>
                                    </a>
                                </td>
                                <td><?= e($order['shipping_full_name']) ?></td>
                                <td><span class="badge"><?= e(order_status_label($order['status'])) ?></span></td>
                                <td><?= e(money((float) $order['total'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-panel">
        <div class="admin-toolbar">
            <h2>Low stock alerts</h2>
            <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= e(url('admin/inventory.php')) ?>">Inventory</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Size</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$lowStock): ?>
                        <tr><td colspan="3" class="muted">Stock levels look healthy.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lowStock as $row): ?>
                            <tr>
                                <td><?= e($row['product_name']) ?></td>
                                <td><?= e($row['size']) ?></td>
                                <td><span class="badge badge--warn"><?= (int) $row['quantity'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
