<?php
/**
 * Art on Denim — Admin document head + shell open
 *
 * Expects: $pageTitle, optional $pageDescription
 */
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Admin';
$pageDescription = $pageDescription ?? 'Art on Denim administration';
$adminUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($pageTitle) ?> | AOD Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
<a class="skip-link" href="#admin-main">Skip to content</a>
<div class="admin-shell">
<?php require __DIR__ . '/admin-sidebar.php'; ?>
<div class="admin-main-wrap">
    <header class="admin-topbar">
        <div>
            <p class="admin-topbar__eyebrow">Art on Denim</p>
            <h1 class="admin-topbar__title"><?= e($pageTitle) ?></h1>
        </div>
        <div class="admin-topbar__actions">
            <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View store</a>
            <span><?= e(($adminUser['first_name'] ?? 'Admin') . ' ' . ($adminUser['last_name'] ?? '')) ?></span>
            <form method="post" action="<?= e(url('admin/logout.php')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="admin-link-btn">Sign out</button>
            </form>
        </div>
    </header>
    <div class="admin-alerts">
        <?php require __DIR__ . '/alerts.php'; ?>
    </div>
    <main id="admin-main" class="admin-content">
