<?php
/**
 * Art on Denim — Shop / catalogue
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$filters = [
    'q'          => trim((string) ($_GET['q'] ?? '')),
    'category'   => trim((string) ($_GET['category'] ?? '')),
    'brand'      => trim((string) ($_GET['brand'] ?? '')),
    'sort'       => trim((string) ($_GET['sort'] ?? 'newest')),
    'gender'     => trim((string) ($_GET['gender'] ?? '')),
    'min_price'  => trim((string) ($_GET['min_price'] ?? '')),
    'max_price'  => trim((string) ($_GET['max_price'] ?? '')),
    'size'       => trim((string) ($_GET['size'] ?? '')),
    'collection' => trim((string) ($_GET['collection'] ?? '')),
    'is_new'     => !empty($_GET['new']),
    'is_on_sale' => !empty($_GET['sale']),
];

$page = max(1, (int) ($_GET['page'] ?? 1));

try {
    $result = query_products($filters, $page);
    $categories = get_categories();
    $brands = get_brands_list();
    $sizes = catalogue_sizes();
    $dbReady = true;
} catch (Throwable $e) {
    $dbReady = false;
    $result = ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
    $categories = [];
    $brands = [];
    $sizes = [];
    error_log('AOD shop error: ' . $e->getMessage());
}

$queryBase = array_filter([
    'q'          => $filters['q'] ?: null,
    'category'   => $filters['category'] ?: null,
    'brand'      => $filters['brand'] ?: null,
    'sort'       => $filters['sort'] !== 'newest' ? $filters['sort'] : null,
    'gender'     => $filters['gender'] ?: null,
    'min_price'  => $filters['min_price'] ?: null,
    'max_price'  => $filters['max_price'] ?: null,
    'size'       => $filters['size'] ?: null,
    'collection' => $filters['collection'] ?: null,
    'new'        => $filters['is_new'] ? '1' : null,
    'sale'       => $filters['is_on_sale'] ? '1' : null,
], static function ($v) {
    return $v !== null && $v !== '';
});

$paginationBase = url('pages/shop.php?' . http_build_query($queryBase) . ($queryBase ? '&' : '') . 'page=');
$pagination = [
    'current'     => (int) $result['page'],
    'total_pages' => (int) $result['total_pages'],
    'base_url'    => $paginationBase,
];

$pageTitle = 'Shop';
$pageDescription = 'Shop premium streetwear and designer fashion at Art on Denim.';
$bodyClass = 'page-shop';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container">
        <header class="section-header catalogue-header">
            <div>
                <p class="eyebrow">Catalogue</p>
                <h1>Shop</h1>
                <p><?= (int) $result['total'] ?> piece<?= (int) $result['total'] === 1 ? '' : 's' ?> available</p>
            </div>
        </header>

        <?php if (!$dbReady): ?>
            <div class="db-notice" role="status">
                <p>Import <code>database/art_on_denim.sql</code> to load the catalogue.</p>
            </div>
        <?php else: ?>
            <div class="catalogue-layout">
                <?php
                $sortOptions = [
                    'newest' => 'Newest',
                    'featured' => 'Featured',
                    'price_asc' => 'Price: Low to High',
                    'price_desc' => 'Price: High to Low',
                    'name_asc' => 'Name A–Z',
                ];
                $sortLabel = $sortOptions[$filters['sort']] ?? 'Newest';
                $minSlider = $filters['min_price'] !== '' ? (int) $filters['min_price'] : 0;
                $maxSlider = $filters['max_price'] !== '' ? (int) $filters['max_price'] : 20000;
                ?>
                <aside class="glass-card catalogue-filters" aria-label="Product filters">
                    <form method="get" action="<?= e(url('pages/shop.php')) ?>" class="filter-form">
                        <div class="card-header">
                            <div class="header-dropdown-container">
                                <input class="hidden-toggle" type="checkbox" id="sort-menu">
                                <label class="header-dropdown" for="sort-menu">
                                    <span class="icon-circle" aria-hidden="true"></span>
                                    <span class="title"><?= e($sortLabel) ?></span>
                                    <svg class="arrow-down" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true">
                                        <path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                    </svg>
                                </label>
                                <div class="dropdown-menu">
                                    <?php foreach ($sortOptions as $value => $label): ?>
                                        <label class="dropdown-item<?= $filters['sort'] === $value ? ' active' : '' ?>">
                                            <input type="radio" name="sort" value="<?= e($value) ?>" <?= $filters['sort'] === $value ? 'checked' : '' ?>>
                                            <?= e($label) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <span class="beta-tag">Catalogue</span>
                            <a class="close-btn" href="<?= e(url('pages/shop.php')) ?>" aria-label="Clear filters">×</a>
                        </div>

                        <hr class="divider">

                        <div class="form-field">
                            <label for="q">Search</label>
                            <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Search products">
                        </div>

                        <div class="form-field">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">All categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= e($cat['slug']) ?>" <?= $filters['category'] === $cat['slug'] ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="brand">Brand</label>
                            <select id="brand" name="brand">
                                <option value="">All brands</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= e($brand['slug']) ?>" <?= $filters['brand'] === $brand['slug'] ? 'selected' : '' ?>>
                                        <?= e($brand['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">All</option>
                                <option value="unisex" <?= $filters['gender'] === 'unisex' ? 'selected' : '' ?>>Unisex</option>
                                <option value="men" <?= $filters['gender'] === 'men' ? 'selected' : '' ?>>Men</option>
                                <option value="women" <?= $filters['gender'] === 'women' ? 'selected' : '' ?>>Women</option>
                            </select>
                        </div>

                        <div class="sliders-container">
                            <div class="slider-row">
                                <label for="min_price">Min</label>
                                <div class="slider-content">
                                    <div class="slider-wrapper">
                                        <input class="custom-slider" type="range" id="min_price" name="min_price" min="0" max="20000" step="100" value="<?= (int) $minSlider ?>">
                                    </div>
                                    <span class="slider-divider" aria-hidden="true"></span>
                                    <span class="slider-value">R<?= number_format($minSlider) ?></span>
                                </div>
                            </div>
                            <div class="slider-row">
                                <label for="max_price">Max</label>
                                <div class="slider-content">
                                    <div class="slider-wrapper">
                                        <input class="custom-slider" type="range" id="max_price" name="max_price" min="0" max="20000" step="100" value="<?= (int) $maxSlider ?>">
                                    </div>
                                    <span class="slider-divider" aria-hidden="true"></span>
                                    <span class="slider-value">R<?= number_format($maxSlider) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="size">Size in stock</label>
                            <select id="size" name="size">
                                <option value="">Any size</option>
                                <?php foreach ($sizes as $size): ?>
                                    <option value="<?= e($size) ?>" <?= $filters['size'] === $size ? 'selected' : '' ?>><?= e($size) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-checks">
                            <label class="check-label">
                                <input type="checkbox" name="new" value="1" <?= $filters['is_new'] ? 'checked' : '' ?>>
                                New arrivals
                            </label>
                            <label class="check-label">
                                <input type="checkbox" name="sale" value="1" <?= $filters['is_on_sale'] ? 'checked' : '' ?>>
                                On sale
                            </label>
                        </div>

                        <?php if ($filters['collection']): ?>
                            <input type="hidden" name="collection" value="<?= e($filters['collection']) ?>">
                        <?php endif; ?>

                        <button type="submit" class="btn btn--brass btn--block">Apply filters</button>
                        <a class="filter-reset" href="<?= e(url('pages/shop.php')) ?>">Clear all</a>
                    </form>
                </aside>

                <div class="catalogue-results">
                    <?php if ($result['items']): ?>
                        <div class="product-grid product-grid--shop">
                            <?php foreach ($result['items'] as $product): ?>
                                <?php require dirname(__DIR__) . '/includes/product-card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php require dirname(__DIR__) . '/includes/pagination.php'; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <h2>No products found</h2>
                            <p>Try adjusting your filters or search terms.</p>
                            <a class="btn btn--inverse" href="<?= e(url('pages/shop.php')) ?>">Reset filters</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
