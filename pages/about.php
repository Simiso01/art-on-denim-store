<?php
/**
 * Art on Denim — About
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'About';
$pageDescription = 'About Art on Denim — premium streetwear and designer fashion.';
$bodyClass = 'page-about';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container" style="max-width:720px;">
        <header class="section-header">
            <div>
                <p class="eyebrow">AOD</p>
                <h1>About Art on Denim</h1>
            </div>
        </header>
        <p>
            Art on Denim is a South African premium streetwear and designer fashion store,
            built around denim craft, curated international labels, and a clean modern wardrobe.
        </p>
        <p>
            From house-cut selvedge to selected Rick Owens and contemporary luxury essentials,
            every piece is chosen for presence, construction, and longevity.
        </p>
        <p>
            <a class="btn btn--inverse" href="<?= e(url('pages/shop.php')) ?>">Shop the collection</a>
        </p>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
