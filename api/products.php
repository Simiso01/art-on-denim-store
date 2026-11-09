<?php
/**
 * API â€” Products
 *
 * GET                  list (filters: q, category, brand, sort, page, new, sale, collection)
 * GET ?id=123          product detail
 * GET ?action=search&q= search alias
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method not allowed.', 405);
}

try {
    $id = (int) ($_GET['id'] ?? 0);
    $action = $_GET['action'] ?? '';

    if ($action === 'meta') {
        $categories = array_map(static function ($row) {
            return ['name' => $row['name'], 'slug' => $row['slug']];
        }, get_categories());
        $brands = array_map(static function ($row) {
            return ['name' => $row['name'], 'slug' => $row['slug']];
        }, get_brands_list());
        api_response(true, [
            'categories' => $categories,
            'brands' => $brands,
            'provinces' => sa_provinces(),
            'shipping' => DEFAULT_SHIPPING,
            'free_shipping_threshold' => FREE_SHIPPING_THRESHOLD,
            'google' => google_is_configured(),
            'sizes' => catalogue_sizes(),
        ]);
    }

    if ($id > 0 || $action === 'detail') {
        $productId = $id ?: (int) ($_GET['product_id'] ?? 0);
        $product = get_product_by_id($productId);
        if (!$product) {
            api_error('Product not found.', 404);
        }
        // attach primary image
        $images = get_product_images($productId);
        $product['image'] = $images[0]['image_path'] ?? null;
        $detail = api_format_product($product, true);
        $detail['related'] = array_map(static function ($row) {
            return api_format_product($row, false);
        }, related_products($product, 4));
        api_response(true, $detail);
    }

    $filters = [
        'q'          => trim((string) ($_GET['q'] ?? '')),
        'category'   => trim((string) ($_GET['category'] ?? '')),
        'brand'      => trim((string) ($_GET['brand'] ?? '')),
        'sort'       => trim((string) ($_GET['sort'] ?? 'newest')),
        'gender'     => trim((string) ($_GET['gender'] ?? '')),
        'collection' => trim((string) ($_GET['collection'] ?? '')),
        'is_new'     => !empty($_GET['new']),
        'is_on_sale' => !empty($_GET['sale']),
        'min_price'  => trim((string) ($_GET['min_price'] ?? '')),
        'max_price'  => trim((string) ($_GET['max_price'] ?? '')),
        'size'       => trim((string) ($_GET['size'] ?? '')),
    ];

    if ($action === 'search' && $filters['q'] === '') {
        api_error('Search query `q` is required.', 422);
    }

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(48, max(1, (int) ($_GET['per_page'] ?? PRODUCTS_PER_PAGE)));
    $result = query_products($filters, $page, $perPage);

    $items = array_map(static function ($p) {
        return api_format_product($p, false);
    }, $result['items']);

    api_response(true, [
        'items'       => $items,
        'pagination'  => [
            'page'        => $result['page'],
            'per_page'    => $result['per_page'],
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
        ],
        'filters'     => $filters,
    ]);
} catch (Throwable $e) {
    error_log('AOD API products: ' . $e->getMessage());
    api_error('Server error.', 500);
}
