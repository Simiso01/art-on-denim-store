<?php
/**
 * Art on Denim — Admin helper queries & mutations
 */

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * @return array<string, mixed>
 */
function admin_dashboard_stats(): array
{
    $pdo = db();

    $sales = (float) $pdo->query(
        "SELECT COALESCE(SUM(total), 0) FROM orders
         WHERE payment_status = 'paid' OR status IN ('paid','processing','shipped','delivered')"
    )->fetchColumn();

    $ordersToday = (int) $pdo->query(
        "SELECT COUNT(*) FROM orders WHERE DATE(placed_at) = CURDATE()"
    )->fetchColumn();

    $ordersTotal = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $customers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    $products = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
    $lowStock = (int) $pdo->query(
        'SELECT COUNT(*) FROM inventory WHERE quantity <= low_stock_threshold'
    )->fetchColumn();
    $messages = (int) $pdo->query(
        "SELECT COUNT(*) FROM contact_messages WHERE status = 'new'"
    )->fetchColumn();

    return [
        'sales_total'   => $sales,
        'orders_today'  => $ordersToday,
        'orders_total'  => $ordersTotal,
        'customers'     => $customers,
        'products'      => $products,
        'low_stock'     => $lowStock,
        'new_messages'  => $messages,
    ];
}

/**
 * @return array<int, array<string, mixed>>
 */
function admin_recent_orders(int $limit = 8): array
{
    $stmt = db()->prepare(
        'SELECT * FROM orders ORDER BY placed_at DESC LIMIT :lim'
    );
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * @return array<int, array<string, mixed>>
 */
function admin_low_stock(int $limit = 8): array
{
    $stmt = db()->prepare(
        "SELECT i.*, p.name AS product_name, p.sku
         FROM inventory i
         INNER JOIN products p ON p.id = i.product_id
         WHERE i.quantity <= i.low_stock_threshold
         ORDER BY i.quantity ASC
         LIMIT :lim"
    );
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * @return array{items: array, total: int, page: int, total_pages: int}
 */
function admin_list_products(string $q = '', int $page = 1, int $perPage = 20): array
{
    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        $where[] = '(p.name LIKE :q OR p.sku LIKE :q OR b.name LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }
    $whereSql = implode(' AND ', $where);
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;

    $count = db()->prepare(
        "SELECT COUNT(*) FROM products p LEFT JOIN brands b ON b.id = p.brand_id WHERE {$whereSql}"
    );
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $stmt = db()->prepare(
        "SELECT p.*, b.name AS brand_name, c.name AS category_name,
                (SELECT COALESCE(SUM(quantity),0) FROM inventory i WHERE i.product_id = p.id) AS stock_total
         FROM products p
         LEFT JOIN brands b ON b.id = p.brand_id
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE {$whereSql}
         ORDER BY p.updated_at DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $stmt->execute($params);

    return [
        'items'       => $stmt->fetchAll(),
        'total'       => $total,
        'page'        => $page,
        'total_pages' => max(1, (int) ceil($total / $perPage)),
    ];
}

/**
 * Secure product image upload. Returns relative path or error string prefixed with ERROR:
 */
function admin_upload_product_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return 'ERROR:Image upload failed.';
    }
    if (($file['size'] ?? 0) > UPLOAD_MAX_SIZE) {
        return 'ERROR:Image exceeds maximum size of 5MB.';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, UPLOAD_ALLOWED_MIME, true)) {
        return 'ERROR:Invalid image type. Use JPG, PNG, or WEBP.';
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return 'ERROR:File is not a valid image.';
    }

    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    $ext = $extMap[$mime] ?? null;
    $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!$ext || !in_array($originalExt, UPLOAD_ALLOWED_EXT, true)) {
        return 'ERROR:Invalid image extension.';
    }

    if (!is_dir(UPLOAD_PRODUCTS_DIR)) {
        mkdir(UPLOAD_PRODUCTS_DIR, 0755, true);
    }

    $filename = 'p_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_PRODUCTS_DIR . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return 'ERROR:Could not save uploaded image.';
    }

    // Verify readable image after move
    if (@getimagesize($dest) === false) {
        @unlink($dest);
        return 'ERROR:Uploaded file failed image verification.';
    }

    return 'uploads/products/' . $filename;
}

/**
 * @param array<string, mixed> $data
 * @return array<string, string>
 */
function admin_validate_product(array $data, bool $requireSkuUnique = true, ?int $ignoreId = null): array
{
    $errors = [];
    $name = trim((string) ($data['name'] ?? ''));
    $sku = trim((string) ($data['sku'] ?? ''));
    $price = $data['price'] ?? '';

    if ($name === '') {
        $errors['name'] = 'Product name is required.';
    }
    if ($sku === '') {
        $errors['sku'] = 'SKU is required.';
    } elseif ($requireSkuUnique) {
        $sql = 'SELECT id FROM products WHERE sku = :sku';
        $params = [':sku' => $sku];
        if ($ignoreId) {
            $sql .= ' AND id != :id';
            $params[':id'] = $ignoreId;
        }
        $stmt = db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors['sku'] = 'This SKU is already in use.';
        }
    }
    if (!is_numeric($price) || (float) $price < 0) {
        $errors['price'] = 'Enter a valid price.';
    }

    return $errors;
}

function admin_save_product_inventory(int $productId, array $sizes, array $quantities): void
{
    $pdo = db();
    $upsert = $pdo->prepare(
        'INSERT INTO inventory (product_id, size, quantity, low_stock_threshold)
         VALUES (:pid, :size, :qty, :thresh)
         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
    );

    foreach ($sizes as $i => $size) {
        $size = trim((string) $size);
        if ($size === '') {
            continue;
        }
        $qty = max(0, (int) ($quantities[$i] ?? 0));
        $upsert->execute([
            ':pid'    => $productId,
            ':size'   => $size,
            ':qty'    => $qty,
            ':thresh' => LOW_STOCK_THRESHOLD,
        ]);
    }
}

/**
 * @return array{items: array, total: int, page: int, total_pages: int}
 */
function admin_list_orders(string $status = '', string $q = '', int $page = 1): array
{
    $where = ['1=1'];
    $params = [];
    if ($status !== '') {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }
    if ($q !== '') {
        $where[] = '(order_number LIKE :q OR customer_email LIKE :q OR shipping_full_name LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }
    $whereSql = implode(' AND ', $where);
    $perPage = ORDERS_PER_PAGE;
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;

    $count = db()->prepare("SELECT COUNT(*) FROM orders WHERE {$whereSql}");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $stmt = db()->prepare(
        "SELECT * FROM orders WHERE {$whereSql}
         ORDER BY placed_at DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $stmt->execute($params);

    return [
        'items'       => $stmt->fetchAll(),
        'total'       => $total,
        'page'        => $page,
        'total_pages' => max(1, (int) ceil($total / $perPage)),
    ];
}

function admin_update_order_status(int $orderId, string $status, string $note = '', ?string $trackingNumber = null, ?string $trackingCarrier = null): ?string
{
    $allowed = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
    if (!in_array($status, $allowed, true)) {
        return 'Invalid status.';
    }

    $order = get_order_by_id($orderId);
    if (!$order) {
        return 'Order not found.';
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $paymentStatus = $order['payment_status'];
        if (in_array($status, ['paid', 'processing', 'shipped', 'delivered'], true)) {
            $paymentStatus = 'paid';
        }
        if ($status === 'refunded') {
            $paymentStatus = 'refunded';
        }

        $stmt = $pdo->prepare(
            'UPDATE orders SET
                status = :status,
                payment_status = :payment_status,
                tracking_number = COALESCE(:tracking_number, tracking_number),
                tracking_carrier = COALESCE(:tracking_carrier, tracking_carrier)
             WHERE id = :id'
        );
        $stmt->execute([
            ':status'           => $status,
            ':payment_status'   => $paymentStatus,
            ':tracking_number'  => $trackingNumber,
            ':tracking_carrier' => $trackingCarrier,
            ':id'               => $orderId,
        ]);

        $hist = $pdo->prepare(
            'INSERT INTO order_status_history (order_id, status, note, created_by)
             VALUES (:oid, :status, :note, :uid)'
        );
        $hist->execute([
            ':oid'    => $orderId,
            ':status' => $status,
            ':note'   => $note !== '' ? $note : 'Status updated by admin',
            ':uid'    => current_user()['id'] ?? null,
        ]);

        if (!empty($order['user_id'])) {
            $noteStmt = $pdo->prepare(
                'INSERT INTO notifications (user_id, type, title, message, link)
                 VALUES (:uid, :type, :title, :message, :link)'
            );
            $noteStmt->execute([
                ':uid'     => (int) $order['user_id'],
                ':type'    => 'order',
                ':title'   => 'Order update',
                ':message' => 'Order ' . $order['order_number'] . ' is now ' . order_status_label($status) . '.',
                ':link'    => 'pages/order-details.php?number=' . urlencode($order['order_number']),
            ]);
        }

        $pdo->commit();
        return null;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('AOD admin_update_order_status: ' . $e->getMessage());
        return 'Could not update order status.';
    }
}

/**
 * @return array<string, mixed>
 */
function admin_sales_report(?string $from = null, ?string $to = null): array
{
    $pdo = db();
    $where = ["(payment_status = 'paid' OR status IN ('paid','processing','shipped','delivered'))"];
    $params = [];

    if ($from) {
        $where[] = 'DATE(placed_at) >= :from';
        $params[':from'] = $from;
    }
    if ($to) {
        $where[] = 'DATE(placed_at) <= :to';
        $params[':to'] = $to;
    }
    $whereSql = implode(' AND ', $where);

    $summary = $pdo->prepare(
        "SELECT
            COALESCE(SUM(total),0) AS revenue,
            COUNT(*) AS orders,
            COALESCE(AVG(total),0) AS avg_order
         FROM orders WHERE {$whereSql}"
    );
    $summary->execute($params);
    $sum = $summary->fetch() ?: ['revenue' => 0, 'orders' => 0, 'avg_order' => 0];

    $daily = $pdo->prepare(
        "SELECT DATE(placed_at) AS day, COALESCE(SUM(total),0) AS revenue, COUNT(*) AS orders
         FROM orders WHERE {$whereSql}
         GROUP BY DATE(placed_at)
         ORDER BY day DESC
         LIMIT 31"
    );
    $daily->execute($params);

    $best = $pdo->prepare(
        "SELECT oi.product_name, oi.product_sku, SUM(oi.quantity) AS qty, SUM(oi.line_total) AS revenue
         FROM order_items oi
         INNER JOIN orders o ON o.id = oi.order_id
         WHERE {$whereSql}
         GROUP BY oi.product_sku, oi.product_name
         ORDER BY qty DESC
         LIMIT 10"
    );
    $best->execute($params);

    $byBrand = $pdo->prepare(
        "SELECT COALESCE(b.name, 'Unbranded') AS brand_name,
                SUM(oi.line_total) AS revenue, SUM(oi.quantity) AS qty
         FROM order_items oi
         INNER JOIN orders o ON o.id = oi.order_id
         LEFT JOIN products p ON p.id = oi.product_id
         LEFT JOIN brands b ON b.id = p.brand_id
         WHERE {$whereSql}
         GROUP BY b.name
         ORDER BY revenue DESC"
    );
    $byBrand->execute($params);

    $byCategory = $pdo->prepare(
        "SELECT COALESCE(c.name, 'Uncategorised') AS category_name,
                SUM(oi.line_total) AS revenue, SUM(oi.quantity) AS qty
         FROM order_items oi
         INNER JOIN orders o ON o.id = oi.order_id
         LEFT JOIN products p ON p.id = oi.product_id
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE {$whereSql}
         GROUP BY c.name
         ORDER BY revenue DESC"
    );
    $byCategory->execute($params);

    // Weekly / monthly rollups
    $weekly = $pdo->prepare(
        "SELECT YEARWEEK(placed_at, 1) AS period, COALESCE(SUM(total),0) AS revenue, COUNT(*) AS orders
         FROM orders WHERE {$whereSql}
         GROUP BY YEARWEEK(placed_at, 1)
         ORDER BY period DESC
         LIMIT 12"
    );
    $weekly->execute($params);

    $monthly = $pdo->prepare(
        "SELECT DATE_FORMAT(placed_at, '%Y-%m') AS period, COALESCE(SUM(total),0) AS revenue, COUNT(*) AS orders
         FROM orders WHERE {$whereSql}
         GROUP BY DATE_FORMAT(placed_at, '%Y-%m')
         ORDER BY period DESC
         LIMIT 12"
    );
    $monthly->execute($params);

    return [
        'summary'     => $sum,
        'daily'       => $daily->fetchAll(),
        'weekly'      => $weekly->fetchAll(),
        'monthly'     => $monthly->fetchAll(),
        'bestsellers' => $best->fetchAll(),
        'by_brand'    => $byBrand->fetchAll(),
        'by_category' => $byCategory->fetchAll(),
        'from'        => $from,
        'to'          => $to,
    ];
}
