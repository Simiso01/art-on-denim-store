<?php
/**
 * Art on Denim — journal, coupons, catalogue extras, shopping assistant.
 */

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

function ensure_studio_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;

    $pdo = db();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS blog_posts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            slug VARCHAR(220) NOT NULL,
            excerpt VARCHAR(500) NOT NULL,
            body MEDIUMTEXT NOT NULL,
            author_name VARCHAR(120) NOT NULL DEFAULT 'Art on Denim',
            status ENUM('draft','published') NOT NULL DEFAULT 'published',
            published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_blog_slug (slug),
            KEY idx_blog_status (status, published_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $column = $pdo->query("SHOW COLUMNS FROM orders LIKE 'coupon_code'")->fetch();
    if (!$column) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) NULL AFTER discount_amount');
    }

    $cartCoupon = $pdo->query("SHOW COLUMNS FROM cart LIKE 'coupon_code'")->fetch();
    if (!$cartCoupon) {
        $pdo->exec('ALTER TABLE cart ADD COLUMN coupon_code VARCHAR(50) NULL');
    }

    seed_studio_content();
}

function seed_studio_content(): void
{
    $pdo = db();
    $posts = (int) $pdo->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
    if ($posts < 10) {
        $stmt = $pdo->prepare(
            'INSERT INTO blog_posts (title, slug, excerpt, body, author_name, status, published_at)
             VALUES (:title, :slug, :excerpt, :body, :author, :status, :published)
             ON DUPLICATE KEY UPDATE title = VALUES(title)'
        );
        foreach (studio_seed_posts() as $i => $post) {
            $stmt->execute([
                ':title' => $post['title'],
                ':slug' => $post['slug'],
                ':excerpt' => $post['excerpt'],
                ':body' => $post['body'],
                ':author' => 'Art on Denim Studio',
                ':status' => 'published',
                ':published' => date('Y-m-d H:i:s', strtotime('-' . (20 - $i) . ' days')),
            ]);
        }
    }

    $couponCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM promotions WHERE coupon_code IS NOT NULL AND coupon_code <> ''"
    )->fetchColumn();
    if ($couponCount >= 10) {
        return;
    }

    $couponStmt = $pdo->prepare(
        'INSERT INTO promotions
            (title, slug, subtitle, description, promo_type, discount_type, discount_value, coupon_code,
             cta_label, cta_url, placement, sort_order, is_active, starts_at, ends_at)
         VALUES
            (:title, :slug, :subtitle, :description, \'coupon\', :dtype, :dvalue, :code,
             \'Shop\', \'pages/shop.php\', \'checkout\', :sort, 1, \'2026-01-01 00:00:00\', \'2026-12-31 23:59:59\')
         ON DUPLICATE KEY UPDATE discount_value = VALUES(discount_value), is_active = 1'
    );
    $sort = 10;
    foreach (studio_seed_coupons() as $coupon) {
        $couponStmt->execute([
            ':title' => $coupon['title'],
            ':slug' => 'coupon-' . strtolower($coupon['code']),
            ':subtitle' => $coupon['subtitle'],
            ':description' => $coupon['subtitle'],
            ':dtype' => $coupon['type'],
            ':dvalue' => $coupon['value'],
            ':code' => $coupon['code'],
            ':sort' => $sort,
        ]);
        $sort++;
    }
}

/**
 * @return array<int, array{title: string, slug: string, excerpt: string, body: string}>
 */
function studio_seed_posts(): array
{
    return [
        ['title' => 'How the house jean is cut', 'slug' => 'how-the-house-jean-is-cut', 'excerpt' => 'Why the AOD taper sits higher at the waist and cleaner through the leg.', 'body' => "The house jean starts from a raw selvedge block and a paper pattern pinned in Johannesburg.\n\nWe keep the rise moderate, the thigh quiet, and the taper visible only from the knee. That is the difference between a costume and a daily uniform.\n\nEach pair is finished with brass hardware and a chain-stitch hem that will settle after the first month of wear."],
        ['title' => 'A size guide for denim', 'slug' => 'a-size-guide-for-denim', 'excerpt' => 'Waist, thigh, and hem: how to choose a size before you order.', 'body' => "Measure a pair you already wear, not your body, and match the waist first.\n\nIf you sit between sizes, take the larger waist on rigid raw denim and the smaller waist on a washed black jean.\n\nThe product page shows how many of each size are left. When a size reads low, it will not be restocked in that dye lot."],
        ['title' => 'Washing raw denim', 'slug' => 'washing-raw-denim', 'excerpt' => 'Wear it, then wash it cold, inside out, and alone.', 'body' => "Raw denim does not need a wash in the first month unless you want to set a fade early.\n\nWhen you do wash, turn the jean inside out, use cold water, and skip the tumble dryer. Hang it by the waistband, not the legs.\n\nA brass cap and a knit can be washed on a gentle cycle. Outerwear should be brushed, not soaked."],
        ['title' => 'Johannesburg studio notes', 'slug' => 'johannesburg-studio-notes', 'excerpt' => 'What the Maboneng workroom is actually for.', 'body' => "The studio is a fitting room, a photo table, and a packing bench. It is not a factory.\n\nHouse pieces are cut here and sewn with a small workshop in the city. Designer labels arrive finished and are checked against the size run before they go online.\n\nVisits are by appointment through the contact form."],
        ['title' => 'Reading a Rick Owens size', 'slug' => 'reading-a-rick-owens-size', 'excerpt' => 'These silhouettes are cut long. Order the size you wear in tailoring, then check the length.', 'body' => "Drop-crotch pants and elongated tees are designed with extra cloth. Sizing down to 'fix' the volume usually ruins the shoulder and the rise.\n\nUse the size you wear in a tailored jacket, then read the product notes for hem length.\n\nIf you are between sizes, write to us before you order. We would rather hold a piece than process a return."],
        ['title' => 'What complimentary shipping covers', 'slug' => 'what-complimentary-shipping-covers', 'excerpt' => 'Orders over the threshold ship free inside South Africa.', 'body' => "Complimentary shipping applies once the merchandise subtotal reaches the amount shown in the announcement bar.\n\nCoupons reduce the merchandise total. Shipping is calculated after that discount.\n\nDelivery is inside South Africa. Remote towns may take an extra day after the parcel leaves Johannesburg."],
        ['title' => 'How order tracking works', 'slug' => 'how-order-tracking-works', 'excerpt' => 'The timeline on the tracking page is the status history stored with the order.', 'body' => "Every status change is written to the order history: pending, paid, processing, shipped, delivered.\n\nGuests can open a shipment with the order number and the email used at checkout. Signed-in customers can open their own orders from the account page.\n\nA carrier number appears only after the parcel is handed over."],
        ['title' => 'Building a week of outfits', 'slug' => 'building-a-week-of-outfits', 'excerpt' => 'One jean, one outer layer, one knit. Repeat.', 'body' => "Start with the indigo selvedge or the washed black straight. Add the canvas jacket for day and a knit when the evening cools.\n\nThe house cap is the small brass note. It should not compete with a designer sneaker.\n\nSale pieces are the same clothes, marked down. They are not a separate quality line."],
        ['title' => 'Brass hardware, and why it marks', 'slug' => 'brass-hardware-and-why-it-marks', 'excerpt' => 'The metal is unlacquered. It will darken where your hands live.', 'body' => "Buttons, cap hardware, and zipper pulls are brass because it records use.\n\nIf you want it bright, a cloth and a drop of metal polish will bring it back. If you want the mark, leave it.\n\nThis is the same metal referenced in the colour of the site: chalk cloth, denim ground, brass edge."],
        ['title' => 'Returns without drama', 'slug' => 'returns-without-drama', 'excerpt' => 'Unworn pieces can come back within fourteen days.', 'body' => "If a size is wrong, contact us with the order number. Unworn items with tags can be returned within fourteen days of delivery.\n\nRaw denim that has been worn and faded is final. Washed pieces that are still unworn are not.\n\nRefunds go back to the original payment method after the parcel is checked in Johannesburg."],
    ];
}

/**
 * @return array<int, array{code: string, title: string, subtitle: string, type: string, value: float}>
 */
function studio_seed_coupons(): array
{
    return [
        ['code' => 'WELCOME10', 'title' => 'Welcome 10%', 'subtitle' => 'Ten percent off your first Art on Denim order.', 'type' => 'percent', 'value' => 10],
        ['code' => 'DENIM15', 'title' => 'Denim 15%', 'subtitle' => 'Fifteen percent off the current bag.', 'type' => 'percent', 'value' => 15],
        ['code' => 'HOUSE20', 'title' => 'House 20%', 'subtitle' => 'Twenty percent off for the house list.', 'type' => 'percent', 'value' => 20],
        ['code' => 'SALE25', 'title' => 'Sale 25%', 'subtitle' => 'Twenty-five percent off marked sale styling.', 'type' => 'percent', 'value' => 25],
        ['code' => 'NIGHT30', 'title' => 'Night 30%', 'subtitle' => 'Thirty percent off after hours.', 'type' => 'percent', 'value' => 30],
        ['code' => 'ATELIER15', 'title' => 'Atelier 15%', 'subtitle' => 'Fifteen percent off the atelier edit.', 'type' => 'percent', 'value' => 15],
        ['code' => 'BRASS100', 'title' => 'Brass R100', 'subtitle' => 'One hundred rand off the merchandise total.', 'type' => 'fixed', 'value' => 100],
        ['code' => 'CAPE50', 'title' => 'Cape R50', 'subtitle' => 'Fifty rand off, named for the coast.', 'type' => 'fixed', 'value' => 50],
        ['code' => 'JOZI200', 'title' => 'Jozi R200', 'subtitle' => 'Two hundred rand off orders packed in Johannesburg.', 'type' => 'fixed', 'value' => 200],
        ['code' => 'STUDIO75', 'title' => 'Studio R75', 'subtitle' => 'Seventy-five rand off from the studio desk.', 'type' => 'fixed', 'value' => 75],
    ];
}

/**
 * @return array<string, mixed>|null
 */
function find_coupon(string $code): ?array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return null;
    }

    $stmt = db()->prepare(
        "SELECT * FROM promotions
         WHERE coupon_code = :code
           AND is_active = 1
           AND discount_type IN ('percent', 'fixed')
           AND discount_value > 0
           AND (starts_at IS NULL OR starts_at <= NOW())
           AND (ends_at IS NULL OR ends_at >= NOW())
         LIMIT 1"
    );
    $stmt->execute([':code' => $code]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function apply_coupon_code(string $code): string
{
    $coupon = find_coupon($code);
    if (!$coupon) {
        unset($_SESSION['coupon_code']);
        save_account_coupon(null);
        return 'That code is not active.';
    }
    $saved = strtoupper((string) $coupon['coupon_code']);
    $_SESSION['coupon_code'] = $saved;
    save_account_coupon($saved);
    return '';
}

function clear_coupon_code(): void
{
    unset($_SESSION['coupon_code']);
    save_account_coupon(null);
}

function save_account_coupon(?string $code): void
{
    $user = current_user();
    if (!$user || !function_exists('get_or_create_cart')) {
        return;
    }
    get_or_create_cart();
    db()->prepare('UPDATE cart SET coupon_code = :code WHERE user_id = :uid')
        ->execute([
            ':code' => $code,
            ':uid' => (int) $user['id'],
        ]);
}

function load_account_coupon(): string
{
    $user = current_user();
    if (!$user) {
        return '';
    }
    $stmt = db()->prepare(
        'SELECT coupon_code FROM cart
         WHERE user_id = :uid AND coupon_code IS NOT NULL AND coupon_code <> ""
         ORDER BY updated_at DESC
         LIMIT 1'
    );
    $stmt->execute([':uid' => (int) $user['id']]);
    return (string) ($stmt->fetchColumn() ?: '');
}

/**
 * @return array<string, mixed>|null
 */
function applied_coupon(): ?array
{
    $code = (string) ($_SESSION['coupon_code'] ?? '');
    if ($code === '') {
        $code = load_account_coupon();
        if ($code !== '') {
            $_SESSION['coupon_code'] = $code;
        }
    }
    if ($code === '') {
        return null;
    }
    $coupon = find_coupon($code);
    if (!$coupon) {
        unset($_SESSION['coupon_code']);
        save_account_coupon(null);
    }
    return $coupon;
}

function coupon_discount_amount(array $coupon, float $subtotal): float
{
    if ($subtotal <= 0) {
        return 0.0;
    }
    $value = (float) $coupon['discount_value'];
    if (($coupon['discount_type'] ?? '') === 'percent') {
        $amount = $subtotal * ($value / 100);
    } else {
        $amount = $value;
    }
    return round(min($subtotal, max(0, $amount)), 2);
}

/**
 * @return array<int, string>
 */
function catalogue_sizes(): array
{
    $rows = db()->query(
        "SELECT DISTINCT size FROM inventory WHERE (quantity - reserved) > 0 ORDER BY size ASC"
    )->fetchAll();
    return array_map(static function ($row) {
        return (string) $row['size'];
    }, $rows);
}

/**
 * @param array<string, mixed> $product
 * @return array<int, array<string, mixed>>
 */
function related_products(array $product, int $limit = 4): array
{
    $limit = max(1, min(8, $limit));
    $stmt = db()->prepare(
        "SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name, c.slug AS category_slug,
                (
                    SELECT pi.image_path FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC
                    LIMIT 1
                ) AS image
         FROM products p
         LEFT JOIN brands b ON b.id = p.brand_id
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.status = 'active'
           AND p.id <> :id
           AND (p.brand_id = :brand OR p.category_id = :category)
         ORDER BY (p.brand_id = :brand2) DESC, p.is_featured DESC, p.created_at DESC
         LIMIT {$limit}"
    );
    $stmt->execute([
        ':id' => (int) $product['id'],
        ':brand' => $product['brand_id'],
        ':brand2' => $product['brand_id'],
        ':category' => $product['category_id'],
    ]);
    return $stmt->fetchAll();
}

/**
 * @return array<int, array<string, mixed>>
 */
function published_posts(int $limit = 12): array
{
    $limit = max(1, min(50, $limit));
    $stmt = db()->query(
        "SELECT * FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC LIMIT {$limit}"
    );
    return $stmt->fetchAll();
}

/**
 * @return array<string, mixed>|null
 */
function find_post_by_slug(string $slug): ?array
{
    $stmt = db()->prepare(
        "SELECT * FROM blog_posts WHERE slug = :slug AND status = 'published' LIMIT 1"
    );
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * @return array{reply: string, products: array<int, array<string, mixed>>}
 */
function studio_chat_reply(string $message): array
{
    $text = trim($message);
    $lower = strtolower($text);
    $products = [];

    if ($text === '' || mb_strlen($text) > 400) {
        return ['reply' => 'Ask me about shipping, sizes, a product name, a coupon, or where to track an order.', 'products' => []];
    }

    if (preg_match('/ship|deliver|courier/', $lower)) {
        return [
            'reply' => 'Shipping inside South Africa is ' . money(DEFAULT_SHIPPING) . '. It is complimentary once the merchandise total, after any coupon, reaches ' . money(FREE_SHIPPING_THRESHOLD) . '.',
            'products' => [],
        ];
    }

    if (preg_match('/track|where is my order|order number/', $lower)) {
        return [
            'reply' => 'Open Order tracking and enter the number that starts with AOD, plus the email used at checkout. The timeline on that page is the saved status history.',
            'products' => [],
        ];
    }

    if (preg_match('/coupon|discount|code|promo/', $lower)) {
        $codes = db()->query(
            "SELECT coupon_code, title, discount_type, discount_value
             FROM promotions
             WHERE coupon_code IS NOT NULL AND coupon_code <> '' AND is_active = 1
             ORDER BY sort_order ASC
             LIMIT 10"
        )->fetchAll();
        $lines = [];
        foreach ($codes as $code) {
            $off = $code['discount_type'] === 'percent'
                ? ((float) $code['discount_value']) . '%'
                : money((float) $code['discount_value']);
            $lines[] = $code['coupon_code'] . ' (' . $off . ')';
        }
        $list = $lines ? implode(', ', $lines) : 'No codes are active right now.';
        return ['reply' => 'Apply a code in the cart summary. Active codes: ' . $list, 'products' => []];
    }

    if (preg_match('/return|refund|exchange/', $lower)) {
        return [
            'reply' => 'Unworn pieces with tags can be returned within fourteen days of delivery. Worn raw denim is final. Start from the contact page with your order number.',
            'products' => [],
        ];
    }

    if (preg_match('/size|fit|measure/', $lower)) {
        return [
            'reply' => 'Match the waist of a jean you already wear. On rigid denim, size up if you are between sizes. Each size on the product page shows how many are left.',
            'products' => [],
        ];
    }

    if (preg_match('/journal|blog|article|story/', $lower)) {
        $posts = published_posts(3);
        $titles = array_map(static function ($post) {
            return $post['title'];
        }, $posts);
        $list = $titles ? implode('; ', $titles) : 'The journal is empty.';
        return ['reply' => 'The journal is in the main menu. Latest notes: ' . $list . '.', 'products' => []];
    }

    $words = preg_split('/\s+/', $lower) ?: [];
    $words = array_values(array_filter($words, static function ($word) {
        return strlen($word) > 2;
    }));
    $query = implode(' ', array_slice($words, 0, 4));
    if ($query !== '') {
        $result = query_products(['q' => $query], 1, 3);
        $products = $result['items'];
    }

    if ($products) {
        $names = array_map(static function ($item) {
            return $item['name'] . ' (' . money((float) $item['price']) . ')';
        }, $products);
        return [
            'reply' => 'I found these in the catalogue: ' . implode('; ', $names) . '. Open any of them below.',
            'products' => $products,
        ];
    }

    return [
        'reply' => 'I can help with shipping, sizes, returns, coupon codes, the journal, and finding a piece by name. Try “selvedge” or “shipping”.',
        'products' => [],
    ];
}
