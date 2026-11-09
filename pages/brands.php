<?php
/**
 * Art on Denim — Brands listing / brand shop
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$brand = null;
$result = ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
$brands = [];

try {
    $brands = get_brands_list();
    if ($slug !== '') {
        foreach ($brands as $b) {
            if ($b['slug'] === $slug) {
                $brand = $b;
                break;
            }
        }
        if ($brand) {
            $result = query_products(['brand' => $slug], $page);
        }
    }
} catch (Throwable $e) {
    error_log('AOD brands: ' . $e->getMessage());
}

$pageTitle = $brand ? $brand['name'] : 'Brands';
$pageDescription = $brand
    ? ('Shop ' . $brand['name'] . ' at Art on Denim.')
    : 'Designer and house brands at Art on Denim.';
$bodyClass = 'page-brands';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container">
        <?php if ($slug && !$brand): ?>
            <div class="empty-state">
                <h1>Brand not found</h1>
                <a class="btn btn--inverse" href="<?= e(url('pages/brands.php')) ?>">All brands</a>
            </div>
        <?php elseif ($brand): ?>
            <header class="section-header">
                <div>
                    <p class="eyebrow">Brand</p>
                    <h1><?= e($brand['name']) ?></h1>
                    <?php if (!empty($brand['description'])): ?>
                        <p><?= e($brand['description']) ?></p>
                    <?php endif; ?>
                </div>
                <a class="section-link" href="<?= e(url('pages/brands.php')) ?>">All brands</a>
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
                    'base_url'    => url('pages/brands.php?slug=' . urlencode($slug) . '&page='),
                ];
                require dirname(__DIR__) . '/includes/pagination.php';
                ?>
            <?php else: ?>
                <div class="empty-state">
                    <h2>No products for this brand yet</h2>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <header class="section-header">
                <div>
                    <p class="eyebrow">Labels</p>
                    <h1>Brands</h1>
                    <p>House denim and international designer labels.</p>
                </div>
            </header>

            <div class="brand-strip brand-strip--page">
                <?php foreach ($brands as $b): ?>
                    <a class="brand-tile" href="<?= e(url('pages/brands.php?slug=' . urlencode($b['slug']))) ?>">
                        <span><?= e($b['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
