<?php
/**
 * Art on Denim — Search results
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

try {
    $result = $q !== ''
        ? query_products(['q' => $q], $page)
        : ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
} catch (Throwable $e) {
    $result = ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
}

$pagination = [
    'current'     => (int) $result['page'],
    'total_pages' => (int) $result['total_pages'],
    'base_url'    => url('pages/search.php?q=' . urlencode($q) . '&page='),
];

$pageTitle = $q !== '' ? ('Search: ' . $q) : 'Search';
$pageDescription = 'Search Art on Denim products.';
$bodyClass = 'page-search';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container">
        <header class="section-header">
            <div>
                <p class="eyebrow">Search</p>
                <h1><?= $q !== '' ? ('Results for “' . e($q) . '”') : 'Search' ?></h1>
            </div>
        </header>

        <form class="search-hero-form" method="get" action="<?= e(url('pages/search.php')) ?>" role="search">
            <label class="visually-hidden" for="search-q">Search products</label>
            <input type="search" id="search-q" name="q" value="<?= e($q) ?>" placeholder="Search denim, brands, styles..." required>
            <button type="submit" class="btn btn--inverse">Search</button>
        </form>

        <?php if ($q === ''): ?>
            <p class="muted">Enter a term to find products.</p>
        <?php elseif ($result['items']): ?>
            <p class="muted"><?= (int) $result['total'] ?> result<?= (int) $result['total'] === 1 ? '' : 's' ?></p>
            <div class="product-grid">
                <?php foreach ($result['items'] as $product): ?>
                    <?php require dirname(__DIR__) . '/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
            <?php require dirname(__DIR__) . '/includes/pagination.php'; ?>
        <?php else: ?>
            <div class="empty-state">
                <h2>No results</h2>
                <p>Try a different keyword or browse the full shop.</p>
                <a class="btn btn--inverse" href="<?= e(url('pages/shop.php')) ?>">Browse shop</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
