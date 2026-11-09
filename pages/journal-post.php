<?php
/**
 * Art on Denim — Journal article
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$post = $slug !== '' ? find_post_by_slug($slug) : null;

if (!$post) {
    http_response_code(404);
}

$pageTitle = $post['title'] ?? 'Journal';
$pageDescription = $post['excerpt'] ?? 'Art on Denim journal.';
$bodyClass = 'page-journal';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container journal-article">
        <?php if (!$post): ?>
            <div class="empty-state">
                <h1>Note not found</h1>
                <p>That journal entry is not published.</p>
                <a class="btn btn--inverse" href="<?= e(url('pages/journal.php')) ?>">Back to the journal</a>
            </div>
        <?php else: ?>
            <p class="eyebrow"><?= e(date('d M Y', strtotime($post['published_at']))) ?> · <?= e($post['author_name']) ?></p>
            <h1><?= e($post['title']) ?></h1>
            <p class="journal-article__excerpt"><?= e($post['excerpt']) ?></p>
            <div class="journal-article__body">
                <?php foreach (preg_split("/\n\s*\n/", (string) $post['body']) as $paragraph): ?>
                    <?php if (trim($paragraph) !== ''): ?>
                        <p><?= nl2br(e(trim($paragraph))) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <a class="section-link" href="<?= e(url('pages/journal.php')) ?>">All notes</a>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
