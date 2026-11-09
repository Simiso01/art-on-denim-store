<?php
/**
 * Art on Denim — Notifications
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$userId = (int) current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token. Please try again.');
        redirect('pages/notifications.php');
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'read_all') {
        mark_all_notifications_read($userId);
        flash('success', 'All notifications marked as read.');
    } elseif ($action === 'read') {
        mark_notification_read($userId, (int) ($_POST['id'] ?? 0));
    }

    redirect('pages/notifications.php');
}

try {
    $notifications = get_user_notifications($userId);
} catch (Throwable $e) {
    $notifications = [];
}

$pageTitle = 'Notifications';
$pageDescription = 'Your Art on Denim notifications.';
$bodyClass = 'page-notifications';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container" style="max-width:860px;">
        <header class="section-header">
            <div>
                <p class="eyebrow">Account</p>
                <h1>Notifications</h1>
            </div>
            <div class="header-actions-inline">
                <?php if ($notifications): ?>
                    <form method="post" action="<?= e(url('pages/notifications.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="read_all">
                        <button type="submit" class="link-btn">Mark all as read</button>
                    </form>
                <?php endif; ?>
                <a class="section-link" href="<?= e(url('pages/account.php')) ?>">Back to account</a>
            </div>
        </header>

        <?php if (!$notifications): ?>
            <div class="empty-state">
                <h2>No notifications</h2>
                <p>Order updates and account messages will appear here.</p>
            </div>
        <?php else: ?>
            <ul class="notification-list">
                <?php foreach ($notifications as $note): ?>
                    <li class="notification-item <?= empty($note['is_read']) ? 'is-unread' : '' ?>">
                        <div>
                            <h2><?= e($note['title']) ?></h2>
                            <p><?= e($note['message']) ?></p>
                            <p class="muted"><?= e(date('d M Y, H:i', strtotime($note['created_at']))) ?></p>
                        </div>
                        <div class="notification-item__actions">
                            <?php if (!empty($note['link'])): ?>
                                <a href="<?= e(url($note['link'])) ?>">View</a>
                            <?php endif; ?>
                            <?php if (empty($note['is_read'])): ?>
                                <form method="post" action="<?= e(url('pages/notifications.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="read">
                                    <input type="hidden" name="id" value="<?= (int) $note['id'] ?>">
                                    <button type="submit" class="link-btn">Mark read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
