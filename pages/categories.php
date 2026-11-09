<?php
/**
 * Art on Denim — Categories
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$category = null;
$result = ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
$categories = [];

try {
    $categories = get_categories();
    if ($slug !== '') {
        foreach ($categories as $c) {
            if ($c['slug'] === $slug) {
                $category = $c;
                break;
            }
        }
        if ($category) {
            $result = query_products(['category' => $slug], $page);
        }
    }
} catch (Throwable $e) {
    error_log('AOD categories: ' . $e->getMessage());
}

$pageTitle = $category ? $category['name'] : 'Categories';
$pageDescription = $category
    ? ('Shop ' . $category['name'] . ' at Art on Denim.')
    : 'Shop by category at Art on Denim.';
$bodyClass = 'page-categories';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container">
        <?php if ($slug && !$category): ?>
            <div class="empty-state">
                <h1>Category not found</h1>
                <a class="btn btn--inverse" href="<?= e(url('pages/categories.php')) ?>">All categories</a>
            </div>
        <?php elseif ($category): ?>
            <header class="section-header">
                <div>
                    <p class="eyebrow">Category</p>
                    <h1><?= e($category['name']) ?></h1>
                    <?php if (!empty($category['description'])): ?>
                        <p><?= e($category['description']) ?></p>
                    <?php endif; ?>
                </div>
                <a class="section-link" href="<?= e(url('pages/categories.php')) ?>">All categories</a>
            </header>

            <?php if ($result['items']): ?>
                <div class="product-grid">
                    <?php foreach ($result['items'] as $product): ?>
                        <?php require dirname(__DIR__) . '/includes/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?php
                $pagination = [
                    'current'     => (int) $result['page'],
                    'total_pages' => (int) $result['total_pages'],
                    'base_url'    => url('pages/categories.php?slug=' . urlencode($slug) . '&page='),
                ];
                require dirname(__DIR__) . '/includes/pagination.php';
                ?>
            <?php else: ?>
                <div class="empty-state"><h2>No products in this category</h2></div>
            <?php endif; ?>
        <?php else: ?>
            <header class="section-header">
                <div>
                    <p class="eyebrow">Browse</p>
                    <h1>Categories</h1>
                </div>
            </header>
            <div class="brand-strip brand-strip--page">
                <?php foreach ($categories as $c): ?>
                    <a class="brand-tile" href="<?= e(url('pages/categories.php?slug=' . urlencode($c['slug']))) ?>">
                        <span><?= e($c['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
