<?php
/**
 * Admin — Customer details
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT id, first_name, last_name, email, phone, role, status,
            address_line1, address_line2, city, province, postal_code, country,
            last_login_at, created_at, updated_at
     FROM users
     WHERE id = :id AND role = 'customer'
     LIMIT 1"
);
$stmt->execute([':id' => $id]);
$customer = $stmt->fetch();

if (!$customer) {
    flash('error', 'Customer not found.');
    redirect('admin/customers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token.');
        redirect('admin/customer-details.php?id=' . $id);
    }
    $status = $_POST['status'] ?? '';
    if (in_array($status, ['active', 'inactive', 'banned'], true)) {
        db()->prepare('UPDATE users SET status = :status WHERE id = :id')->execute([
            ':status' => $status,
            ':id' => $id,
        ]);
        flash('success', 'Customer status updated.');
    }
    redirect('admin/customer-details.php?id=' . $id);
}

$orders = db()->prepare('SELECT * FROM orders WHERE user_id = :uid ORDER BY placed_at DESC LIMIT 20');
$orders->execute([':uid' => $id]);
$orderRows = $orders->fetchAll();

$pageTitle = 'Customer';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-grid-2">
    <section class="admin-panel">
        <h2><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></h2>
        <p><?= e($customer['email']) ?><br><?= e((string) ($customer['phone'] ?? '')) ?></p>
        <p class="muted">
            <?= e((string) ($customer['address_line1'] ?? '')) ?><br>
            <?= e(trim(($customer['city'] ?? '') . ' ' . ($customer['province'] ?? '') . ' ' . ($customer['postal_code'] ?? ''))) ?>
        </p>
        <p>Joined <?= e(date('d M Y', strtotime($customer['created_at']))) ?></p>

        <form method="post" class="admin-form" style="margin-top:1.25rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="admin-field">
                <label for="status">Account status</label>
                <select id="status" name="status">
                    <?php foreach (['active','inactive','banned'] as $s): ?>
                        <option value="<?= $s ?>" <?= $customer['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="admin-btn">Update status</button>
        </form>
    </section>

    <section class="admin-panel">
        <h2>Orders</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Order</th><th>Status</th><th>Total</th></tr></thead>
                <tbody>
                    <?php if (!$orderRows): ?>
                        <tr><td colspan="3" class="muted">No orders.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orderRows as $o): ?>
                            <tr>
                                <td><a href="<?= e(url('admin/order-details.php?id=' . (int) $o['id'])) ?>"><?= e($o['order_number']) ?></a></td>
                                <td><?= e(order_status_label($o['status'])) ?></td>
                                <td><?= e(money((float) $o['total'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
