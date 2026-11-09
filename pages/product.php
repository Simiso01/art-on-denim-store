<?php
/**
 * Art on Denim — Product details
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$product = null;
$images = [];
$inventory = [];
$inWishlist = false;
$related = [];

try {
    if ($id > 0) {
        $product = get_product_by_id($id);
        if ($product) {
            $images = get_product_images($id);
            $inventory = get_product_inventory($id);
            $related = related_products($product, 4);
            if (is_logged_in()) {
                $inWishlist = wishlist_has((int) current_user()['id'], $id);
            }
        }
    }
} catch (Throwable $e) {
    error_log('AOD product error: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    if (!csrf_verify()) {
        flash('error', 'Invalid security token. Please try again.');
        redirect('pages/product.php?id=' . $id);
    }

    $action = $_POST['action'] ?? 'cart';

    if ($action === 'wishlist') {
        if (!is_logged_in()) {
            flash('error', 'Please sign in to save items to your wishlist.');
            $_SESSION['redirect_after_login'] = url('pages/product.php?id=' . $id);
            redirect('pages/login.php');
        }
        $err = wishlist_add((int) current_user()['id'], $id);
        flash($err ? 'error' : 'success', $err ?: 'Product saved to your wishlist.');
        redirect('pages/product.php?id=' . $id);
    }

    if ($action === 'cart') {
        $size = trim((string) ($_POST['size'] ?? ''));
        $qty = max(1, (int) ($_POST['quantity'] ?? 1));
        $err = cart_add($id, $size, $qty);
        if ($err) {
            flash('error', $err);
        } else {
            flash('success', 'Product successfully added to your cart.');
        }
        redirect('pages/product.php?id=' . $id);
    }
}

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    $pageDescription = 'The requested product could not be found.';
    require dirname(__DIR__) . '/includes/header.php';
    require dirname(__DIR__) . '/includes/navigation.php';
    ?>
    <main id="main-content" class="section">
        <div class="container empty-state">
            <h1>Product not found</h1>
            <p>This piece may have been removed or is unavailable.</p>
            <a class="btn btn--inverse" href="<?= e(url('pages/shop.php')) ?>">Back to shop</a>
        </div>
    </main>
    <?php
    require dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$primaryImage = $images[0]['image_path'] ?? null;
$compare = isset($product['compare_at_price']) ? (float) $product['compare_at_price'] : null;
$price = (float) $product['price'];
$onSale = !empty($product['is_on_sale']) && $compare && $compare > $price;
$totalStock = 0;
foreach ($inventory as $row) {
    $totalStock += max(0, (int) $row['quantity'] - (int) $row['reserved']);
}

$pageTitle = $product['name'];
$pageDescription = $product['short_description'] ?: ($product['meta_description'] ?: $product['name']);
$bodyClass = 'page-product';

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/navigation.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>

<main id="main-content" class="section product-section">
    <div class="container product-layout">
        <div class="product-gallery">
            <div class="product-gallery__main">
                <img
                    id="product-main-image"
                    src="<?= e(product_image_url($primaryImage)) ?>"
                    alt="<?= e($product['name']) ?>"
                    width="800"
                    height="1000"
                >
            </div>
            <?php if (count($images) > 1): ?>
                <ul class="product-gallery__thumbs">
                    <?php foreach ($images as $index => $img): ?>
                        <li>
                            <button
                                type="button"
                                class="product-thumb <?= $index === 0 ? 'is-active' : '' ?>"
                                data-full="<?= e(product_image_url($img['image_path'])) ?>"
                                aria-label="View image <?= $index + 1 ?>"
                            >
                                <img src="<?= e(product_image_url($img['image_path'])) ?>" alt="<?= e($img['alt_text'] ?: $product['name']) ?>">
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="product-info">
            <p class="eyebrow">
                <?php if (!empty($product['brand_name'])): ?>
                    <a href="<?= e(url('pages/brands.php?slug=' . urlencode($product['brand_slug']))) ?>">
                        <?= e($product['brand_name']) ?>
                    </a>
                <?php else: ?>
                    Art on Denim
                <?php endif; ?>
            </p>

            <h1><?= e($product['name']) ?></h1>

            <p class="product-info__price">
                <span class="price"><?= e(money($price)) ?></span>
                <?php if ($onSale): ?>
                    <span class="price price--compare"><?= e(money($compare)) ?></span>
                    <span class="product-info__badge">Sale</span>
                <?php endif; ?>
            </p>

            <?php if (!empty($product['short_description'])): ?>
                <p class="product-info__summary"><?= e($product['short_description']) ?></p>
            <?php endif; ?>

            <?php if ($totalStock < 1): ?>
                <p class="stock-note stock-note--out" role="status">Currently out of stock</p>
            <?php elseif ($totalStock <= LOW_STOCK_THRESHOLD): ?>
                <p class="stock-note" role="status">Low stock — limited sizes remaining</p>
            <?php endif; ?>

            <form class="product-form" method="post" action="<?= e(url('pages/product.php?id=' . $id)) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cart">

                <fieldset class="size-fieldset">
                    <legend>Select size</legend>
                    <div class="size-options" role="radiogroup" aria-label="Available sizes">
                        <?php foreach ($inventory as $row): ?>
                            <?php
                            $avail = max(0, (int) $row['quantity'] - (int) $row['reserved']);
                            $disabled = $avail < 1;
                            ?>
                            <label class="size-option <?= $disabled ? 'is-disabled' : '' ?>">
                                <input
                                    type="radio"
                                    name="size"
                                    value="<?= e($row['size']) ?>"
                                    <?= $disabled ? 'disabled' : '' ?>
                                    required
                                >
                                <span><?= e($row['size']) ?><?php if (!$disabled && $avail <= LOW_STOCK_THRESHOLD): ?><small><?= (int) $avail ?> left</small><?php endif; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="qty-row">
                    <label for="quantity">Quantity</label>
                    <div class="qty-control" data-qty>
                        <button type="button" class="qty-btn" data-qty-minus aria-label="Decrease quantity">−</button>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= max(1, $totalStock) ?>" required>
                        <button type="button" class="qty-btn" data-qty-plus aria-label="Increase quantity">+</button>
                    </div>
                </div>

                <div class="product-actions">
                    <button type="submit" class="btn btn--inverse" <?= $totalStock < 1 ? 'disabled' : '' ?>>
                        Add to cart
                    </button>
                </div>
            </form>

            <form method="post" action="<?= e(url('pages/product.php?id=' . $id)) ?>" class="wishlist-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="wishlist">
                <button type="submit" class="btn btn--ghost-dark">
                    <?= $inWishlist ? 'Saved to wishlist' : 'Add to wishlist' ?>
                </button>
            </form>

            <?php if (!empty($product['description'])): ?>
                <section class="product-description" aria-labelledby="details-heading">
                    <h2 id="details-heading">Details</h2>
                    <p><?= nl2br(e($product['description'])) ?></p>
                    <p class="meta-line">SKU: <?= e($product['sku']) ?></p>
                    <?php if (!empty($product['category_name'])): ?>
                        <p class="meta-line">
                            Category:
                            <a href="<?= e(url('pages/shop.php?category=' . urlencode($product['category_slug']))) ?>">
                                <?= e($product['category_name']) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($related): ?>
        <section class="section related-section" aria-labelledby="related-heading">
            <div class="container">
                <div class="section-header">
                    <div>
                        <h2 id="related-heading">Also in this edit</h2>
                        <p>Pieces from the same house or the same category.</p>
                    </div>
                </div>
                <div class="product-grid">
                    <?php foreach ($related as $product): ?>
                        <?php require dirname(__DIR__) . '/includes/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
