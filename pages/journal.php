<?php
/**
 * Art on Denim — Journal index
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

try {
    $posts = published_posts(12);
} catch (Throwable $e) {
    $posts = [];
    error_log('AOD journal: ' . $e->getMessage());
}

$pageTitle = 'Journal';
$pageDescription = 'Studio notes from Art on Denim on fit, cloth, shipping, and care.';
$bodyClass = 'page-journal';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container">
        <header class="section-header">
            <div>
                <p class="eyebrow">Studio</p>
                <h1>Journal</h1>
                <p>Notes from the Johannesburg workroom. Ten pieces are published so the archive is readable on day one.</p>
            </div>
        </header>

        <?php if (!$posts): ?>
            <div class="empty-state">
                <h2>The journal is quiet</h2>
                <p>Import the database, then refresh. Posts are created with the store.</p>
            </div>
        <?php else: ?>
            <div class="journal-grid">
                <?php foreach ($posts as $post): ?>
                    <a class="journal-card" href="<?= e(url('pages/journal-post.php?slug=' . urlencode($post['slug']))) ?>">
                        <p class="journal-card__date"><?= e(date('d M Y', strtotime($post['published_at']))) ?></p>
                        <h3><?= e($post['title']) ?></h3>
                        <p><?= e($post['excerpt']) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
