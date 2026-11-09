<?php
/**
 * Admin sidebar navigation
 */
declare(strict_types=1);

$adminNav = [
    ['Dashboard', 'admin/index.php', '/admin/index.php'],
    ['Products', 'admin/products.php', '/admin/products'],
    ['Add Product', 'admin/add-product.php', '/admin/add-product'],
    ['Categories', 'admin/categories.php', '/admin/categories'],
    ['Brands', 'admin/brands.php', '/admin/brands'],
    ['Inventory', 'admin/inventory.php', '/admin/inventory'],
    ['Orders', 'admin/orders.php', '/admin/orders'],
    ['Customers', 'admin/customers.php', '/admin/customers'],
    ['Promotions', 'admin/promotions.php', '/admin/promotions'],
    ['Reports', 'admin/reports.php', '/admin/reports'],
    ['Journal', 'admin/journal.php', '/admin/journal'],
    ['Messages', 'admin/messages.php', '/admin/messages'],
    ['Settings', 'admin/settings.php', '/admin/settings'],
];
?>
<aside class="admin-sidebar" aria-label="Admin navigation">
    <div class="admin-sidebar__brand">
        <span class="admin-sidebar__mark">AOD</span>
        <span class="admin-sidebar__label">Admin</span>
    </div>
    <button type="button" class="admin-nav-toggle" id="admin-nav-toggle" aria-expanded="false" aria-controls="admin-nav">
        Menu
    </button>
    <nav id="admin-nav" class="admin-nav">
        <ul>
            <?php foreach ($adminNav as $item): ?>
                <li>
                    <a class="<?= e(is_active($item[2])) ?>" href="<?= e(url($item[1])) ?>">
                        <?= e($item[0]) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
