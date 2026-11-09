<?php
/**
 * API â€” Cart
 *
 * GET                 view cart
 * POST                add item {product_id, size, quantity}
 * PUT / PATCH         update item {item_id, quantity}
 * DELETE              remove item {item_id} or clear=1
 *
 * Guest carts use PHP session; authenticated carts use user_id.
 * Send Bearer token when logged in. Optionally send X-AOD-Session for guests.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$method = api_method();
$body = api_json_body();
api_optional_user();

try {
    if ($method === 'GET') {
        $totals = cart_totals();
        $items = array_map(static function ($item) {
            return [
                'id'         => (int) $item['id'],
                'product_id' => (int) $item['product_id'],
                'name'       => $item['name'],
                'brand'      => $item['brand_name'] ?? null,
                'sku'        => $item['sku'],
                'size'       => $item['size'],
                'quantity'   => (int) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'line_total' => (float) $item['unit_price'] * (int) $item['quantity'],
                'image'      => api_image_url($item['image'] ?? null),
            ];
        }, $totals['items']);

        api_response(true, [
            'items'    => $items,
            'count'    => cart_count(),
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'] ?? 0,
            'coupon'   => $totals['coupon']['coupon_code'] ?? null,
            'shipping' => $totals['shipping'],
            'total'    => $totals['total'],
            'currency' => CURRENCY_CODE,
            'free_shipping_threshold' => FREE_SHIPPING_THRESHOLD,
        ]);
    }

    if ($method === 'POST' && (($_GET['action'] ?? '') === 'coupon')) {
        $message = apply_coupon_code((string) ($body['coupon_code'] ?? ''));
        if ($message !== '') {
            api_error($message, 422);
        }
        api_response(true, ['coupon' => $_SESSION['coupon_code'] ?? null], 'Coupon applied.');
    }

    if ($method === 'POST' && (($_GET['action'] ?? '') === 'coupon_remove')) {
        clear_coupon_code();
        api_response(true, ['coupon' => null], 'Coupon removed.');
    }

    if ($method === 'POST') {
        $productId = (int) ($body['product_id'] ?? 0);
        $size = trim((string) ($body['size'] ?? ''));
        $qty = max(1, (int) ($body['quantity'] ?? 1));
        $err = cart_add($productId, $size, $qty);
        if ($err) {
            api_error($err, 422);
        }
        api_response(true, ['count' => cart_count()], 'Product successfully added to your cart.');
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $itemId = (int) ($body['item_id'] ?? 0);
        $qty = (int) ($body['quantity'] ?? 1);
        $err = cart_update($itemId, $qty);
        if ($err) {
            api_error($err, 422);
        }
        api_response(true, ['count' => cart_count()], 'Cart updated.');
    }

    if ($method === 'DELETE') {
        if (!empty($body['clear']) || !empty($_GET['clear'])) {
            cart_clear();
            api_response(true, ['count' => 0], 'Cart cleared.');
        }
        $itemId = (int) ($body['item_id'] ?? $_GET['item_id'] ?? 0);
        if ($itemId < 1) {
            api_error('item_id is required.', 422);
        }
        cart_remove($itemId);
        api_response(true, ['count' => cart_count()], 'Item removed from your cart.');
    }

    api_error('Method not allowed.', 405);
} catch (Throwable $e) {
    error_log('AOD API cart: ' . $e->getMessage());
    api_error('Server error.', 500);
}
