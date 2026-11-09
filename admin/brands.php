<?php
/**
 * Admin — Brands
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token.');
        redirect('admin/brands.php');
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash('error', 'Brand name is required.');
        } else {
            db()->prepare(
                'INSERT INTO brands (name, slug, description, is_featured, is_active)
                 VALUES (:name, :slug, :description, :featured, 1)'
            )->execute([
                ':name' => $name,
                ':slug' => slugify($name),
                ':description' => trim((string) ($_POST['description'] ?? '')) ?: null,
                ':featured' => !empty($_POST['is_featured']) ? 1 : 0,
            ]);
            flash('success', 'Brand created.');
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $field = ($_POST['field'] ?? 'is_active') === 'is_featured' ? 'is_featured' : 'is_active';
        db()->prepare("UPDATE brands SET {$field} = IF({$field}=1,0,1) WHERE id = :id")->execute([':id' => $id]);
        flash('success', 'Brand updated.');
    }

    redirect('admin/brands.php');
}

$brands = db()->query('SELECT * FROM brands ORDER BY name ASC')->fetchAll();
$pageTitle = 'Brands';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-grid-2">
    <section class="admin-panel">
        <h2>All brands</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Name</th><th>Featured</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($brands as $b): ?>
                    <tr>
                        <td><?= e($b['name']) ?></td>
                        <td><?= $b['is_featured'] ? 'Yes' : 'No' ?></td>
                        <td><span class="badge <?= $b['is_active'] ? 'badge--ok' : 'badge--danger' ?>"><?= $b['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td class="stack-actions">
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="field" value="is_featured">
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">Feature</button>
                            </form>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="field" value="is_active">
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
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
        <h2>Add brand</h2>
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
            <label class="admin-checks"><input type="checkbox" name="is_featured" value="1"> Featured brand</label>
            <button type="submit" class="admin-btn">Create</button>
        </form>
    </section>
</div>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
