<?php
/**
 * Admin — Contact messages
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token.');
        redirect('admin/messages.php');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id > 0 && in_array($status, ['new','read','replied','archived'], true)) {
        db()->prepare('UPDATE contact_messages SET status = :status WHERE id = :id')
            ->execute([':status' => $status, ':id' => $id]);
        flash('success', 'Message updated.');
    }
    redirect('admin/messages.php');
}

$messages = db()->query(
    'SELECT * FROM contact_messages ORDER BY FIELD(status, "new","read","replied","archived"), created_at DESC'
)->fetchAll();

$pageTitle = 'Messages';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<section class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$messages): ?>
                    <tr><td colspan="6" class="muted">No messages yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <tr>
                            <td><?= e(date('d M Y H:i', strtotime($m['created_at']))) ?></td>
                            <td>
                                <?= e($m['name']) ?><br>
                                <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
                            </td>
                            <td><?= e($m['subject']) ?></td>
                            <td style="max-width:280px;"><?= e(strlen($m['message']) > 120 ? substr($m['message'], 0, 117) . '...' : $m['message']) ?></td>
                            <td><span class="badge"><?= e($m['status']) ?></span></td>
                            <td>
                                <form method="post" class="stack-actions">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                    <select name="status" style="background:#1b2330;color:#fff;border:1px solid var(--admin-border);min-height:2.2rem;">
                                        <?php foreach (['new','read','replied','archived'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $m['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="admin-btn admin-btn--sm">Save</button>
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
