<?php
/**
 * API â€” Orders
 *
 * GET                         list my orders (Bearer)
 * GET ?number=AOD-...         order details (Bearer or guest session)
 * GET ?action=track&number=&email=   public tracking
 * POST ?action=checkout       place order from cart (Bearer recommended)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$method = api_method();
$action = $_GET['action'] ?? '';
$body = api_json_body();

try {
    if ($method === 'GET' && $action === 'track') {
        $number = trim((string) ($_GET['number'] ?? $body['number'] ?? ''));
        $email = trim((string) ($_GET['email'] ?? $body['email'] ?? ''));

        if ($number === '') {
            api_error('Order number is required.', 422);
        }

        $order = get_order_by_number($number);
        if (!$order) {
            api_error('Order not found.', 404);
        }

        $user = api_optional_user();
        $allowed = false;
        if ($user && ((int) ($order['user_id'] ?? 0) === (int) $user['id'] || ($user['role'] ?? '') === 'admin')) {
            $allowed = true;
        } elseif ($email !== '' && strcasecmp($email, (string) $order['customer_email']) === 0) {
            $allowed = true;
            remember_guest_order($order['order_number']);
        } elseif (can_view_order($order)) {
            $allowed = true;
        }

        if (!$allowed) {
            api_error('Order number and email do not match.', 403);
        }

        $history = get_order_history((int) $order['id']);
        api_response(true, [
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'status_label' => order_status_label($order['status']),
            'payment_status' => $order['payment_status'],
            'tracking_number' => $order['tracking_number'],
            'tracking_carrier' => $order['tracking_carrier'],
            'placed_at' => $order['placed_at'],
            'timeline' => array_map(static function ($e) {
                return [
                    'status' => $e['status'],
                    'status_label' => order_status_label($e['status']),
                    'note' => $e['note'],
                    'at' => $e['created_at'],
                ];
            }, $history),
        ]);
    }

    if ($method === 'POST' && $action === 'checkout') {
        api_optional_user();
        $errors = validate_checkout($body);
        if ($errors !== []) {
            api_error('Validation failed.', 422, ['errors' => $errors]);
        }

        [$error, $orderId, $orderNumber] = place_order($body);
        if ($error) {
            api_error($error, 422);
        }

        if (!current_user() && $orderNumber) {
            remember_guest_order($orderNumber);
        }

        $order = get_order_by_number((string) $orderNumber);
        api_response(true, [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'status' => $order['status'] ?? 'pending',
            'payment_status' => $order['payment_status'] ?? 'pending',
            'total' => isset($order['total']) ? (float) $order['total'] : null,
            'currency' => CURRENCY_CODE,
        ], 'Your order has been successfully placed.', 201);
    }

    if ($method === 'GET') {
        $number = trim((string) ($_GET['number'] ?? ''));

        if ($number !== '') {
            $order = get_order_by_number($number);
            // Uniform 404 to avoid order-number enumeration
            if (!$order) {
                api_optional_user();
                api_error('Order not found.', 404);
            }

            api_optional_user();
            if (!can_view_order($order)) {
                api_error('Order not found.', 404);
            }

            $items = get_order_items((int) $order['id']);
            $history = get_order_history((int) $order['id']);
            api_response(true, [
                'order' => [
                    'id' => (int) $order['id'],
                    'order_number' => $order['order_number'],
                    'status' => $order['status'],
                    'status_label' => order_status_label($order['status']),
                    'payment_status' => $order['payment_status'],
                    'payment_method' => $order['payment_method'],
                    'subtotal' => (float) $order['subtotal'],
                    'shipping' => (float) $order['shipping_amount'],
                    'total' => (float) $order['total'],
                    'currency' => $order['currency'],
                    'placed_at' => $order['placed_at'],
                    'shipping_address' => [
                        'full_name' => $order['shipping_full_name'],
                        'line1' => $order['shipping_address_line1'],
                        'line2' => $order['shipping_address_line2'],
                        'city' => $order['shipping_city'],
                        'province' => $order['shipping_province'],
                        'postal_code' => $order['shipping_postal_code'],
                        'country' => $order['shipping_country'],
                        'phone' => $order['customer_phone'],
                        'email' => $order['customer_email'],
                    ],
                    'tracking_number' => $order['tracking_number'],
                    'tracking_carrier' => $order['tracking_carrier'],
                ],
                'items' => array_map(static function ($i) {
                    return [
                        'product_id' => $i['product_id'] ? (int) $i['product_id'] : null,
                        'name' => $i['product_name'],
                        'sku' => $i['product_sku'],
                        'size' => $i['size'],
                        'quantity' => (int) $i['quantity'],
                        'unit_price' => (float) $i['unit_price'],
                        'line_total' => (float) $i['line_total'],
                    ];
                }, $items),
                'timeline' => array_map(static function ($e) {
                    return [
                        'status' => $e['status'],
                        'status_label' => order_status_label($e['status']),
                        'note' => $e['note'],
                        'at' => $e['created_at'],
                    ];
                }, $history),
            ]);
        }

        $user = api_require_user();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = get_user_orders((int) $user['id'], $page);
        api_response(true, [
            'items' => array_map(static function ($o) {
                return [
                    'id' => (int) $o['id'],
                    'order_number' => $o['order_number'],
                    'status' => $o['status'],
                    'status_label' => order_status_label($o['status']),
                    'payment_status' => $o['payment_status'],
                    'total' => (float) $o['total'],
                    'currency' => $o['currency'],
                    'placed_at' => $o['placed_at'],
                ];
            }, $result['items']),
            'pagination' => [
                'page' => $result['page'],
                'total' => $result['total'],
                'total_pages' => $result['total_pages'],
            ],
        ]);
    }

    api_error('Method not allowed.', 405);
} catch (Throwable $e) {
    error_log('AOD API orders: ' . $e->getMessage());
    api_error('Server error.', 500);
}
