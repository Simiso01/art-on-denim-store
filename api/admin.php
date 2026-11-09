<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method not allowed.', 405);
}

$user = api_require_user();
if (($user['role'] ?? '') !== 'admin') {
    api_error('This area is for the Art on Denim studio.', 403);
}

$action = $_GET['action'] ?? 'dashboard';

try {
    if ($action === 'dashboard') {
        $stats = admin_dashboard_stats();
        api_response(true, [
            'stats' => $stats,
            'orders' => array_map(static function ($order) {
                return [
                    'order_number' => $order['order_number'],
                    'email' => $order['customer_email'],
                    'status' => $order['status'],
                    'total' => (float) $order['total'],
                ];
            }, admin_recent_orders(8)),
            'low_stock' => array_map(static function ($row) {
                return [
                    'name' => $row['product_name'],
                    'sku' => $row['sku'],
                    'size' => $row['size'],
                    'quantity' => (int) $row['quantity'],
                ];
            }, admin_low_stock(8)),
        ]);
    }

    if ($action === 'customers') {
        $rows = db()->query("SELECT first_name, last_name, email, city FROM users WHERE role = 'customer' ORDER BY id DESC LIMIT 30")->fetchAll();
        api_response(true, ['items' => $rows]);
    }

    if ($action === 'messages') {
        $rows = db()->query('SELECT name, email, subject, status, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 30')->fetchAll();
        api_response(true, ['items' => $rows]);
    }

    api_error('Unknown admin action.', 404);
} catch (Throwable $e) {
    error_log('AOD API admin: ' . $e->getMessage());
    api_error('Server error.', 500);
}
