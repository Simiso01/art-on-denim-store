<?php
/**
 * Art on Denim — Wishlist
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$userId = (int) current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token. Please try again.');
        redirect('pages/wishlist.php');
    }

    $action = $_POST['action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($action === 'remove' && $productId > 0) {
        wishlist_remove($userId, $productId);
        flash('success', 'Item removed from your wishlist.');
    }

    if ($action === 'to_cart' && $productId > 0) {
        $size = trim((string) ($_POST['size'] ?? ''));
        $err = cart_add($productId, $size, 1);
        if ($err) {
            flash('error', $err);
        } else {
            wishlist_remove($userId, $productId);
            flash('success', 'Product successfully added to your cart.');
            redirect('pages/cart.php');
        }
    }

    redirect('pages/wishlist.php');
}

try {
    $items = get_wishlist_items($userId);
} catch (Throwable $e) {
    $items = [];
    error_log('AOD wishlist: ' . $e->getMessage());
}

$pageTitle = 'Wishlist';
$pageDescription = 'Your saved Art on Denim pieces.';
$bodyClass = 'page-wishlist';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section">
    <div class="container">
        <header class="section-header">
            <div>
                <p class="eyebrow">Saved</p>
                <h1>Wishlist</h1>
                <p><?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?></p>
            </div>
        </header>

        <?php if (!$items): ?>
            <div class="empty-state">
                <h2>Your wishlist is empty</h2>
                <p>Save pieces while you browse to revisit them later.</p>
                <a class="btn btn--inverse" href="<?= e(url('pages/shop.php')) ?>">Browse shop</a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($items as $product): ?>
                    <?php
                    $inventory = get_product_inventory((int) $product['id']);
                    ?>
                    <article class="wishlist-card">
                        <?php require dirname(__DIR__) . '/includes/product-card.php'; ?>

                        <div class="wishlist-card__actions">
                            <form method="post" action="<?= e(url('pages/wishlist.php')) ?>" class="wishlist-move">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="to_cart">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <label class="visually-hidden" for="size-<?= (int) $product['id'] ?>">Size</label>
                                <select id="size-<?= (int) $product['id'] ?>" name="size" required>
                                    <option value="">Select size</option>
                                    <?php foreach ($inventory as $row): ?>
                                        <?php $avail = max(0, (int) $row['quantity'] - (int) $row['reserved']); ?>
                                        <option value="<?= e($row['size']) ?>" <?= $avail < 1 ? 'disabled' : '' ?>>
                                            <?= e($row['size']) ?><?= $avail < 1 ? ' (out)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn--inverse">Move to cart</button>
                            </form>

                            <form method="post" action="<?= e(url('pages/wishlist.php')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <button type="submit" class="link-btn link-btn--danger">Remove</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
