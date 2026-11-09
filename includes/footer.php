<?php
/**
 * Art on Denim — Site footer
 */
declare(strict_types=1);
?>
<footer class="site-footer" role="contentinfo">
    <div class="footer-grid">
        <div class="footer-brand">
            <p class="footer-brand__mark">AOD</p>
            <p class="footer-brand__name">Art on Denim</p>
            <p class="footer-brand__tag"><?= e(APP_TAGLINE) ?></p>
        </div>

        <nav class="footer-nav" aria-label="Shop">
            <h2 class="footer-heading">Shop</h2>
            <ul>
                <li><a href="<?= e(url('pages/shop.php')) ?>">All Products</a></li>
                <li><a href="<?= e(url('pages/new-arrivals.php')) ?>">New Arrivals</a></li>
                <li><a href="<?= e(url('pages/sale.php')) ?>">Sale</a></li>
                <li><a href="<?= e(url('pages/brands.php')) ?>">Brands</a></li>
            </ul>
        </nav>

        <nav class="footer-nav" aria-label="Support">
            <h2 class="footer-heading">Support</h2>
            <ul>
                <li><a href="<?= e(url('pages/contact.php')) ?>">Contact</a></li>
                <li><a href="<?= e(url('pages/tracking.php')) ?>">Order Tracking</a></li>
                <li><a href="<?= e(url('pages/about.php')) ?>">About</a></li>
                <li><a href="<?= e(url('pages/journal.php')) ?>">Journal</a></li>
                <li><a href="<?= e(url('pages/account.php')) ?>">Account</a></li>
            </ul>
        </nav>

        <div class="footer-newsletter">
            <h2 class="footer-heading">Newsletter</h2>
            <p>New drops, private sales, and denim craft — in your inbox.</p>
            <form class="newsletter-form" action="<?= e(url('pages/contact.php')) ?>" method="get">
                <label class="visually-hidden" for="newsletter-email">Email address</label>
                <input
                    type="email"
                    id="newsletter-email"
                    name="email"
                    placeholder="Email address"
                    required
                    autocomplete="email"
                >
                <button type="submit" class="btn btn--brass">Join</button>
            </form>
        </div>
    </div>

    <div class="atelier-chat" data-chat-endpoint="<?= e(url('api/chat.php')) ?>">
        <button type="button" class="atelier-chat__toggle btn btn--brass" aria-expanded="false" aria-controls="atelier-chat-panel">
            Ask the studio
        </button>
        <section id="atelier-chat-panel" class="atelier-chat__panel" hidden>
            <header class="atelier-chat__head">
                <p>Studio desk</p>
                <button type="button" class="atelier-chat__close" aria-label="Close assistant">Close</button>
            </header>
            <div class="atelier-chat__log" aria-live="polite">
                <p class="atelier-chat__bubble">Ask about shipping, a size, a coupon, or a piece by name.</p>
            </div>
            <form class="atelier-chat__form">
                <label class="visually-hidden" for="atelier-chat-input">Message</label>
                <input id="atelier-chat-input" name="message" type="text" maxlength="400" placeholder="Try selvedge or shipping" required>
                <button type="submit" class="btn btn--brass">Send</button>
            </form>
        </section>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
        <p class="footer-locale">South Africa · ZAR</p>
    </div>
</footer>

<script src="<?= e(asset('assets/js/app.js')) ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/js/app.js') ?>" defer></script>
<script src="<?= e(asset('assets/js/chat.js')) ?>" defer></script>
</body>
</html>
