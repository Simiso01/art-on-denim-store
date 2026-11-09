<?php
/**
 * Art on Denim — Wishlist (authenticated users)
 */

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

function wishlist_add(int $userId, int $productId): ?string
{
    $product = get_product_by_id($productId);
    if (!$product) {
        return 'Product not found.';
    }

    $check = db()->prepare(
        'SELECT id FROM wishlist WHERE user_id = :uid AND product_id = :pid LIMIT 1'
    );
    $check->execute([':uid' => $userId, ':pid' => $productId]);
    if ($check->fetch()) {
        return null; // already saved
    }

    $stmt = db()->prepare(
        'INSERT INTO wishlist (user_id, product_id) VALUES (:uid, :pid)'
    );
    $stmt->execute([':uid' => $userId, ':pid' => $productId]);
    return null;
}

function wishlist_remove(int $userId, int $productId): void
{
    $stmt = db()->prepare(
        'DELETE FROM wishlist WHERE user_id = :uid AND product_id = :pid'
    );
    $stmt->execute([':uid' => $userId, ':pid' => $productId]);
}

function wishlist_has(int $userId, int $productId): bool
{
    $stmt = db()->prepare(
        'SELECT id FROM wishlist WHERE user_id = :uid AND product_id = :pid LIMIT 1'
    );
    $stmt->execute([':uid' => $userId, ':pid' => $productId]);
    return (bool) $stmt->fetch();
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_wishlist_items(int $userId): array
{
    $stmt = db()->prepare(
        "SELECT w.id AS wishlist_id, w.created_at AS wishlisted_at,
                p.*, b.name AS brand_name, b.slug AS brand_slug,
                (
                    SELECT pi.image_path FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_primary DESC, pi.sort_order ASC
                    LIMIT 1
                ) AS image
         FROM wishlist w
         INNER JOIN products p ON p.id = w.product_id
         LEFT JOIN brands b ON b.id = p.brand_id
         WHERE w.user_id = :uid AND p.status = 'active'
         ORDER BY w.created_at DESC"
    );
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}
