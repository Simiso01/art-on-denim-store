<?php
/**
 * Admin â€” Edit product
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    flash('error', 'Product not found.');
    redirect('admin/products.php');
}

$categories = get_categories(false);
$brands = get_brands_list(false);
$inventory = get_product_inventory($id);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors['form'] = 'Invalid security token.';
    } else {
        $errors = admin_validate_product($_POST, true, $id);
        $imagePath = '';
        if (!empty($_FILES['image']['name'])) {
            $upload = admin_upload_product_image($_FILES['image']);
            if (strpos($upload, 'ERROR:') === 0) {
                $errors['image'] = substr($upload, 6);
            } else {
                $imagePath = $upload;
            }
        }

        if ($errors === []) {
            $name = trim((string) $_POST['name']);
            $upd = db()->prepare(
                'UPDATE products SET
                    sku = :sku, name = :name, description = :description, short_description = :short_description,
                    category_id = :category_id, brand_id = :brand_id, price = :price, compare_at_price = :compare_at_price,
                    gender = :gender, is_featured = :is_featured, is_new = :is_new, is_on_sale = :is_on_sale,
                    is_aod_collection = :is_aod_collection, status = :status
                 WHERE id = :id'
            );
            $upd->execute([
                ':sku'               => trim((string) $_POST['sku']),
                ':name'              => $name,
                ':description'       => trim((string) ($_POST['description'] ?? '')) ?: null,
                ':short_description' => trim((string) ($_POST['short_description'] ?? '')) ?: null,
                ':category_id'       => (int) ($_POST['category_id'] ?? 0) ?: null,
                ':brand_id'          => (int) ($_POST['brand_id'] ?? 0) ?: null,
                ':price'             => (float) $_POST['price'],
                ':compare_at_price'  => ($_POST['compare_at_price'] ?? '') !== '' ? (float) $_POST['compare_at_price'] : null,
                ':gender'            => in_array($_POST['gender'] ?? '', ['unisex','men','women'], true) ? $_POST['gender'] : 'unisex',
                ':is_featured'       => !empty($_POST['is_featured']) ? 1 : 0,
                ':is_new'            => !empty($_POST['is_new']) ? 1 : 0,
                ':is_on_sale'        => !empty($_POST['is_on_sale']) ? 1 : 0,
                ':is_aod_collection' => !empty($_POST['is_aod_collection']) ? 1 : 0,
                ':status'            => in_array($_POST['status'] ?? '', ['draft','active','archived'], true) ? $_POST['status'] : 'active',
                ':id'                => $id,
            ]);

            // Replace inventory rows for submitted sizes
            db()->prepare('DELETE FROM inventory WHERE product_id = :id')->execute([':id' => $id]);
            admin_save_product_inventory($id, $_POST['sizes'] ?? [], $_POST['quantities'] ?? []);

            if ($imagePath !== '') {
                db()->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = :id')->execute([':id' => $id]);
                $img = db()->prepare(
                    'INSERT INTO product_images (product_id, image_path, alt_text, is_primary)
                     VALUES (:pid, :path, :alt, 1)'
                );
                $img->execute([':pid' => $id, ':path' => $imagePath, ':alt' => $name]);
            }

            flash('success', 'Product updated.');
            redirect('admin/edit-product.php?id=' . $id);
        }
        $product = array_merge($product, $_POST);
        $inventory = [];
        foreach (($_POST['sizes'] ?? []) as $i => $size) {
            $inventory[] = ['size' => $size, 'quantity' => $_POST['quantities'][$i] ?? 0];
        }
    }
}

$pageTitle = 'Edit Product';
require dirname(__DIR__) . '/includes/admin-header.php';
?>

<section class="admin-panel">
    <form method="post" action="<?= e(url('admin/edit-product.php?id=' . $id)) ?>" enctype="multipart/form-data" class="admin-form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php if (!empty($errors['form'])): ?><p class="field-error"><?= e($errors['form']) ?></p><?php endif; ?>

        <div class="admin-form-row">
            <div class="admin-field">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required value="<?= e((string) $product['name']) ?>">
                <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>
            <div class="admin-field">
                <label for="sku">SKU</label>
                <input type="text" id="sku" name="sku" required value="<?= e((string) $product['sku']) ?>">
                <?php if (!empty($errors['sku'])): ?><p class="field-error"><?= e($errors['sku']) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="admin-form-row">
            <div class="admin-field">
                <label for="price">Price (ZAR)</label>
                <input type="number" step="0.01" min="0" id="price" name="price" required value="<?= e((string) $product['price']) ?>">
                <?php if (!empty($errors['price'])): ?><p class="field-error"><?= e($errors['price']) ?></p><?php endif; ?>
            </div>
            <div class="admin-field">
                <label for="compare_at_price">Compare-at price</label>
                <input type="number" step="0.01" min="0" id="compare_at_price" name="compare_at_price" value="<?= e((string) ($product['compare_at_price'] ?? '')) ?>">
            </div>
        </div>

        <div class="admin-form-row">
            <div class="admin-field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">â€”</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-field">
                <label for="brand_id">Brand</label>
                <select id="brand_id" name="brand_id">
                    <option value="">â€”</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= (int) $b['id'] ?>" <?= (int) ($product['brand_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="admin-form-row">
            <div class="admin-field">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <?php foreach (['unisex','men','women'] as $g): ?>
                        <option value="<?= $g ?>" <?= ($product['gender'] ?? '') === $g ? 'selected' : '' ?>><?= ucfirst($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (['active','draft','archived'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($product['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="admin-field">
            <label for="short_description">Short description</label>
            <input type="text" id="short_description" name="short_description" maxlength="500" value="<?= e((string) ($product['short_description'] ?? '')) ?>">
        </div>
        <div class="admin-field">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= e((string) ($product['description'] ?? '')) ?></textarea>
        </div>

        <div class="admin-checks">
            <label><input type="checkbox" name="is_featured" value="1" <?= !empty($product['is_featured']) ? 'checked' : '' ?>> Featured</label>
            <label><input type="checkbox" name="is_new" value="1" <?= !empty($product['is_new']) ? 'checked' : '' ?>> New</label>
            <label><input type="checkbox" name="is_on_sale" value="1" <?= !empty($product['is_on_sale']) ? 'checked' : '' ?>> On sale</label>
            <label><input type="checkbox" name="is_aod_collection" value="1" <?= !empty($product['is_aod_collection']) ? 'checked' : '' ?>> AOD collection</label>
        </div>

        <div class="admin-field">
            <label for="image">Replace primary image</label>
            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <?php if (!empty($errors['image'])): ?><p class="field-error"><?= e($errors['image']) ?></p><?php endif; ?>
        </div>

        <div>
            <h2 style="font-size:1rem;">Inventory</h2>
            <div class="inventory-editor" id="inventory-editor">
                <?php if ($inventory): ?>
                    <?php foreach ($inventory as $row): ?>
                        <div class="inventory-row">
                            <div class="admin-field"><label>Size</label><input type="text" name="sizes[]" value="<?= e($row['size']) ?>" required></div>
                            <div class="admin-field"><label>Qty</label><input type="number" name="quantities[]" min="0" value="<?= (int) $row['quantity'] ?>" required></div>
                            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-remove-row>Remove</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="inventory-row">
                        <div class="admin-field"><label>Size</label><input type="text" name="sizes[]" required></div>
                        <div class="admin-field"><label>Qty</label><input type="number" name="quantities[]" min="0" value="0" required></div>
                        <span></span>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" id="add-size-row" style="margin-top:.75rem;">Add size</button>
        </div>

        <div class="stack-actions">
            <button type="submit" class="admin-btn">Save changes</button>
            <a class="admin-btn admin-btn--ghost" href="<?= e(url('admin/products.php')) ?>">Back</a>
            <a class="admin-btn admin-btn--ghost" href="<?= e(url('pages/product.php?id=' . $id)) ?>" target="_blank" rel="noopener">View on store</a>
        </div>
    </form>
</section>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
