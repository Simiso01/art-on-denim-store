<?php
/**
 * Admin — Products list
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token.');
        redirect('admin/products.php');
    }
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'archive' && $id > 0) {
        db()->prepare("UPDATE products SET status = 'archived' WHERE id = :id")->execute([':id' => $id]);
        flash('success', 'Product archived.');
    }
    if ($action === 'activate' && $id > 0) {
        db()->prepare("UPDATE products SET status = 'active' WHERE id = :id")->execute([':id' => $id]);
        flash('success', 'Product activated.');
    }
    redirect('admin/products.php' . ($q !== '' ? '?q=' . urlencode($q) : ''));
}

$result = admin_list_products($q, $page);
$pagination = [
    'current'     => $result['page'],
    'total_pages' => $result['total_pages'],
    'base_url'    => url('admin/products.php?' . ($q !== '' ? 'q=' . urlencode($q) . '&' : '') . 'page='),
];

$pageTitle = 'Products';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-toolbar">
    <form method="get" action="<?= e(url('admin/products.php')) ?>">
        <label class="visually-hidden" for="q">Search products</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Search name or SKU">
        <button type="submit" class="admin-btn admin-btn--sm">Search</button>
    </form>
    <a class="admin-btn" href="<?= e(url('admin/add-product.php')) ?>">Add product</a>
</div>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$result['items']): ?>
                    <tr><td colspan="7" class="muted">No products found.</td></tr>
                <?php else: ?>
                    <?php foreach ($result['items'] as $p): ?>
                        <tr>
                            <td><?= e($p['sku']) ?></td>
                            <td><?= e($p['name']) ?></td>
                            <td><?= e($p['brand_name'] ?? '—') ?></td>
                            <td><?= e(money((float) $p['price'])) ?></td>
                            <td>
                                <?php $stock = (int) $p['stock_total']; ?>
                                <span class="badge <?= $stock <= LOW_STOCK_THRESHOLD ? 'badge--warn' : 'badge--ok' ?>">
                                    <?= $stock ?>
                                </span>
                            </td>
                            <td><span class="badge"><?= e($p['status']) ?></span></td>
                            <td class="stack-actions">
                                <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= e(url('admin/edit-product.php?id=' . (int) $p['id'])) ?>">Edit</a>
                                <form method="post" action="<?= e(url('admin/products.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <?php if ($p['status'] === 'active'): ?>
                                        <input type="hidden" name="action" value="archive">
                                        <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm" data-confirm="Archive this product?">Archive</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="activate">
                                        <button type="submit" class="admin-btn admin-btn--ghost admin-btn--sm">Activate</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php require dirname(__DIR__) . '/includes/pagination.php'; ?>
</section>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
