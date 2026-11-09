<?php
/**
 * Art on Denim — Homepage
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = APP_NAME;
$pageDescription = 'Art on Denim — premium streetwear and designer fashion. Shop AOD, Rick Owens, and curated luxury.';
$bodyClass = 'page-home';

$dbReady = true;
$dbError = null;
$heroPromo = null;
$midPromo = null;
$salePromo = null;
$newArrivals = [];
$featured = [];
$aodCollection = [];
$rickOwens = [];
$saleProducts = [];
$brands = [];

try {
    $heroList = get_promotions('hero', 1);
    $heroPromo = $heroList[0] ?? null;

    $midList = get_promotions('home_mid', 1);
    $midPromo = $midList[0] ?? null;

    $saleList = get_promotions('home_sale', 1);
    $salePromo = $saleList[0] ?? null;

    $newArrivals = get_products(['is_new' => true], 4);
    $featured = get_products(['is_featured' => true], 4);
    $aodCollection = get_products(['is_aod_collection' => true], 4);
    $rickOwens = get_products(['brand_slug' => 'rick-owens'], 4);
    $saleProducts = get_products(['is_on_sale' => true], 4);
    $brands = get_featured_brands(6);
} catch (Throwable $e) {
    $dbReady = false;
    $dbError = APP_DEBUG ? $e->getMessage() : 'Database unavailable';
    error_log('AOD homepage DB error: ' . $e->getMessage());
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navigation.php';
require __DIR__ . '/includes/alerts.php';
?>

<main id="main-content">

    <?php if (!$dbReady): ?>
        <div class="db-notice" role="status">
            <h2>Database setup required</h2>
            <p>
                Import <code>database/art_on_denim.sql</code> via phpMyAdmin, then refresh this page.
                Confirm credentials in <code>config/database.php</code>.
            </p>
            <?php if ($dbError): ?>
                <p><small><?= e($dbError) ?></small></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Hero: brand-first, full-bleed -->
    <section class="hero" aria-label="Featured campaign">
        <div class="hero__media" aria-hidden="true">
            <img src="<?= e(asset('assets/images/hero-denim.jpg')) ?>" alt="">
        </div>
        <div class="hero__overlay" aria-hidden="true"></div>

        <div class="hero__content">
            <p class="hero__index">01 — Johannesburg</p>
            <p class="hero__brand">Art on Denim</p>

            <?php if ($heroPromo): ?>
                <p class="hero__eyebrow">Campaign</p>
                <h1 class="hero__title"><?= e($heroPromo['title']) ?></h1>
                <p class="hero__text">
                    <?= e($heroPromo['subtitle'] ?: ($heroPromo['description'] ?? '')) ?>
                </p>
                <div class="hero__actions">
                    <a class="btn btn--primary" href="<?= e(url(safe_app_url($heroPromo['cta_url'] ?? null, 'pages/shop.php'))) ?>">
                        <?= e($heroPromo['cta_label'] ?: 'Shop Now') ?>
                    </a>
                    <a class="btn btn--ghost" href="<?= e(url('pages/new-arrivals.php')) ?>">New Arrivals</a>
                </div>
            <?php else: ?>
                <p class="hero__eyebrow">Premium streetwear &amp; designer fashion</p>
                <h1 class="hero__title">Denim craft. Designer edge.</h1>
                <p class="hero__text">
                    Curated house pieces and international labels — built for presence.
                </p>
                <div class="hero__actions">
                    <a class="btn btn--primary" href="<?= e(url('pages/shop.php')) ?>">Shop Collection</a>
                    <a class="btn btn--ghost" href="<?= e(url('pages/new-arrivals.php')) ?>">New Arrivals</a>
                </div>
            <?php endif; ?>
        </div>
        <p class="hero__meta">House denim · Designer edit · Priced in rand</p>
    </section>

    <section class="floor" aria-labelledby="new-arrivals-heading">
        <div class="container">
            <div class="section-header">
                <div>
                    <p class="eyebrow">02 — The floor</p>
                    <h2 id="new-arrivals-heading">New arrivals</h2>
                </div>
                <a class="section-link" href="<?= e(url('pages/new-arrivals.php')) ?>">View all</a>
            </div>
            <?php if ($newArrivals): ?>
                <div class="product-grid product-grid--floor">
                    <?php foreach ($newArrivals as $product): ?>
                        <?php require __DIR__ . '/includes/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="split-edit" aria-labelledby="aod-promo-heading">
        <div class="split-edit__story">
            <p class="eyebrow">House label</p>
            <h2 id="aod-promo-heading"><?= e($midPromo['title'] ?? 'New AOD Collection') ?></h2>
            <p><?= e($midPromo['subtitle'] ?? 'House denim, redefined.') ?></p>
            <a class="btn btn--primary" href="<?= e(url('pages/shop.php?collection=aod')) ?>">Explore the house</a>
        </div>
        <div class="split-edit__list">
            <?php foreach (array_slice($aodCollection, 0, 4) as $product): ?>
                <a class="edit-row" href="<?= e(url('pages/product.php?id=' . (int) $product['id'])) ?>">
                    <img src="<?= e(product_image_url($product['image'] ?? null)) ?>" alt="">
                    <span>
                        <small><?= e($product['brand_name'] ?? 'Art on Denim') ?></small>
                        <strong><?= e($product['name']) ?></strong>
                    </span>
                    <em><?= e(money((float) $product['price'])) ?></em>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($brands): ?>
        <section class="brand-marquee" aria-label="Featured brands">
            <div class="brand-marquee__track">
                <?php foreach (array_merge($brands, $brands) as $brand): ?>
                    <a href="<?= e(url('pages/brands.php?slug=' . urlencode($brand['slug']))) ?>"><?= e($brand['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($saleProducts): ?>
        <section class="floor floor--sale" aria-labelledby="sale-products-heading">
            <div class="container">
                <div class="section-header">
                    <div>
                        <p class="eyebrow">Limited</p>
                        <h2 id="sale-products-heading"><?= e($salePromo['title'] ?? 'On sale') ?></h2>
                    </div>
                    <a class="section-link" href="<?= e(url('pages/sale.php')) ?>">View sale</a>
                </div>
                <div class="product-grid product-grid--floor">
                    <?php foreach (array_slice($saleProducts, 0, 4) as $product): ?>
                        <?php require __DIR__ . '/includes/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php
    $journalPosts = [];
    try {
        $journalPosts = published_posts(3);
    } catch (Throwable $e) {
        $journalPosts = [];
    }
    ?>
    <?php if ($journalPosts): ?>
        <section class="section journal-teaser" aria-labelledby="journal-heading">
            <div class="container">
                <div class="section-header">
                    <div>
                        <h2 id="journal-heading">Journal</h2>
                        <p>Studio notes on fit, cloth, and how an order moves.</p>
                    </div>
                    <a class="section-link" href="<?= e(url('pages/journal.php')) ?>">Read the journal</a>
                </div>
                <div class="journal-grid">
                    <?php foreach ($journalPosts as $post): ?>
                        <a class="journal-card" href="<?= e(url('pages/journal-post.php?slug=' . urlencode($post['slug']))) ?>">
                            <p class="journal-card__date"><?= e(date('d M Y', strtotime($post['published_at']))) ?></p>
                            <h3><?= e($post['title']) ?></h3>
                            <p><?= e($post['excerpt']) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
