<?php
/**
 * Admin — Journal
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token.');
        redirect('admin/journal.php');
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($title === '' || $excerpt === '' || $body === '') {
            flash('error', 'Title, excerpt, and body are required.');
        } else {
            $slug = slugify($title);
            db()->prepare(
                'INSERT INTO blog_posts (title, slug, excerpt, body, author_name, status)
                 VALUES (:title, :slug, :excerpt, :body, :author, :status)'
            )->execute([
                ':title' => $title,
                ':slug' => $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 4),
                ':excerpt' => $excerpt,
                ':body' => $body,
                ':author' => 'Art on Denim Studio',
                ':status' => ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published',
            ]);
            flash('success', 'Journal note saved.');
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare(
            "UPDATE blog_posts SET status = IF(status = 'published', 'draft', 'published') WHERE id = :id"
        )->execute([':id' => $id]);
        flash('success', 'Journal note updated.');
    }

    redirect('admin/journal.php');
}

$posts = db()->query('SELECT id, title, slug, status, published_at FROM blog_posts ORDER BY published_at DESC')->fetchAll();
$pageTitle = 'Journal';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-grid-2">
    <section class="admin-panel">
        <h2>Notes</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Title</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?= e($post['title']) ?></td>
                        <td><?= e($post['status']) ?></td>
                        <td>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                                <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">Toggle</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="admin-panel">
        <h2>New note</h2>
        <form method="post" class="admin-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="admin-field">
                <label for="title">Title</label>
                <input id="title" name="title" required maxlength="200">
            </div>
            <div class="admin-field">
                <label for="excerpt">Excerpt</label>
                <input id="excerpt" name="excerpt" required maxlength="500">
            </div>
            <div class="admin-field">
                <label for="body">Body</label>
                <textarea id="body" name="body" rows="8" required></textarea>
            </div>
            <div class="admin-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <button type="submit" class="admin-btn">Publish note</button>
        </form>
    </section>
</div>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
