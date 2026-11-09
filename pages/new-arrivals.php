<?php
/**
 * Art on Denim — New arrivals
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$page = max(1, (int) ($_GET['page'] ?? 1));

try {
    $result = query_products(['is_new' => true], $page);
} catch (Throwable $e) {
    $result = ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
}

$pagination = [
    'current'     => (int) $result['page'],
    'total_pages' => (int) $result['total_pages'],
    'base_url'    => url('pages/new-arrivals.php?page='),
];

$pageTitle = 'New Arrivals';
$pageDescription = 'Latest arrivals at Art on Denim.';
$bodyClass = 'page-new';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container">
        <header class="section-header">
            <div>
                <p class="eyebrow">Just in</p>
                <h1>New arrivals</h1>
                <p>Fresh drops across house denim and designer labels.</p>
            </div>
        </header>

        <?php if ($result['items']): ?>
            <div class="product-grid">
                <?php foreach ($result['items'] as $product): ?>
                    <?php require dirname(__DIR__) . '/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
            <?php require dirname(__DIR__) . '/includes/pagination.php'; ?>
        <?php else: ?>
            <div class="empty-state">
                <h2>No new arrivals yet</h2>
                <a class="btn btn--inverse" href="<?= e(url('pages/shop.php')) ?>">Browse shop</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
