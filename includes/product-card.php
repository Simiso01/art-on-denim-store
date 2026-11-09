<?php
/**
 * Art on Denim — Product card partial
 *
 * Expects: $product (array)
 */
declare(strict_types=1);

if (empty($product) || !is_array($product)) {
    return;
}

$name = $product['name'] ?? 'Product';
$brand = $product['brand_name'] ?? '';
$price = (float) ($product['price'] ?? 0);
$compare = isset($product['compare_at_price']) ? (float) $product['compare_at_price'] : null;
$image = product_image_url($product['image'] ?? null);
$id = (int) ($product['id'] ?? 0);
$onSale = !empty($product['is_on_sale']) && $compare && $compare > $price;
?>
<article class="product-card">
    <a class="product-card__media" href="<?= e(url('pages/product.php?id=' . $id)) ?>">
        <img
            src="<?= e($image) ?>"
            alt="<?= e($name) ?>"
            loading="lazy"
            width="600"
            height="750"
        >
        <?php if ($onSale): ?>
            <span class="product-card__badge">Sale</span>
        <?php elseif (!empty($product['is_new'])): ?>
            <span class="product-card__badge product-card__badge--new">New</span>
        <?php endif; ?>
    </a>

    <div class="product-card__body">
        <?php if ($brand !== ''): ?>
            <p class="product-card__brand"><?= e($brand) ?></p>
        <?php endif; ?>
        <h3 class="product-card__title">
            <a href="<?= e(url('pages/product.php?id=' . $id)) ?>"><?= e($name) ?></a>
        </h3>
        <p class="product-card__price">
            <span class="price"><?= e(money($price)) ?></span>
            <?php if ($onSale): ?>
                <span class="price price--compare"><?= e(money($compare)) ?></span>
            <?php endif; ?>
        </p>
    </div>
</article>
