<?php
/**
 * Art on Denim — 404
 */
declare(strict_types=1);

http_response_code(404);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Page Not Found';
$pageDescription = 'The requested Art on Denim page could not be found.';
$bodyClass = 'page-404';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
?>

<main id="main-content" class="section">
    <div class="container empty-state">
        <p class="eyebrow">404</p>
        <h1>Page not found</h1>
        <p>The page you requested does not exist or may have moved.</p>
        <p>
            <a class="btn btn--inverse" href="<?= e(url('index.php')) ?>">Back to home</a>
            <a class="btn btn--ghost-dark" href="<?= e(url('pages/shop.php')) ?>">Browse shop</a>
        </p>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
