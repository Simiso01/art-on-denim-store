<?php
/**
 * Admin — Orders
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$status = trim((string) ($_GET['status'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$result = admin_list_orders($status, $q, $page);

$query = array_filter(['status' => $status ?: null, 'q' => $q ?: null]);
$pagination = [
    'current'     => $result['page'],
    'total_pages' => $result['total_pages'],
    'base_url'    => url('admin/orders.php?' . http_build_query($query) . ($query ? '&' : '') . 'page='),
];

$pageTitle = 'Orders';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-toolbar">
    <form method="get" action="<?= e(url('admin/orders.php')) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Order # or email">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (['pending','paid','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(order_status_label($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="admin-btn admin-btn--sm">Filter</button>
    </form>
</div>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$result['items']): ?>
                    <tr><td colspan="6" class="muted">No orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($result['items'] as $order): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('admin/order-details.php?id=' . (int) $order['id'])) ?>">
                                    <?= e($order['order_number']) ?>
                                </a>
                            </td>
                            <td><?= e(date('d M Y H:i', strtotime($order['placed_at']))) ?></td>
                            <td>
                                <?= e($order['shipping_full_name']) ?><br>
                                <span class="muted"><?= e($order['customer_email']) ?></span>
                            </td>
                            <td><span class="badge"><?= e(order_status_label($order['status'])) ?></span></td>
                            <td><?= e(ucfirst((string) $order['payment_status'])) ?></td>
                            <td><?= e(money((float) $order['total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php require dirname(__DIR__) . '/includes/pagination.php'; ?>
</section>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
