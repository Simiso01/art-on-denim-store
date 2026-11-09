<?php
/**
 * Art on Denim — Orders & checkout helpers
 */

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Generate unique order number: AOD-2026-000001
 */
function generate_order_number(PDO $pdo): string
{
    $year = date('Y');
    $prefix = ORDER_PREFIX . '-' . $year . '-';

    $stmt = $pdo->prepare(
        "SELECT order_number FROM orders
         WHERE order_number LIKE :prefix
         ORDER BY id DESC
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->execute([':prefix' => $prefix . '%']);
    $last = $stmt->fetchColumn();

    $next = 1;
    if ($last && preg_match('/(\d+)$/', (string) $last, $m)) {
        $next = (int) $m[1] + 1;
    }

    return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
}

/**
 * SA provinces for checkout select.
 *
 * @return array<int, string>
 */
function sa_provinces(): array
{
    return [
        'Eastern Cape',
        'Free State',
        'Gauteng',
        'KwaZulu-Natal',
        'Limpopo',
        'Mpumalanga',
        'Northern Cape',
        'North West',
        'Western Cape',
    ];
}

/**
 * Validate checkout payload.
 *
 * @param array<string, mixed> $data
 * @return array<string, string>
 */
function validate_checkout(array $data): array
{
    $errors = [];

    $fullName = trim((string) ($data['full_name'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $phone = trim((string) ($data['phone'] ?? ''));
    $line1 = trim((string) ($data['address_line1'] ?? ''));
    $city = trim((string) ($data['city'] ?? ''));
    $province = trim((string) ($data['province'] ?? ''));
    $postal = trim((string) ($data['postal_code'] ?? ''));
    $method = trim((string) ($data['payment_method'] ?? ''));

    if ($fullName === '' || mb_strlen($fullName) > 160) {
        $errors['full_name'] = 'Please enter your full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email address.';
    }
    if ($phone === '' || !preg_match('/^[0-9+\-\s()]{7,30}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    if ($line1 === '' || mb_strlen($line1) > 255) {
        $errors['address_line1'] = 'Please enter your street address.';
    }
    if ($city === '' || mb_strlen($city) > 100) {
        $errors['city'] = 'Please enter your city.';
    }
    if (!in_array($province, sa_provinces(), true)) {
        $errors['province'] = 'Please select a valid province.';
    }
    if ($postal === '' || !preg_match('/^[0-9A-Za-z\-\s]{3,20}$/', $postal)) {
        $errors['postal_code'] = 'Please enter a valid postal code.';
    }
    if (!in_array($method, ['payfast', 'eft', 'card'], true)) {
        $errors['payment_method'] = 'Please select a payment method.';
    }

    return $errors;
}

/**
 * Place order from current cart. Returns [error|null, orderId|null, orderNumber|null]
 *
 * @param array<string, mixed> $data
 * @return array{0: ?string, 1: ?int, 2: ?string}
 */
function place_order(array $data): array
{
    $pdo = db();
    $totals = cart_totals();
    $items = $totals['items'];

    if ($items === []) {
        return ['Your cart is empty.', null, null];
    }

    // Re-validate stock server-side
    foreach ($items as $item) {
        $available = get_available_stock((int) $item['product_id'], (string) $item['size']);
        if ((int) $item['quantity'] > $available) {
            return [
                'Your requested quantity for "' . $item['name'] . '" (' . $item['size'] . ') is not currently available.',
                null,
                null,
            ];
        }
        if (($item['status'] ?? '') !== 'active') {
            return ['One or more products in your cart are no longer available.', null, null];
        }
    }

    $user = current_user();
    $method = (string) $data['payment_method'];

    // Simulated gateway result for local/dev architecture
    // payfast/card: mark pending payment (awaiting gateway)
    // eft: pending until admin confirms
    $paymentStatus = 'pending';
    $orderStatus = 'pending';

    // Dev/demo: treat "card" as successful mock charge (never stores card data)
    if ($method === 'card' && APP_ENV === 'development') {
        $paymentStatus = 'paid';
        $orderStatus = 'paid';
    }

    try {
        $pdo->beginTransaction();

        $orderNumber = generate_order_number($pdo);

        $stmt = $pdo->prepare(
            'INSERT INTO orders (
                order_number, user_id, status, payment_status, payment_method,
                subtotal, shipping_amount, discount_amount, coupon_code, tax_amount, total, currency,
                customer_email, customer_phone, shipping_full_name,
                shipping_address_line1, shipping_address_line2, shipping_city,
                shipping_province, shipping_postal_code, shipping_country, notes
             ) VALUES (
                :order_number, :user_id, :status, :payment_status, :payment_method,
                :subtotal, :shipping_amount, :discount_amount, :coupon_code, :tax_amount, :total, :currency,
                :customer_email, :customer_phone, :shipping_full_name,
                :shipping_address_line1, :shipping_address_line2, :shipping_city,
                :shipping_province, :shipping_postal_code, :shipping_country, :notes
             )'
        );

        $stmt->execute([
            ':order_number'           => $orderNumber,
            ':user_id'                => $user ? (int) $user['id'] : null,
            ':status'                 => $orderStatus,
            ':payment_status'         => $paymentStatus,
            ':payment_method'         => $method,
            ':subtotal'               => $totals['subtotal'],
            ':shipping_amount'        => $totals['shipping'],
            ':discount_amount'        => $totals['discount'] ?? 0,
            ':coupon_code'            => $totals['coupon']['coupon_code'] ?? null,
            ':tax_amount'             => 0,
            ':total'                  => $totals['total'],
            ':currency'               => CURRENCY_CODE,
            ':customer_email'         => strtolower(trim((string) $data['email'])),
            ':customer_phone'         => trim((string) $data['phone']),
            ':shipping_full_name'     => trim((string) $data['full_name']),
            ':shipping_address_line1' => trim((string) $data['address_line1']),
            ':shipping_address_line2' => trim((string) ($data['address_line2'] ?? '')) ?: null,
            ':shipping_city'          => trim((string) $data['city']),
            ':shipping_province'      => trim((string) $data['province']),
            ':shipping_postal_code'    => trim((string) $data['postal_code']),
            ':shipping_country'       => 'South Africa',
            ':notes'                  => trim((string) ($data['notes'] ?? '')) ?: null,
        ]);

        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (
                order_id, product_id, product_name, product_sku, size, quantity, unit_price, line_total
             ) VALUES (
                :order_id, :product_id, :product_name, :product_sku, :size, :quantity, :unit_price, :line_total
             )'
        );

        $stockStmt = $pdo->prepare(
            'UPDATE inventory
             SET quantity = quantity - :qty
             WHERE product_id = :pid AND size = :size AND (quantity - reserved) >= :qty2'
        );

        foreach ($items as $item) {
            $qty = (int) $item['quantity'];
            $unit = (float) $item['unit_price'];
            $line = $unit * $qty;

            $itemStmt->execute([
                ':order_id'     => $orderId,
                ':product_id'   => (int) $item['product_id'],
                ':product_name' => $item['name'],
                ':product_sku'  => $item['sku'],
                ':size'         => $item['size'],
                ':quantity'     => $qty,
                ':unit_price'   => $unit,
                ':line_total'   => $line,
            ]);

            $stockStmt->execute([
                ':qty'  => $qty,
                ':pid'  => (int) $item['product_id'],
                ':size' => $item['size'],
                ':qty2' => $qty,
            ]);

            if ($stockStmt->rowCount() < 1) {
                throw new RuntimeException('Stock update failed for ' . $item['name']);
            }
        }

        // Payment record (never stores raw card data)
        $payStmt = $pdo->prepare(
            'INSERT INTO payments (order_id, provider, provider_ref, amount, currency, status, paid_at)
             VALUES (:order_id, :provider, :provider_ref, :amount, :currency, :status, :paid_at)'
        );

        $provider = $method === 'eft' ? 'eft' : ($method === 'card' ? 'card_mock' : 'payfast');
        $payStmt->execute([
            ':order_id'     => $orderId,
            ':provider'     => $provider,
            ':provider_ref' => $orderNumber . '-' . strtoupper(bin2hex(random_bytes(3))),
            ':amount'       => $totals['total'],
            ':currency'     => CURRENCY_CODE,
            ':status'       => $paymentStatus === 'paid' ? 'completed' : 'pending',
            ':paid_at'      => $paymentStatus === 'paid' ? date('Y-m-d H:i:s') : null,
        ]);

        // Status history
        $hist = $pdo->prepare(
            'INSERT INTO order_status_history (order_id, status, note, created_by)
             VALUES (:oid, :status, :note, :uid)'
        );
        $hist->execute([
            ':oid'    => $orderId,
            ':status' => $orderStatus,
            ':note'   => 'Order placed',
            ':uid'    => $user ? (int) $user['id'] : null,
        ]);

        // Notification for logged-in users
        if ($user) {
            $note = $pdo->prepare(
                'INSERT INTO notifications (user_id, type, title, message, link)
                 VALUES (:uid, :type, :title, :message, :link)'
            );
            $note->execute([
                ':uid'     => (int) $user['id'],
                ':type'    => 'order',
                ':title'   => 'Order placed',
                ':message' => 'Your order ' . $orderNumber . ' has been placed successfully.',
                ':link'    => 'pages/order-details.php?number=' . urlencode($orderNumber),
            ]);
        }

        // Clear cart items
        $cartId = get_or_create_cart();
        $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cid')->execute([':cid' => $cartId]);

        $pdo->commit();
        clear_coupon_code();
        refresh_cart_count();

        return [null, $orderId, $orderNumber];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('AOD place_order: ' . $e->getMessage());
        return ['We could not complete your order. Please try again.', null, null];
    }
}

/**
 * @return array<string, mixed>|null
 */
function get_order_by_number(string $orderNumber): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE order_number = :num LIMIT 1');
    $stmt->execute([':num' => $orderNumber]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * @return array<string, mixed>|null
 */
function get_order_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_order_items(int $orderId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM order_items WHERE order_id = :oid ORDER BY id ASC'
    );
    $stmt->execute([':oid' => $orderId]);
    return $stmt->fetchAll();
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_order_history(int $orderId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM order_status_history
         WHERE order_id = :oid
         ORDER BY created_at ASC, id ASC'
    );
    $stmt->execute([':oid' => $orderId]);
    return $stmt->fetchAll();
}

/**
 * @return array{items: array, total: int, page: int, total_pages: int}
 */
function get_user_orders(int $userId, int $page = 1, int $perPage = ORDERS_PER_PAGE): array
{
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;

    $count = db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id = :uid');
    $count->execute([':uid' => $userId]);
    $total = (int) $count->fetchColumn();

    $stmt = db()->prepare(
        "SELECT * FROM orders
         WHERE user_id = :uid
         ORDER BY placed_at DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $stmt->execute([':uid' => $userId]);

    return [
        'items'       => $stmt->fetchAll(),
        'total'       => $total,
        'page'        => $page,
        'total_pages' => max(1, (int) ceil($total / $perPage)),
    ];
}

/**
 * Whether the current viewer can access an order.
 */
function can_view_order(array $order): bool
{
    $user = current_user();
    if ($user && is_admin()) {
        return true;
    }
    if ($user && !empty($order['user_id']) && (int) $order['user_id'] === (int) $user['id']) {
        return true;
    }
    // Guest confirmation via session
    $allowed = $_SESSION['guest_orders'] ?? [];
    return in_array($order['order_number'], $allowed, true);
}

function remember_guest_order(string $orderNumber): void
{
    if (!isset($_SESSION['guest_orders']) || !is_array($_SESSION['guest_orders'])) {
        $_SESSION['guest_orders'] = [];
    }
    $_SESSION['guest_orders'][] = $orderNumber;
    $_SESSION['guest_orders'] = array_values(array_unique($_SESSION['guest_orders']));
}

function order_status_label(string $status): string
{
    $map = [
        'pending'    => 'Pending',
        'paid'       => 'Paid',
        'processing' => 'Processing',
        'shipped'    => 'Shipped',
        'delivered'  => 'Delivered',
        'cancelled'  => 'Cancelled',
        'refunded'   => 'Refunded',
    ];
    return $map[$status] ?? ucfirst($status);
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_user_notifications(int $userId, int $limit = 30): array
{
    $stmt = db()->prepare(
        'SELECT * FROM notifications
         WHERE user_id = :uid
         ORDER BY created_at DESC
         LIMIT :lim'
    );
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function mark_notification_read(int $userId, int $notificationId): void
{
    $stmt = db()->prepare(
        'UPDATE notifications SET is_read = 1
         WHERE id = :id AND user_id = :uid'
    );
    $stmt->execute([':id' => $notificationId, ':uid' => $userId]);
}

function mark_all_notifications_read(int $userId): void
{
    $stmt = db()->prepare(
        'UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0'
    );
    $stmt->execute([':uid' => $userId]);
}

/**
 * Update customer profile / address.
 *
 * @param array<string, mixed> $data
 * @return array<string, string>
 */
function update_customer_profile(int $userId, array $data): array
{
    $errors = [];

    $first = trim((string) ($data['first_name'] ?? ''));
    $last = trim((string) ($data['last_name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $line1 = trim((string) ($data['address_line1'] ?? ''));
    $line2 = trim((string) ($data['address_line2'] ?? ''));
    $city = trim((string) ($data['city'] ?? ''));
    $province = trim((string) ($data['province'] ?? ''));
    $postal = trim((string) ($data['postal_code'] ?? ''));

    if ($first === '' || mb_strlen($first) > 80) {
        $errors['first_name'] = 'Please enter a valid first name.';
    }
    if ($last === '' || mb_strlen($last) > 80) {
        $errors['last_name'] = 'Please enter a valid last name.';
    }
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,30}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    if ($province !== '' && !in_array($province, sa_provinces(), true)) {
        $errors['province'] = 'Please select a valid province.';
    }

    if ($errors !== []) {
        return $errors;
    }

    $stmt = db()->prepare(
        'UPDATE users SET
            first_name = :first_name,
            last_name = :last_name,
            phone = :phone,
            address_line1 = :address_line1,
            address_line2 = :address_line2,
            city = :city,
            province = :province,
            postal_code = :postal_code
         WHERE id = :id'
    );

    $stmt->execute([
        ':first_name'     => $first,
        ':last_name'      => $last,
        ':phone'          => $phone !== '' ? $phone : null,
        ':address_line1'  => $line1 !== '' ? $line1 : null,
        ':address_line2'  => $line2 !== '' ? $line2 : null,
        ':city'           => $city !== '' ? $city : null,
        ':province'       => $province !== '' ? $province : null,
        ':postal_code'     => $postal !== '' ? $postal : null,
        ':id'             => $userId,
    ]);

    // Refresh session display fields
    if (!empty($_SESSION['user']) && (int) $_SESSION['user']['id'] === $userId) {
        $_SESSION['user']['first_name'] = $first;
        $_SESSION['user']['last_name'] = $last;
        $_SESSION['user']['phone'] = $phone !== '' ? $phone : null;
    }

    return [];
}
