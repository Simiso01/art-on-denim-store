<?php
/**
 * Raise each required table to at least 10 linked rows.
 * Run: C:\wamp64\bin\php\php7.4.0\php.exe database\seed_minimum.php
 * New customers use the existing demo password from the SQL file. No API tokens are invented.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = db();
$hash = '$2y$10$I/l1.eNDftcyVtF9p3RkBOSpkOc.yAEAcwJDriAFwvDrf.c0Z9PBm';

$people = [
    ['Amahle', 'Dlamini', 'amahle.seed@example.com', 'Johannesburg', 'Gauteng', '2001'],
    ['Sipho', 'Khumalo', 'sipho.seed@example.com', 'Pretoria', 'Gauteng', '0002'],
    ['Naledi', 'Molefe', 'naledi.seed@example.com', 'Cape Town', 'Western Cape', '8001'],
    ['Johan', 'Botha', 'johan.seed@example.com', 'Stellenbosch', 'Western Cape', '7600'],
    ['Zanele', 'Cele', 'zanele.seed@example.com', 'Durban', 'KwaZulu-Natal', '4001'],
    ['Pieter', 'van Wyk', 'pieter.seed@example.com', 'Bloemfontein', 'Free State', '9301'],
    ['Lerato', 'Mahlangu', 'lerato.seed@example.com', 'Polokwane', 'Limpopo', '0700'],
    ['Emma', 'Jacobs', 'emma.seed@example.com', 'Gqeberha', 'Eastern Cape', '6001'],
    ['Kagiso', 'Modise', 'kagiso.seed@example.com', 'Mahikeng', 'North West', '2745'],
    ['Fatima', 'Adams', 'fatima.seed@example.com', 'Kimberley', 'Northern Cape', '8301'],
];

$userStmt = $pdo->prepare(
    'INSERT INTO users (first_name, last_name, email, phone, password_hash, role, status, address_line1, city, province, postal_code)
     VALUES (:first, :last, :email, :phone, :hash, "customer", "active", :line, :city, :province, :postal)
     ON DUPLICATE KEY UPDATE first_name = VALUES(first_name)'
);
foreach ($people as $i => $person) {
    $userStmt->execute([
        ':first' => $person[0],
        ':last' => $person[1],
        ':email' => $person[2],
        ':phone' => '08210000' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
        ':hash' => $hash,
        ':line' => ($i + 12) . ' Studio Street',
        ':city' => $person[3],
        ':province' => $person[4],
        ':postal' => $person[5],
    ]);
}

$categories = [
    ['Outerwear', 'outerwear', 'Coats and overshirts'],
    ['Knitwear', 'knitwear', 'Jerseys and knits'],
    ['Accessories', 'accessories', 'Belts, caps, and small leather'],
    ['Tailoring', 'tailoring', 'Trousers and structured pieces'],
    ['Denim', 'denim', 'Jeans, jackets, and selvedge'],
    ['Footwear', 'footwear', 'Boots and shoes'],
    ['Shirts', 'shirts', 'Shirts and overshirts'],
    ['Bags', 'bags', 'Leather and canvas bags'],
];
$catStmt = $pdo->prepare(
    'INSERT INTO categories (name, slug, description, sort_order, is_active)
     VALUES (:name, :slug, :description, :sort, 1)
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
);
foreach ($categories as $i => $row) {
    $catStmt->execute([
        ':name' => $row[0],
        ':slug' => $row[1],
        ':description' => $row[2],
        ':sort' => 20 + $i,
    ]);
}

$brands = [
    ['Our Legacy', 'our-legacy', 'Stockholm'],
    ['Lemaire', 'lemaire', 'Paris'],
    ['Auralee', 'auralee', 'Tokyo'],
    ['Studio Nicholson', 'studio-nicholson', 'London'],
];
$brandStmt = $pdo->prepare(
    'INSERT INTO brands (name, slug, description, is_featured, is_active)
     VALUES (:name, :slug, :description, 0, 1)
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
);
foreach ($brands as $row) {
    $brandStmt->execute([
        ':name' => $row[0],
        ':slug' => $row[1],
        ':description' => $row[0] . ' (' . $row[2] . ') pieces carried by Art on Denim.',
    ]);
}

$users = $pdo->query("SELECT id, email, first_name, last_name, phone, city, province, postal_code, address_line1 FROM users WHERE role = 'customer' ORDER BY id")->fetchAll();
$products = $pdo->query(
    'SELECT p.id, p.name, p.sku, p.price, i.size
     FROM products p
     INNER JOIN inventory i ON i.product_id = p.id AND (i.quantity - i.reserved) > 0
     WHERE p.status = "active"
     ORDER BY p.id, i.size'
)->fetchAll();
if (count($users) < 1 || count($products) < 1) {
    fwrite(STDERR, "Need customers and stocked products before orders can be linked.\n");
    exit(1);
}

$cartCount = (int) $pdo->query('SELECT COUNT(*) FROM cart')->fetchColumn();
$cartInsert = $pdo->prepare('INSERT INTO cart (user_id) VALUES (:user)');
$itemInsert = $pdo->prepare(
    'INSERT INTO cart_items (cart_id, product_id, size, quantity, unit_price)
     VALUES (:cart, :product, :size, 1, :price)
     ON DUPLICATE KEY UPDATE quantity = quantity'
);
for ($i = $cartCount; $i < 10; $i++) {
    $user = $users[$i % count($users)];
    $product = $products[$i % count($products)];
    $cartInsert->execute([':user' => $user['id']]);
    $itemInsert->execute([
        ':cart' => (int) $pdo->lastInsertId(),
        ':product' => $product['id'],
        ':size' => $product['size'],
        ':price' => $product['price'],
    ]);
}

$wishCount = (int) $pdo->query('SELECT COUNT(*) FROM wishlist')->fetchColumn();
$wish = $pdo->prepare('INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (:user, :product)');
$n = 0;
while ((int) $pdo->query('SELECT COUNT(*) FROM wishlist')->fetchColumn() < 10 && $n < 80) {
    $user = $users[$n % count($users)];
    $product = $products[$n % count($products)];
    $wish->execute([':user' => $user['id'], ':product' => $product['id']]);
    $n++;
}

$statuses = ['pending', 'paid', 'processing', 'shipped', 'delivered'];
$orderInsert = $pdo->prepare(
    'INSERT INTO orders (
        order_number, user_id, status, payment_status, payment_method, subtotal, shipping_amount,
        discount_amount, tax_amount, total, currency, customer_email, customer_phone,
        shipping_full_name, shipping_address_line1, shipping_city, shipping_province,
        shipping_postal_code, shipping_country, tracking_number, tracking_carrier, placed_at
     ) VALUES (
        :number, :user, :status, :pay_status, "payfast", :subtotal, :shipping,
        0, 0, :total, "ZAR", :email, :phone,
        :name, :line, :city, :province, :postal, "South Africa", :tracking, :carrier, :placed
     )'
);
$lineInsert = $pdo->prepare(
    'INSERT INTO order_items (order_id, product_id, product_name, product_sku, size, quantity, unit_price, line_total)
     VALUES (:order, :product, :name, :sku, :size, 1, :price, :line)'
);
$payInsert = $pdo->prepare(
    'INSERT INTO payments (order_id, provider, provider_ref, amount, currency, status, paid_at)
     VALUES (:order, "payfast", :ref, :amount, "ZAR", :status, :paid)'
);
$histInsert = $pdo->prepare(
    'INSERT INTO order_status_history (order_id, status, note, created_by) VALUES (:order, :status, :note, :by)'
);
$noteInsert = $pdo->prepare(
    'INSERT INTO notifications (user_id, type, title, message, link, is_read)
     VALUES (:user, "order", :title, :message, :link, 0)'
);

$pdo->exec("DELETE FROM orders WHERE order_number LIKE 'AOD-SEED-%'");
$existingOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
for ($i = $existingOrders; $i < 10; $i++) {
    $user = $users[$i % count($users)];
    $product = $products[$i % count($products)];
    $status = $statuses[$i % count($statuses)];
    $price = (float) $product['price'];
    $shipping = $price >= 1500 ? 0 : 99;
    $number = sprintf('AOD-SEED-%02d', $i + 1);
    $exists = $pdo->prepare('SELECT id FROM orders WHERE order_number = :number');
    $exists->execute([':number' => $number]);
    if ($exists->fetch()) {
        continue;
    }
    $orderInsert->execute([
        ':number' => $number,
        ':user' => $user['id'],
        ':status' => $status,
        ':pay_status' => in_array($status, ['paid', 'processing', 'shipped', 'delivered'], true) ? 'paid' : 'pending',
        ':subtotal' => $price,
        ':shipping' => $shipping,
        ':total' => $price + $shipping,
        ':email' => $user['email'],
        ':phone' => $user['phone'],
        ':name' => $user['first_name'] . ' ' . $user['last_name'],
        ':line' => $user['address_line1'] ?: '12 Studio Street',
        ':city' => $user['city'] ?: 'Johannesburg',
        ':province' => $user['province'] ?: 'Gauteng',
        ':postal' => $user['postal_code'] ?: '2000',
        ':tracking' => $status === 'shipped' || $status === 'delivered' ? 'TRK' . (1000 + $i) : null,
        ':carrier' => $status === 'shipped' || $status === 'delivered' ? 'The Courier Guy' : null,
        ':placed' => date('Y-m-d H:i:s', strtotime('-' . (30 - $i) . ' days')),
    ]);
    $orderId = (int) $pdo->lastInsertId();
    $lineInsert->execute([
        ':order' => $orderId,
        ':product' => $product['id'],
        ':name' => $product['name'],
        ':sku' => $product['sku'],
        ':size' => $product['size'],
        ':price' => $price,
        ':line' => $price,
    ]);
    $payInsert->execute([
        ':order' => $orderId,
        ':ref' => 'PF-SEED-' . $number,
        ':amount' => $price + $shipping,
        ':status' => in_array($status, ['paid', 'processing', 'shipped', 'delivered'], true) ? 'completed' : 'pending',
        ':paid' => in_array($status, ['paid', 'processing', 'shipped', 'delivered'], true) ? date('Y-m-d H:i:s') : null,
    ]);
    $histInsert->execute([
        ':order' => $orderId,
        ':status' => 'pending',
        ':note' => 'Order placed.',
        ':by' => $user['id'],
    ]);
    if ($status !== 'pending') {
        $histInsert->execute([
            ':order' => $orderId,
            ':status' => $status,
            ':note' => 'Status updated for the studio record.',
            ':by' => $user['id'],
        ]);
    }
    $noteInsert->execute([
        ':user' => $user['id'],
        ':title' => 'Order ' . $number,
        ':message' => 'Your Art on Denim order is ' . $status . '.',
        ':link' => 'pages/order-details.php?number=' . $number,
    ]);
}

$msg = $pdo->prepare(
    'INSERT INTO contact_messages (name, email, subject, message, status)
     SELECT :name, :email, :subject, :message, "new" FROM DUAL
     WHERE (SELECT COUNT(*) FROM contact_messages) < 10'
);
$messages = [
    ['Amahle Dlamini', 'amahle.seed@example.com', 'Hem on the house jean', 'Can the studio shorten the inseam after purchase?'],
    ['Sipho Khumalo', 'sipho.seed@example.com', 'Johannesburg collection', 'When does the next house denim drop arrive in store?'],
    ['Naledi Molefe', 'naledi.seed@example.com', 'Size exchange', 'I need to exchange a size 32 for a size 30.'],
    ['Johan Botha', 'johan.seed@example.com', 'EFT payment', 'I sent the EFT. Please confirm when it reflects.'],
    ['Zanele Cele', 'zanele.seed@example.com', 'Cape Town delivery', 'How long is delivery from Johannesburg to Durban?'],
    ['Pieter van Wyk', 'pieter.seed@example.com', 'Gift wrap', 'Do you offer a note with an order sent as a gift?'],
    ['Lerato Mahlangu', 'lerato.seed@example.com', 'Sale return', 'What is the return window on sale denim?'],
    ['Emma Jacobs', 'emma.seed@example.com', 'Store visit', 'Is there a fitting appointment at the studio?'],
    ['Kagiso Modise', 'kagiso.seed@example.com', 'Wishlist', 'Please tell me when the black overshirt is back.'],
    ['Fatima Adams', 'fatima.seed@example.com', 'Invoice', 'Please send a VAT invoice for my last order.'],
];
foreach ($messages as $row) {
    $msg->execute([
        ':name' => $row[0],
        ':email' => $row[1],
        ':subject' => $row[2],
        ':message' => $row[3],
    ]);
}

$tables = [
    'users', 'categories', 'brands', 'products', 'product_images', 'inventory',
    'cart', 'cart_items', 'wishlist', 'orders', 'order_items', 'payments',
    'promotions', 'notifications', 'contact_messages', 'order_status_history', 'blog_posts',
];
foreach ($tables as $table) {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
    echo str_pad($table, 24) . $count . ($count < 10 ? "  SHORT\n" : "\n");
}
echo "api_tokens left to real sign-in only\n";
