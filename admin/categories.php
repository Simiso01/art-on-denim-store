<?php
/**
 * Admin — Categories
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token.');
        redirect('admin/categories.php');
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash('error', 'Category name is required.');
        } else {
            $slug = slugify($name);
            db()->prepare(
                'INSERT INTO categories (name, slug, description, is_active) VALUES (:name, :slug, :description, 1)'
            )->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            ]);
            flash('success', 'Category created.');
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('UPDATE categories SET is_active = IF(is_active=1,0,1) WHERE id = :id')->execute([':id' => $id]);
        flash('success', 'Category updated.');
    }

    redirect('admin/categories.php');
}

$categories = db()->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();
$pageTitle = 'Categories';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-grid-2">
    <section class="admin-panel">
        <h2>All categories</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Name</th><th>Slug</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><?= e($c['name']) ?></td>
                        <td class="muted"><?= e($c['slug']) ?></td>
                        <td><span class="badge <?= $c['is_active'] ? 'badge--ok' : 'badge--danger' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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
        <h2>Add category</h2>
        <form method="post" class="admin-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="admin-field">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="admin-field">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
            </div>
            <button type="submit" class="admin-btn">Create</button>
        </form>
    </section>
</div>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
