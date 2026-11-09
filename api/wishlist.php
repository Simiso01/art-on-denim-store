<?php
/**
 * API â€” Wishlist (authenticated)
 *
 * GET                list
 * POST               add {product_id}
 * DELETE             remove {product_id}
 * POST ?action=to_cart  move to cart {product_id, size}
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = api_require_user();
$userId = (int) $user['id'];
$method = api_method();
$action = $_GET['action'] ?? '';
$body = api_json_body();

try {
    if ($method === 'GET') {
        $items = get_wishlist_items($userId);
        $formatted = array_map(static function ($p) {
            return api_format_product($p, false);
        }, $items);
        api_response(true, ['items' => $formatted, 'count' => count($formatted)]);
    }

    if ($method === 'POST' && $action === 'to_cart') {
        $productId = (int) ($body['product_id'] ?? 0);
        $size = trim((string) ($body['size'] ?? ''));
        $err = cart_add($productId, $size, 1);
        if ($err) {
            api_error($err, 422);
        }
        wishlist_remove($userId, $productId);
        api_response(true, ['count' => cart_count()], 'Product successfully added to your cart.');
    }

    if ($method === 'POST') {
        $productId = (int) ($body['product_id'] ?? 0);
        $err = wishlist_add($userId, $productId);
        if ($err) {
            api_error($err, 422);
        }
        api_response(true, null, 'Product saved to your wishlist.');
    }

    if ($method === 'DELETE') {
        $productId = (int) ($body['product_id'] ?? $_GET['product_id'] ?? 0);
        if ($productId < 1) {
            api_error('product_id is required.', 422);
        }
        wishlist_remove($userId, $productId);
        api_response(true, null, 'Item removed from your wishlist.');
    }

    api_error('Method not allowed.', 405);
} catch (Throwable $e) {
    error_log('AOD API wishlist: ' . $e->getMessage());
    api_error('Server error.', 500);
}
