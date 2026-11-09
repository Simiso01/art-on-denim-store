<?php
/**
 * Admin — Settings overview
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$pageTitle = 'Settings';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<section class="admin-panel">
    <h2>Store configuration</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <tbody>
                <tr><th>Store name</th><td><?= e(APP_NAME) ?></td></tr>
                <tr><th>Environment</th><td><?= e(APP_ENV) ?></td></tr>
                <tr><th>Base URL</th><td><?= e(BASE_URL) ?></td></tr>
                <tr><th>Currency</th><td><?= e(CURRENCY_CODE) ?> (<?= e(CURRENCY_SYMBOL) ?>)</td></tr>
                <tr><th>Default shipping</th><td><?= e(money(DEFAULT_SHIPPING)) ?></td></tr>
                <tr><th>Free shipping threshold</th><td><?= e(money(FREE_SHIPPING_THRESHOLD)) ?></td></tr>
                <tr><th>Low stock threshold</th><td><?= (int) LOW_STOCK_THRESHOLD ?></td></tr>
                <tr><th>Order prefix</th><td><?= e(ORDER_PREFIX) ?></td></tr>
                <tr><th>Timezone</th><td><?= e(date_default_timezone_get()) ?></td></tr>
            </tbody>
        </table>
    </div>
    <p class="muted" style="margin-top:1rem;">
        Edit these values in <code>config/config.php</code> and <code>config/database.php</code>.
    </p>
</section>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
