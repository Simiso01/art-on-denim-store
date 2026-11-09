<?php
/**
 * Art on Denim — Shopping cart page
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token. Please try again.');
        redirect('pages/cart.php');
    }

    $action = $_POST['action'] ?? '';
    $itemId = (int) ($_POST['item_id'] ?? 0);

    if ($action === 'update' && $itemId > 0) {
        $qty = (int) ($_POST['quantity'] ?? 1);
        $err = cart_update($itemId, $qty);
        flash($err ? 'error' : 'success', $err ?: 'Cart updated.');
    } elseif ($action === 'remove' && $itemId > 0) {
        cart_remove($itemId);
        flash('success', 'Item removed from your cart.');
    } elseif ($action === 'coupon') {
        $message = apply_coupon_code((string) ($_POST['coupon_code'] ?? ''));
        flash($message === '' ? 'success' : 'error', $message === '' ? 'Coupon applied.' : $message);
    } elseif ($action === 'coupon_remove') {
        clear_coupon_code();
        flash('success', 'Coupon removed.');
    }

    redirect('pages/cart.php');
}

try {
    $totals = cart_totals();
    $items = $totals['items'];
} catch (Throwable $e) {
    $totals = ['items' => [], 'subtotal' => 0, 'discount' => 0, 'coupon' => null, 'shipping' => 0, 'total' => 0];
    $items = [];
    error_log('AOD cart page: ' . $e->getMessage());
}

$pageTitle = 'Shopping Cart';
$pageDescription = 'Review your Art on Denim shopping cart.';
$bodyClass = 'page-cart';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container">
        <header class="section-header">
            <div>
                <p class="eyebrow">Bag</p>
                <h1>Shopping cart</h1>
            </div>
        </header>

        <?php if (!$items): ?>
            <div class="empty-state">
                <h2>Your cart is empty</h2>
                <p>Browse the collection and add pieces you love.</p>
                <a class="btn btn--inverse" href="<?= e(url('pages/shop.php')) ?>">Continue shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-lines" role="list">
                    <?php foreach ($items as $item): ?>
                        <article class="cart-line" role="listitem">
                            <a class="cart-line__media" href="<?= e(url('pages/product.php?id=' . (int) $item['product_id'])) ?>">
                                <img
                                    src="<?= e(product_image_url($item['image'] ?? null)) ?>"
                                    alt="<?= e($item['name']) ?>"
                                    width="160"
                                    height="200"
                                >
                            </a>

                            <div class="cart-line__info">
                                <?php if (!empty($item['brand_name'])): ?>
                                    <p class="product-card__brand"><?= e($item['brand_name']) ?></p>
                                <?php endif; ?>
                                <h2 class="cart-line__title">
                                    <a href="<?= e(url('pages/product.php?id=' . (int) $item['product_id'])) ?>">
                                        <?= e($item['name']) ?>
                                    </a>
                                </h2>
                                <p>Size: <?= e($item['size']) ?></p>
                                <p class="price"><?= e(money((float) $item['unit_price'])) ?></p>

                                <form method="post" class="cart-line__qty" action="<?= e(url('pages/cart.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <label class="visually-hidden" for="qty-<?= (int) $item['id'] ?>">Quantity</label>
                                    <div class="qty-control" data-qty>
                                        <button type="button" class="qty-btn" data-qty-minus aria-label="Decrease quantity">−</button>
                                        <input
                                            type="number"
                                            id="qty-<?= (int) $item['id'] ?>"
                                            name="quantity"
                                            value="<?= (int) $item['quantity'] ?>"
                                            min="1"
                                            max="99"
                                        >
                                        <button type="button" class="qty-btn" data-qty-plus aria-label="Increase quantity">+</button>
                                    </div>
                                    <button type="submit" class="link-btn">Update</button>
                                </form>

                                <form method="post" action="<?= e(url('pages/cart.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="link-btn link-btn--danger">Remove</button>
                                </form>
                            </div>

                            <p class="cart-line__total">
                                <?= e(money((float) $item['unit_price'] * (int) $item['quantity'])) ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <aside class="cart-summary" aria-label="Order summary">
                    <h2>Summary</h2>
                    <dl class="summary-list">
                        <div>
                            <dt>Subtotal</dt>
                            <dd><?= e(money($totals['subtotal'])) ?></dd>
                        </div>
                        <?php if (($totals['discount'] ?? 0) > 0): ?>
                        <div>
                            <dt>Coupon<?= !empty($totals['coupon']['coupon_code']) ? ' ' . e($totals['coupon']['coupon_code']) : '' ?></dt>
                            <dd>−<?= e(money($totals['discount'])) ?></dd>
                        </div>
                        <?php endif; ?>
                        <div>
                            <dt>Shipping</dt>
                            <dd>
                                <?= $totals['shipping'] <= 0 ? 'Complimentary' : e(money($totals['shipping'])) ?>
                            </dd>
                        </div>
                        <div class="summary-list__total">
                            <dt>Total</dt>
                            <dd><?= e(money($totals['total'])) ?></dd>
                        </div>
                    </dl>
                    <?php if ($totals['subtotal'] < FREE_SHIPPING_THRESHOLD && $totals['subtotal'] > 0): ?>
                        <p class="summary-note">
                            Spend <?= e(money(FREE_SHIPPING_THRESHOLD - $totals['subtotal'])) ?> more for complimentary shipping.
                        </p>
                    <?php endif; ?>
                    <form class="coupon-form" method="post" action="<?= e(url('pages/cart.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="coupon">
                        <label class="visually-hidden" for="coupon_code">Coupon code</label>
                        <input id="coupon_code" name="coupon_code" type="text" maxlength="50" placeholder="Coupon code" value="<?= e((string) ($totals['coupon']['coupon_code'] ?? '')) ?>" autocomplete="off">
                        <button type="submit" class="btn btn--brass">Apply</button>
                    </form>
                    <?php if (!empty($totals['coupon'])): ?>
                        <form method="post" action="<?= e(url('pages/cart.php')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="coupon_remove">
                            <button type="submit" class="link-btn">Remove coupon</button>
                        </form>
                    <?php endif; ?>
                    <a class="btn btn--inverse btn--block" href="<?= e(url('pages/checkout.php')) ?>">Checkout</a>
                    <a class="btn btn--ghost-dark btn--block" href="<?= e(url('pages/shop.php')) ?>">Continue shopping</a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
