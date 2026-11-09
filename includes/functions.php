<?php
/**
 * Art on Denim — Shared helper functions
 */

declare(strict_types=1);

if (basename($_SERVER['PHP_SELF'] ?? '') === 'functions.php') {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Escape output for HTML context.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Build an application URL.
 */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    if ($path === '') {
        return rtrim(BASE_URL, '/') . '/';
    }
    return rtrim(BASE_URL, '/') . '/' . $path;
}

/**
 * Build an asset URL.
 */
function asset(string $path): string
{
    return url(ltrim($path, '/'));
}

/**
 * Redirect and exit.
 */
function redirect(string $path): void
{
    $isAbsolute = strncmp($path, 'http://', 7) === 0 || strncmp($path, 'https://', 8) === 0;
    header('Location: ' . ($isAbsolute ? $path : url($path)));
    exit;
}

/**
 * Format ZAR currency.
 */
function money($amount): string
{
    return CURRENCY_SYMBOL . number_format((float) $amount, 2, '.', ',');
}

/**
 * Flash a session message.
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieve and clear flash messages.
 *
 * @return array<int, array{type:string,message:string}>
 */
function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Current authenticated user (customer/admin) or null.
 *
 * @return array<string, mixed>|null
 */
function current_user(): ?array
{
    if (empty($_SESSION['user'])) {
        return null;
    }
    return $_SESSION['user'];
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null
        && ($user['role'] ?? '') === 'admin'
        && ($user['status'] ?? '') === 'active';
}

/**
 * Active navigation helper.
 */
function is_active(string $needle): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($uri, $needle) !== false ? 'is-active' : '';
}

/**
 * Old input helper for forms.
 */
function old(string $key, string $default = ''): string
{
    return e((string) ($_SESSION['_old'][$key] ?? $default));
}

function store_old(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['csrf_token']);
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

/**
 * Create a URL-friendly slug.
 */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_categories(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM categories';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';
    return db()->query($sql)->fetchAll();
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_brands_list(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM brands';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY name ASC';
    return db()->query($sql)->fetchAll();
}

/**
 * Catalogue query with filters, sort, and pagination.
 *
 * @param array<string, mixed> $filters
 * @return array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
 */
function query_products(array $filters = [], int $page = 1, int $perPage = PRODUCTS_PER_PAGE): array
{
    $where = ["p.status = 'active'"];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = '(p.name LIKE :q OR p.short_description LIKE :q OR p.description LIKE :q OR b.name LIKE :q)';
        $params[':q'] = '%' . $filters['q'] . '%';
    }
    if (!empty($filters['category'])) {
        $where[] = 'c.slug = :category';
        $params[':category'] = $filters['category'];
    }
    if (!empty($filters['brand'])) {
        $where[] = 'b.slug = :brand';
        $params[':brand'] = $filters['brand'];
    }
    if (!empty($filters['is_new'])) {
        $where[] = 'p.is_new = 1';
    }
    if (!empty($filters['is_on_sale'])) {
        $where[] = 'p.is_on_sale = 1';
    }
    if (!empty($filters['is_aod_collection']) || (!empty($filters['collection']) && $filters['collection'] === 'aod')) {
        $where[] = 'p.is_aod_collection = 1';
    }
    if (!empty($filters['gender']) && in_array($filters['gender'], ['men', 'women', 'unisex'], true)) {
        $where[] = 'p.gender = :gender';
        $params[':gender'] = $filters['gender'];
    }
    if (isset($filters['min_price']) && $filters['min_price'] !== '' && is_numeric($filters['min_price'])) {
        $where[] = 'p.price >= :min_price';
        $params[':min_price'] = (float) $filters['min_price'];
    }
    if (isset($filters['max_price']) && $filters['max_price'] !== '' && is_numeric($filters['max_price'])) {
        $where[] = 'p.price <= :max_price';
        $params[':max_price'] = (float) $filters['max_price'];
    }
    if (!empty($filters['size'])) {
        $where[] = "EXISTS (
            SELECT 1 FROM inventory inv
            WHERE inv.product_id = p.id AND inv.size = :size AND (inv.quantity - inv.reserved) > 0
        )";
        $params[':size'] = (string) $filters['size'];
    }

    $sort = $filters['sort'] ?? 'newest';
    switch ($sort) {
        case 'price_asc':
            $orderBy = 'p.price ASC';
            break;
        case 'price_desc':
            $orderBy = 'p.price DESC';
            break;
        case 'name_asc':
            $orderBy = 'p.name ASC';
            break;
        case 'featured':
            $orderBy = 'p.is_featured DESC, p.created_at DESC';
            break;
        default:
            $orderBy = 'p.created_at DESC';
    }

    $whereSql = implode(' AND ', $where);
    $page = max(1, $page);
    $perPage = max(1, min(48, $perPage));
    $offset = ($page - 1) * $perPage;

    $countStmt = db()->prepare(
        "SELECT COUNT(*) FROM products p
         LEFT JOIN brands b ON b.id = p.brand_id
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE {$whereSql}"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sql = "SELECT
                p.*,
                b.name AS brand_name,
                b.slug AS brand_slug,
                c.name AS category_name,
                c.slug AS category_slug,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC
                    LIMIT 1
                ) AS image
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE {$whereSql}
            ORDER BY {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    return [
        'items'       => $items,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => max(1, (int) ceil($total / $perPage)),
    ];
}

/**
 * @return array<string, mixed>|null
 */
function get_product_by_id(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT p.*, b.name AS brand_name, b.slug AS brand_slug,
                c.name AS category_name, c.slug AS category_slug
         FROM products p
         LEFT JOIN brands b ON b.id = p.brand_id
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.id = :id AND p.status = 'active'
         LIMIT 1"
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_product_images(int $productId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM product_images
         WHERE product_id = :id
         ORDER BY is_primary DESC, sort_order ASC, id ASC'
    );
    $stmt->execute([':id' => $productId]);
    return $stmt->fetchAll();
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_product_inventory(int $productId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM inventory
         WHERE product_id = :id
         ORDER BY id ASC'
    );
    $stmt->execute([':id' => $productId]);
    return $stmt->fetchAll();
}

/**
 * Available quantity for a product size.
 */
function get_available_stock(int $productId, string $size): int
{
    $stmt = db()->prepare(
        'SELECT quantity, reserved FROM inventory
         WHERE product_id = :pid AND size = :size
         LIMIT 1'
    );
    $stmt->execute([':pid' => $productId, ':size' => $size]);
    $row = $stmt->fetch();
    if (!$row) {
        return 0;
    }
    return max(0, (int) $row['quantity'] - (int) $row['reserved']);
}

/**
 * Fetch active promotions by placement.
 *
 * @return array<int, array<string, mixed>>
 */
function get_promotions(string $placement, int $limit = 5): array
{
    $sql = "SELECT * FROM promotions
            WHERE is_active = 1
              AND placement = :placement
              AND (starts_at IS NULL OR starts_at <= NOW())
              AND (ends_at IS NULL OR ends_at >= NOW())
            ORDER BY sort_order ASC, id DESC
            LIMIT :lim";

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':placement', $placement, PDO::PARAM_STR);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Fetch products with optional filters for homepage sections.
 *
 * @param array<string, mixed> $filters
 * @return array<int, array<string, mixed>>
 */
function get_products(array $filters = [], int $limit = 8): array
{
    $where = ["p.status = 'active'"];
    $params = [];

    if (!empty($filters['is_featured'])) {
        $where[] = 'p.is_featured = 1';
    }
    if (!empty($filters['is_new'])) {
        $where[] = 'p.is_new = 1';
    }
    if (!empty($filters['is_on_sale'])) {
        $where[] = 'p.is_on_sale = 1';
    }
    if (!empty($filters['is_aod_collection'])) {
        $where[] = 'p.is_aod_collection = 1';
    }
    if (!empty($filters['brand_slug'])) {
        $where[] = 'b.slug = :brand_slug';
        $params[':brand_slug'] = $filters['brand_slug'];
    }
    if (!empty($filters['brand_id'])) {
        $where[] = 'p.brand_id = :brand_id';
        $params[':brand_id'] = (int) $filters['brand_id'];
    }

    $sql = "SELECT
                p.*,
                b.name AS brand_name,
                b.slug AS brand_slug,
                c.name AS category_name,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC
                    LIMIT 1
                ) AS image
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.created_at DESC
            LIMIT :lim";

    $stmt = db()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Featured brands.
 *
 * @return array<int, array<string, mixed>>
 */
function get_featured_brands(int $limit = 6): array
{
    $stmt = db()->prepare(
        "SELECT * FROM brands
         WHERE is_active = 1 AND is_featured = 1
         ORDER BY name ASC
         LIMIT :lim"
    );
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Cart item count for header badge.
 */
function cart_count(): int
{
    if (isset($_SESSION['cart_count'])) {
        return (int) $_SESSION['cart_count'];
    }

    try {
        refresh_cart_count();
    } catch (Throwable $e) {
        $_SESSION['cart_count'] = 0;
    }

    return (int) ($_SESSION['cart_count'] ?? 0);
}

/**
 * Resolve product image path to a usable URL.
 */
function product_image_url(?string $path): string
{
    if ($path === null || $path === '') {
        return asset('assets/images/products/placeholder-denim.svg');
    }
    if (strncmp($path, 'http://', 7) === 0 || strncmp($path, 'https://', 8) === 0) {
        return $path;
    }
    return asset($path);
}
