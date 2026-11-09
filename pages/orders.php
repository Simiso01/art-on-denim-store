<?php
/**
 * Art on Denim — Customer order history
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$userId = (int) current_user()['id'];
$page = max(1, (int) ($_GET['page'] ?? 1));

try {
    $result = get_user_orders($userId, $page);
} catch (Throwable $e) {
    $result = ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
}

$pagination = [
    'current'     => (int) $result['page'],
    'total_pages' => (int) $result['total_pages'],
    'base_url'    => url('pages/orders.php?page='),
];

$pageTitle = 'My Orders';
$pageDescription = 'Your Art on Denim order history.';
$bodyClass = 'page-orders';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container">
        <header class="section-header">
            <div>
                <p class="eyebrow">Account</p>
                <h1>Orders</h1>
                <p><?= (int) $result['total'] ?> order<?= (int) $result['total'] === 1 ? '' : 's' ?></p>
            </div>
            <a class="section-link" href="<?= e(url('pages/account.php')) ?>">Back to account</a>
        </header>

        <?php if (!$result['items']): ?>
            <div class="empty-state">
                <h2>No orders yet</h2>
                <p>When you place an order, it will appear here.</p>
                <a class="btn btn--inverse" href="<?= e(url('pages/shop.php')) ?>">Start shopping</a>
            </div>
        <?php else: ?>
            <div class="orders-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Order</th>
                            <th scope="col">Date</th>
                            <th scope="col">Status</th>
                            <th scope="col">Payment</th>
                            <th scope="col">Total</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['items'] as $order): ?>
                            <tr>
                                <td>
                                    <a href="<?= e(url('pages/order-details.php?number=' . urlencode($order['order_number']))) ?>">
                                        <?= e($order['order_number']) ?>
                                    </a>
                                </td>
                                <td><?= e(date('d M Y', strtotime($order['placed_at']))) ?></td>
                                <td>
                                    <span class="status-pill status-pill--<?= e($order['status']) ?>">
                                        <?= e(order_status_label($order['status'])) ?>
                                    </span>
                                </td>
                                <td><?= e(ucfirst((string) $order['payment_status'])) ?></td>
                                <td><?= e(money((float) $order['total'])) ?></td>
                                <td>
                                    <a href="<?= e(url('pages/tracking.php?number=' . urlencode($order['order_number']))) ?>">Track</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php require dirname(__DIR__) . '/includes/pagination.php'; ?>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
