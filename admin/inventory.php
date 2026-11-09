<?php
/**
 * Admin — Inventory
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token.');
        redirect('admin/inventory.php');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $qty = max(0, (int) ($_POST['quantity'] ?? 0));
    $action = $_POST['action'] ?? 'set';

    if ($action === 'add') {
        db()->prepare('UPDATE inventory SET quantity = quantity + :qty WHERE id = :id')
            ->execute([':qty' => $qty, ':id' => $id]);
        flash('success', 'Stock added.');
    } else {
        db()->prepare('UPDATE inventory SET quantity = :qty WHERE id = :id')
            ->execute([':qty' => $qty, ':id' => $id]);
        flash('success', 'Stock updated.');
    }

    // Auto-archive product if all sizes are 0
    $stmt = db()->prepare('SELECT product_id FROM inventory WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $productId = (int) $stmt->fetchColumn();
    if ($productId) {
        $sum = db()->prepare('SELECT COALESCE(SUM(quantity),0) FROM inventory WHERE product_id = :pid');
        $sum->execute([':pid' => $productId]);
        $total = (int) $sum->fetchColumn();
        if ($total < 1) {
            db()->prepare("UPDATE products SET status = 'archived' WHERE id = :id")->execute([':id' => $productId]);
            flash('warning', 'Product archived because stock reached zero.');
        }
    }

    redirect('admin/inventory.php' . (!empty($_GET['low']) ? '?low=1' : ''));
}

$lowOnly = !empty($_GET['low']);
$sql = "SELECT i.*, p.name AS product_name, p.sku, p.status
        FROM inventory i
        INNER JOIN products p ON p.id = i.product_id";
if ($lowOnly) {
    $sql .= ' WHERE i.quantity <= i.low_stock_threshold';
}
$sql .= ' ORDER BY i.quantity ASC, p.name ASC';
$rows = db()->query($sql)->fetchAll();

$pageTitle = 'Inventory';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-toolbar">
    <p class="muted"><?= count($rows) ?> stock rows</p>
    <div class="stack-actions">
        <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= e(url('admin/inventory.php')) ?>">All stock</a>
        <a class="admin-btn admin-btn--sm" href="<?= e(url('admin/inventory.php?low=1')) ?>">Low stock only</a>
    </div>
</div>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Size</th>
                    <th>Qty</th>
                    <th>Threshold</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="6" class="muted">No inventory rows.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e($row['sku']) ?></td>
                            <td>
                                <?= e($row['product_name']) ?>
                                <?php if ((int) $row['quantity'] <= (int) $row['low_stock_threshold']): ?>
                                    <span class="badge badge--warn">Low</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['size']) ?></td>
                            <td><?= (int) $row['quantity'] ?></td>
                            <td><?= (int) $row['low_stock_threshold'] ?></td>
                            <td>
                                <form method="post" class="stack-actions">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <input type="number" name="quantity" min="0" value="1" style="width:5rem;min-height:2.2rem;background:#1b2330;border:1px solid var(--admin-border);color:#fff;padding:.3rem;">
                                    <button type="submit" name="action" value="add" class="admin-btn admin-btn--sm">Add</button>
                                    <button type="submit" name="action" value="set" class="admin-btn admin-btn--ghost admin-btn--sm">Set</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
