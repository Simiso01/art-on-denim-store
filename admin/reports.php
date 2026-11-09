<?php
/**
 * Admin — Sales reports
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));
if ($from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = '';
}
if ($to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = '';
}

$report = admin_sales_report($from !== '' ? $from : null, $to !== '' ? $to : null);
$sum = $report['summary'];

$pageTitle = 'Reports';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-toolbar">
    <form method="get" action="<?= e(url('admin/reports.php')) ?>">
        <label class="visually-hidden" for="from">From</label>
        <input type="date" id="from" name="from" value="<?= e($from) ?>">
        <label class="visually-hidden" for="to">To</label>
        <input type="date" id="to" name="to" value="<?= e($to) ?>">
        <button type="submit" class="admin-btn admin-btn--sm">Filter</button>
        <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= e(url('admin/reports.php')) ?>">Reset</a>
    </form>
</div>

<section class="admin-stats">
    <article class="stat-card stat-card--accent">
        <p class="stat-card__label">Revenue</p>
        <p class="stat-card__value"><?= e(money((float) $sum['revenue'])) ?></p>
    </article>
    <article class="stat-card">
        <p class="stat-card__label">Orders</p>
        <p class="stat-card__value"><?= (int) $sum['orders'] ?></p>
    </article>
    <article class="stat-card">
        <p class="stat-card__label">Avg order</p>
        <p class="stat-card__value"><?= e(money((float) $sum['avg_order'])) ?></p>
    </article>
    <article class="stat-card">
        <p class="stat-card__label">Period</p>
        <p class="stat-card__value" style="font-size:1rem;padding-top:.55rem;">
            <?= e(($from ?: 'All') . ' → ' . ($to ?: 'Now')) ?>
        </p>
    </article>
</section>

<div class="report-blocks">
    <section class="admin-panel">
        <h2>Daily sales</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead>
                <tbody>
                    <?php if (!$report['daily']): ?>
                        <tr><td colspan="3" class="muted">No data.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report['daily'] as $row): ?>
                            <tr>
                                <td><?= e($row['day']) ?></td>
                                <td><?= (int) $row['orders'] ?></td>
                                <td><?= e(money((float) $row['revenue'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="admin-grid-2">
        <section class="admin-panel">
            <h2>Weekly</h2>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Week</th><th>Orders</th><th>Revenue</th></tr></thead>
                    <tbody>
                        <?php foreach ($report['weekly'] as $row): ?>
                            <tr>
                                <td><?= e((string) $row['period']) ?></td>
                                <td><?= (int) $row['orders'] ?></td>
                                <td><?= e(money((float) $row['revenue'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="admin-panel">
            <h2>Monthly</h2>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Month</th><th>Orders</th><th>Revenue</th></tr></thead>
                    <tbody>
                        <?php foreach ($report['monthly'] as $row): ?>
                            <tr>
                                <td><?= e((string) $row['period']) ?></td>
                                <td><?= (int) $row['orders'] ?></td>
                                <td><?= e(money((float) $row['revenue'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="admin-panel">
        <h2>Best-selling products</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Revenue</th></tr></thead>
                <tbody>
                    <?php if (!$report['bestsellers']): ?>
                        <tr><td colspan="4" class="muted">No data.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report['bestsellers'] as $row): ?>
                            <tr>
                                <td><?= e($row['product_name']) ?></td>
                                <td><?= e($row['product_sku']) ?></td>
                                <td><?= (int) $row['qty'] ?></td>
                                <td><?= e(money((float) $row['revenue'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="admin-grid-2">
        <section class="admin-panel">
            <h2>Sales by brand</h2>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Brand</th><th>Qty</th><th>Revenue</th></tr></thead>
                    <tbody>
                        <?php foreach ($report['by_brand'] as $row): ?>
                            <tr>
                                <td><?= e($row['brand_name']) ?></td>
                                <td><?= (int) $row['qty'] ?></td>
                                <td><?= e(money((float) $row['revenue'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="admin-panel">
            <h2>Sales by category</h2>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Category</th><th>Qty</th><th>Revenue</th></tr></thead>
                    <tbody>
                        <?php foreach ($report['by_category'] as $row): ?>
                            <tr>
                                <td><?= e($row['category_name']) ?></td>
                                <td><?= (int) $row['qty'] ?></td>
                                <td><?= e(money((float) $row['revenue'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
