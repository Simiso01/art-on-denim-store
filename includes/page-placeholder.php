<?php
/**
 * Shared minimal page renderer for routes not yet fully built.
 * Each public page still outputs complete HTML5 via header/nav/footer.
 */
declare(strict_types=1);

function render_placeholder_page(
    string $title,
    string $description,
    string $heading,
    string $message
): void {
    $pageTitle = $title;
    $pageDescription = $description;
    $bodyClass = 'page-placeholder';

    require dirname(__DIR__) . '/includes/header.php';
    require dirname(__DIR__) . '/includes/navigation.php';
    require dirname(__DIR__) . '/includes/alerts.php';
    ?>
    <main id="main-content" class="section">
        <div class="container">
            <header class="section-header">
                <div>
                    <h1><?= e($heading) ?></h1>
                    <p><?= e($message) ?></p>
                </div>
            </header>
            <p>
                <a class="btn btn--inverse" href="<?= e(url('index.php')) ?>">Back to home</a>
            </p>
        </div>
    </main>
    <?php
    require dirname(__DIR__) . '/includes/footer.php';
}
