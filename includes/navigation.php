<?php
/**
 * Art on Denim — Primary navigation
 */
declare(strict_types=1);

$user = current_user();
$cartItems = cart_count();
$unreadNotifications = 0;
if ($user) {
    try {
        $unreadNotifications = unread_notification_count((int) $user['id']);
    } catch (Throwable $e) {
        $unreadNotifications = 0;
    }
}
?>
<header class="site-header" role="banner">
    <div class="announcement-bar" role="region" aria-label="Promotions">
        <p>Complimentary shipping on orders over <?= e(money(FREE_SHIPPING_THRESHOLD)) ?></p>
    </div>

    <div class="header-inner">
        <button
            type="button"
            class="nav-toggle"
            id="nav-toggle"
            aria-controls="primary-nav"
            aria-expanded="false"
            aria-label="Open menu"
        >
            <span class="nav-toggle__bar" aria-hidden="true"></span>
            <span class="nav-toggle__bar" aria-hidden="true"></span>
            <span class="nav-toggle__bar" aria-hidden="true"></span>
        </button>

        <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="<?= e(APP_NAME) ?> home">
            <span class="brand__mark">AOD</span>
            <span class="brand__name">Art on Denim</span>
        </a>

        <nav class="primary-nav" id="primary-nav" aria-label="Primary">
            <ul class="primary-nav__list">
                <li><a class="<?= e(is_active('/pages/shop')) ?>" href="<?= e(url('pages/shop.php')) ?>">Shop</a></li>
                <li><a class="<?= e(is_active('/pages/new-arrivals')) ?>" href="<?= e(url('pages/new-arrivals.php')) ?>">New</a></li>
                <li><a class="<?= e(is_active('/pages/brands')) ?>" href="<?= e(url('pages/brands.php')) ?>">Brands</a></li>
                <li><a class="<?= e(is_active('/pages/sale')) ?>" href="<?= e(url('pages/sale.php')) ?>">Sale</a></li>
                <li><a class="<?= e(is_active('/pages/journal')) ?>" href="<?= e(url('pages/journal.php')) ?>">Journal</a></li>
                <li><a class="<?= e(is_active('/pages/about')) ?>" href="<?= e(url('pages/about.php')) ?>">About</a></li>
                <li><a class="<?= e(is_active('/pages/contact')) ?>" href="<?= e(url('pages/contact.php')) ?>">Contact</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <a class="icon-link" href="<?= e(url('pages/search.php')) ?>" aria-label="Search">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </a>

            <?php if ($user): ?>
                <a class="icon-link icon-link--cart" href="<?= e(url('pages/notifications.php')) ?>" aria-label="Notifications<?= $unreadNotifications ? ', ' . $unreadNotifications . ' unread' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 9a6 6 0 0112 0c0 7 3 7 3 7H3s3 0 3-7" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                        <path d="M10 19a2 2 0 004 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="cart-badge"><?= (int) $unreadNotifications ?></span>
                    <?php endif; ?>
                </a>
                <a class="icon-link" href="<?= e(url('pages/account.php')) ?>" aria-label="Account">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.6"/>
                        <path d="M5 19c1.5-3 4-4.5 7-4.5s5.5 1.5 7 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                </a>
            <?php else: ?>
                <a class="icon-link" href="<?= e(url('pages/login.php')) ?>" aria-label="Sign in">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.6"/>
                        <path d="M5 19c1.5-3 4-4.5 7-4.5s5.5 1.5 7 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                </a>
            <?php endif; ?>

            <a class="icon-link" href="<?= e(url('pages/wishlist.php')) ?>" aria-label="Wishlist">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 20s-7-4.4-7-10a4 4 0 017-2.5A4 4 0 0119 10c0 5.6-7 10-7 10z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
            </a>

            <a class="icon-link icon-link--cart" href="<?= e(url('pages/cart.php')) ?>" aria-label="Cart<?= $cartItems ? ', ' . $cartItems . ' items' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 7h15l-1.5 9h-12L6 7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M6 7L5 3H2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    <circle cx="9" cy="20" r="1.2" fill="currentColor"/>
                    <circle cx="17" cy="20" r="1.2" fill="currentColor"/>
                </svg>
                <?php if ($cartItems > 0): ?>
                    <span class="cart-badge"><?= (int) $cartItems ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</header>
