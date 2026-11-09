<?php
/**
 * Art on Denim — Customer account dashboard
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$user = current_user();
$full = find_user_by_id((int) $user['id']);

$pageTitle = 'My Account';
$pageDescription = 'Manage your Art on Denim account.';
$bodyClass = 'page-account';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container account-layout">
        <header class="section-header">
            <div>
                <p class="eyebrow">Account</p>
                <h1>Hello, <?= e($user['first_name']) ?></h1>
                <p>Manage your profile, orders, and wishlist.</p>
            </div>
        </header>

        <div class="account-grid">
            <a class="account-tile" href="<?= e(url('pages/profile.php')) ?>">
                <h2>Profile</h2>
                <p>Update your details and address.</p>
            </a>
            <a class="account-tile" href="<?= e(url('pages/orders.php')) ?>">
                <h2>Orders</h2>
                <p>View order history and status.</p>
            </a>
            <a class="account-tile" href="<?= e(url('pages/wishlist.php')) ?>">
                <h2>Wishlist</h2>
                <p>Saved pieces ready to revisit.</p>
            </a>
            <a class="account-tile" href="<?= e(url('pages/tracking.php')) ?>">
                <h2>Tracking</h2>
                <p>Track a shipment by order number.</p>
            </a>
            <a class="account-tile" href="<?= e(url('pages/notifications.php')) ?>">
                <h2>Notifications</h2>
                <p>Order and account updates.</p>
            </a>
        </div>

        <div class="account-meta">
            <p><strong>Email:</strong> <?= e($full['email'] ?? $user['email']) ?></p>
            <?php if (!empty($full['phone'])): ?>
                <p><strong>Phone:</strong> <?= e($full['phone']) ?></p>
            <?php endif; ?>

            <form method="post" action="<?= e(url('pages/logout.php')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--ghost-dark">Sign out</button>
            </form>
        </div>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
