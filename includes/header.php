<?php
/**
 * Art on Denim — HTML <head> + opening body
 *
 * Expected variables (optional):
 *   $pageTitle (string)
 *   $pageDescription (string)
 *   $bodyClass (string)
 *   $extraCss (array of relative CSS paths)
 */

declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? (APP_NAME . ' — ' . APP_TAGLINE);
$bodyClass = $bodyClass ?? '';
$extraCss = $extraCss ?? [];
$fullTitle = ($pageTitle === APP_NAME)
    ? APP_NAME . ' | ' . APP_TAGLINE
    : $pageTitle . ' | ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#121820">
    <title><?= e($fullTitle) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/responsive.css')) ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/css/responsive.css') ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/atelier.css')) ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/css/atelier.css') ?>">
    <?php foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?= e(asset($css)) ?>">
    <?php endforeach; ?>
</head>
<body class="<?= e(trim($bodyClass . (strpos($bodyClass, 'page-home') !== false ? ' splash-open' : ''))) ?>">
<?php if (strpos($bodyClass, 'page-home') !== false): ?>
<div class="aod-splash" id="page-loader" role="status" aria-live="polite" aria-label="Loading Art on Denim">
    <div class="aod-splash__stage">
        <!-- From Uiverse.io by Nawsome -->
        <div class="pl">
            <?php for ($dot = 0; $dot < 12; $dot++): ?>
                <div class="pl__dot"></div>
            <?php endfor; ?>
            <div class="pl__text">
                <strong>AOD</strong>
                <span>Art on Denim</span>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="page-loader" id="page-loader" role="status" aria-live="polite" aria-label="Loading Art on Denim">
    <span class="page-loader__bar"></span>
</div>
<?php endif; ?>
<a class="skip-link" href="#main-content">Skip to content</a>
