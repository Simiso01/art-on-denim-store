<?php
/**
 * Admin — Customers
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = "role = 'customer'";
$params = [];
if ($q !== '') {
    $where .= ' AND (email LIKE :q OR first_name LIKE :q OR last_name LIKE :q OR phone LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$count = db()->prepare("SELECT COUNT(*) FROM users WHERE {$where}");
$count->execute($params);
$total = (int) $count->fetchColumn();

$stmt = db()->prepare(
    "SELECT u.*,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count
     FROM users u
     WHERE {$where}
     ORDER BY u.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pagination = [
    'current'     => $page,
    'total_pages' => max(1, (int) ceil($total / $perPage)),
    'base_url'    => url('admin/customers.php?' . ($q !== '' ? 'q=' . urlencode($q) . '&' : '') . 'page='),
];

$pageTitle = 'Customers';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-toolbar">
    <form method="get">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search customers">
        <button class="admin-btn admin-btn--sm" type="submit">Search</button>
    </form>
    <p class="muted"><?= $total ?> customers</p>
</div>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Orders</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$customers): ?>
                    <tr><td colspan="6" class="muted">No customers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
                            <td><?= e($c['email']) ?></td>
                            <td><?= e((string) ($c['phone'] ?? '—')) ?></td>
                            <td><?= (int) $c['order_count'] ?></td>
                            <td><span class="badge"><?= e($c['status']) ?></span></td>
                            <td><a href="<?= e(url('admin/customer-details.php?id=' . (int) $c['id'])) ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php require dirname(__DIR__) . '/includes/pagination.php'; ?>
</section>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
