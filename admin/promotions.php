<?php
/**
 * Admin — Promotions
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token.');
        redirect('admin/promotions.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            flash('error', 'Promotion is required.');
        } else {
            db()->prepare(
                'INSERT INTO promotions (
                    title, slug, subtitle, description, promo_type, discount_type, discount_value,
                    brand_id, cta_label, cta_url, placement, sort_order, is_active, starts_at, ends_at
                 ) VALUES (
                    :title, :slug, :subtitle, :description, :promo_type, :discount_type, :discount_value,
                    :brand_id, :cta_label, :cta_url, :placement, :sort_order, 1, :starts_at, :ends_at
                 )'
            )->execute([
                ':title' => $title,
                ':slug' => slugify($title) . '-' . substr(bin2hex(random_bytes(2)), 0, 4),
                ':subtitle' => trim((string) ($_POST['subtitle'] ?? '')) ?: null,
                ':description' => trim((string) ($_POST['description'] ?? '')) ?: null,
                ':promo_type' => in_array($_POST['promo_type'] ?? '', ['banner','campaign','coupon','sale'], true) ? $_POST['promo_type'] : 'campaign',
                ':discount_type' => in_array($_POST['discount_type'] ?? '', ['percent','fixed','none'], true) ? $_POST['discount_type'] : 'none',
                ':discount_value' => ($_POST['discount_value'] ?? '') !== '' ? (float) $_POST['discount_value'] : null,
                ':brand_id' => (int) ($_POST['brand_id'] ?? 0) ?: null,
                ':cta_label' => trim((string) ($_POST['cta_label'] ?? '')) ?: null,
                ':cta_url' => safe_app_url(trim((string) ($_POST['cta_url'] ?? '')), 'pages/shop.php'),
                ':placement' => in_array($_POST['placement'] ?? '', ['hero','home_mid','home_sale','sidebar','checkout'], true) ? $_POST['placement'] : 'hero',
                ':sort_order' => (int) ($_POST['sort_order'] ?? 0),
                ':starts_at' => ($_POST['starts_at'] ?? '') !== '' ? $_POST['starts_at'] . ' 00:00:00' : null,
                ':ends_at' => ($_POST['ends_at'] ?? '') !== '' ? $_POST['ends_at'] . ' 23:59:59' : null,
            ]);
            flash('success', 'Promotion created.');
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('UPDATE promotions SET is_active = IF(is_active=1,0,1) WHERE id = :id')->execute([':id' => $id]);
        flash('success', 'Promotion updated.');
    }

    redirect('admin/promotions.php');
}

$promos = db()->query('SELECT p.*, b.name AS brand_name FROM promotions p LEFT JOIN brands b ON b.id = p.brand_id ORDER BY sort_order ASC, id DESC')->fetchAll();
$brands = get_brands_list(false);

$pageTitle = 'Promotions';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-grid-2">
    <section class="admin-panel">
        <h2>Campaigns</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr><th>Title</th><th>Placement</th><th>Discount</th><th>Active</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($promos as $p): ?>
                        <tr>
                            <td>
                                <?= e($p['title']) ?>
                                <?php if ($p['brand_name']): ?><br><span class="muted"><?= e($p['brand_name']) ?></span><?php endif; ?>
                            </td>
                            <td><?= e($p['placement']) ?></td>
                            <td>
                                <?php if ($p['discount_type'] === 'percent'): ?>
                                    <?= e((string) $p['discount_value']) ?>%
                                <?php elseif ($p['discount_type'] === 'fixed'): ?>
                                    <?= e(money((float) $p['discount_value'])) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $p['is_active'] ? 'badge--ok' : 'badge--danger' ?>"><?= $p['is_active'] ? 'Yes' : 'No' ?></span></td>
                            <td>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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
        <h2>Create promotion</h2>
        <form method="post" class="admin-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="admin-field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="admin-field">
                <label for="subtitle">Subtitle</label>
                <input type="text" id="subtitle" name="subtitle">
            </div>
            <div class="admin-field">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
            </div>
            <div class="admin-form-row">
                <div class="admin-field">
                    <label for="placement">Placement</label>
                    <select id="placement" name="placement">
                        <?php foreach (['hero','home_mid','home_sale','sidebar','checkout'] as $pl): ?>
                            <option value="<?= $pl ?>"><?= $pl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-field">
                    <label for="promo_type">Type</label>
                    <select id="promo_type" name="promo_type">
                        <?php foreach (['campaign','banner','sale','coupon'] as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="admin-form-row">
                <div class="admin-field">
                    <label for="discount_type">Discount type</label>
                    <select id="discount_type" name="discount_type">
                        <option value="none">None</option>
                        <option value="percent">Percent</option>
                        <option value="fixed">Fixed</option>
                    </select>
                </div>
                <div class="admin-field">
                    <label for="discount_value">Discount value</label>
                    <input type="number" step="0.01" min="0" id="discount_value" name="discount_value">
                </div>
            </div>
            <div class="admin-field">
                <label for="brand_id">Brand (optional)</label>
                <select id="brand_id" name="brand_id">
                    <option value="">—</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-row">
                <div class="admin-field">
                    <label for="cta_label">CTA label</label>
                    <input type="text" id="cta_label" name="cta_label" value="Shop now">
                </div>
                <div class="admin-field">
                    <label for="cta_url">CTA URL</label>
                    <input type="text" id="cta_url" name="cta_url" value="pages/shop.php">
                </div>
            </div>
            <div class="admin-form-row">
                <div class="admin-field">
                    <label for="starts_at">Starts</label>
                    <input type="date" id="starts_at" name="starts_at">
                </div>
                <div class="admin-field">
                    <label for="ends_at">Ends</label>
                    <input type="date" id="ends_at" name="ends_at">
                </div>
            </div>
            <div class="admin-field">
                <label for="sort_order">Sort order</label>
                <input type="number" id="sort_order" name="sort_order" value="0">
            </div>
            <button type="submit" class="admin-btn">Create promotion</button>
        </form>
    </section>
</div>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
