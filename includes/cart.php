<?php
/**
 * Art on Denim — Shopping cart (database-backed)
 */

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

function cart_session_key(): string
{
    if (empty($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['cart_session_id'];
}

/**
 * Get or create the current cart row.
 */
function get_or_create_cart(): int
{
    $pdo = db();
    $user = current_user();

    if ($user) {
        $stmt = $pdo->prepare(
            'SELECT c.id
             FROM cart c
             LEFT JOIN cart_items i ON i.cart_id = c.id
             WHERE c.user_id = :uid
             GROUP BY c.id
             ORDER BY COUNT(i.id) DESC, c.updated_at DESC, c.id DESC'
        );
        $stmt->execute([':uid' => (int) $user['id']]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($ids) {
            $primary = (int) $ids[0];
            foreach (array_slice($ids, 1) as $extraId) {
                absorb_cart((int) $extraId, $primary);
            }
            merge_guest_cart_into_user($primary);
            return $primary;
        }
    }

    $sessionId = cart_session_key();
    $stmt = $pdo->prepare('SELECT id FROM cart WHERE session_id = :sid AND user_id IS NULL LIMIT 1');
    $stmt->execute([':sid' => $sessionId]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO cart (user_id, session_id) VALUES (:uid, :sid)'
    );
    $stmt->execute([
        ':uid' => $user ? (int) $user['id'] : null,
        ':sid' => $user ? null : $sessionId,
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Merge anonymous cart into authenticated cart after login.
 */
function absorb_cart(int $fromId, int $intoId): void
{
    if ($fromId < 1 || $fromId === $intoId) {
        return;
    }
    $pdo = db();
    $items = $pdo->prepare('SELECT * FROM cart_items WHERE cart_id = :cid');
    $items->execute([':cid' => $fromId]);
    foreach ($items->fetchAll() as $item) {
        move_cart_line($intoId, $item);
    }
    $pdo->prepare('DELETE FROM cart WHERE id = :id')->execute([':id' => $fromId]);
}

function move_cart_line(int $cartId, array $item): void
{
    $pdo = db();
    $check = $pdo->prepare(
        'SELECT id, quantity FROM cart_items
         WHERE cart_id = :cid AND product_id = :pid AND size = :size LIMIT 1'
    );
    $check->execute([
        ':cid' => $cartId,
        ':pid' => $item['product_id'],
        ':size' => $item['size'],
    ]);
    $existing = $check->fetch();
    $available = get_available_stock((int) $item['product_id'], (string) $item['size']);
    $existingQty = is_array($existing) ? (int) $existing['quantity'] : 0;
    $mergedQty = (int) $item['quantity'] + $existingQty;
    if ($available > 0) {
        $mergedQty = min($mergedQty, $available);
    }
    if ($existing) {
        $pdo->prepare('UPDATE cart_items SET quantity = :qty WHERE id = :id')->execute([
            ':qty' => max(1, $mergedQty),
            ':id' => (int) $existing['id'],
        ]);
    } else {
        $pdo->prepare(
            'INSERT INTO cart_items (cart_id, product_id, size, quantity, unit_price)
             VALUES (:cid, :pid, :size, :qty, :price)'
        )->execute([
            ':cid' => $cartId,
            ':pid' => $item['product_id'],
            ':size' => $item['size'],
            ':qty' => max(1, $mergedQty),
            ':price' => $item['unit_price'],
        ]);
    }
}

function merge_guest_cart_into_user(int $userCartId): void
{
    if (empty($_SESSION['cart_session_id'])) {
        return;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM cart WHERE session_id = :sid AND user_id IS NULL LIMIT 1');
    $stmt->execute([':sid' => $_SESSION['cart_session_id']]);
    $guestCartId = $stmt->fetchColumn();

    if (!$guestCartId || (int) $guestCartId === $userCartId) {
        return;
    }

    $guestCartId = (int) $guestCartId;
    $items = $pdo->prepare('SELECT * FROM cart_items WHERE cart_id = :cid');
    $items->execute([':cid' => $guestCartId]);

    foreach ($items->fetchAll() as $item) {
        move_cart_line($userCartId, $item);
    }

    $pdo->prepare('DELETE FROM cart WHERE id = :id')->execute([':id' => $guestCartId]);
    unset($_SESSION['cart_session_id']);
    refresh_cart_count();
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_cart_items(): array
{
    $cartId = get_or_create_cart();

    $stmt = db()->prepare(
        "SELECT ci.*, p.name, p.slug, p.sku, p.status,
                b.name AS brand_name,
                (
                    SELECT pi.image_path FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.is_primary DESC, pi.sort_order ASC
                    LIMIT 1
                ) AS image
         FROM cart_items ci
         INNER JOIN products p ON p.id = ci.product_id
         LEFT JOIN brands b ON b.id = p.brand_id
         WHERE ci.cart_id = :cid
         ORDER BY ci.id DESC"
    );
    $stmt->execute([':cid' => $cartId]);
    return $stmt->fetchAll();
}

function refresh_cart_count(): void
{
    $pdo = db();
    $user = current_user();
    $cartId = null;

    if ($user) {
        $stmt = $pdo->prepare('SELECT id FROM cart WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => (int) $user['id']]);
        $cartId = $stmt->fetchColumn() ?: null;
    } elseif (!empty($_SESSION['cart_session_id'])) {
        $stmt = $pdo->prepare('SELECT id FROM cart WHERE session_id = :sid AND user_id IS NULL LIMIT 1');
        $stmt->execute([':sid' => $_SESSION['cart_session_id']]);
        $cartId = $stmt->fetchColumn() ?: null;
    }

    if (!$cartId) {
        $_SESSION['cart_count'] = 0;
        return;
    }

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = :cid');
    $stmt->execute([':cid' => (int) $cartId]);
    $_SESSION['cart_count'] = (int) $stmt->fetchColumn();
}

/**
 * Add item to cart. Returns error message or null.
 */
function cart_add(int $productId, string $size, int $quantity): ?string
{
    $size = trim($size);
    $quantity = max(1, $quantity);

    if ($size === '') {
        return 'Please select a size before adding this product to your cart.';
    }

    $product = get_product_by_id($productId);
    if (!$product) {
        return 'Product not found.';
    }

    $available = get_available_stock($productId, $size);
    if ($available < 1) {
        return 'Your requested quantity is not currently available.';
    }

    $cartId = get_or_create_cart();
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT id, quantity FROM cart_items
         WHERE cart_id = :cid AND product_id = :pid AND size = :size LIMIT 1'
    );
    $stmt->execute([':cid' => $cartId, ':pid' => $productId, ':size' => $size]);
    $existing = $stmt->fetch();

    $newQty = $quantity + ($existing ? (int) $existing['quantity'] : 0);
    if ($newQty > $available) {
        return 'Your requested quantity is not currently available.';
    }

    if ($existing) {
        $upd = $pdo->prepare('UPDATE cart_items SET quantity = :qty, unit_price = :price WHERE id = :id');
        $upd->execute([
            ':qty'   => $newQty,
            ':price' => $product['price'],
            ':id'    => (int) $existing['id'],
        ]);
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO cart_items (cart_id, product_id, size, quantity, unit_price)
             VALUES (:cid, :pid, :size, :qty, :price)'
        );
        $ins->execute([
            ':cid'   => $cartId,
            ':pid'   => $productId,
            ':size'  => $size,
            ':qty'   => $quantity,
            ':price' => $product['price'],
        ]);
    }

    refresh_cart_count();
    return null;
}

function cart_update(int $itemId, int $quantity): ?string
{
    $cartId = get_or_create_cart();
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM cart_items WHERE id = :id AND cart_id = :cid LIMIT 1');
    $stmt->execute([':id' => $itemId, ':cid' => $cartId]);
    $item = $stmt->fetch();

    if (!$item) {
        return 'Cart item not found.';
    }

    if ($quantity < 1) {
        return cart_remove($itemId);
    }

    $available = get_available_stock((int) $item['product_id'], $item['size']);
    if ($quantity > $available) {
        return 'Your requested quantity is not currently available.';
    }

    $upd = $pdo->prepare('UPDATE cart_items SET quantity = :qty WHERE id = :id');
    $upd->execute([':qty' => $quantity, ':id' => $itemId]);
    refresh_cart_count();
    return null;
}

function cart_remove(int $itemId): ?string
{
    $cartId = get_or_create_cart();
    $stmt = db()->prepare('DELETE FROM cart_items WHERE id = :id AND cart_id = :cid');
    $stmt->execute([':id' => $itemId, ':cid' => $cartId]);
    refresh_cart_count();
    return null;
}

function cart_clear(): void
{
    $cartId = get_or_create_cart();
    db()->prepare('DELETE FROM cart_items WHERE cart_id = :cid')->execute([':cid' => $cartId]);
    refresh_cart_count();
}

/**
 * @return array{subtotal: float, shipping: float, total: float, items: array}
 */
function cart_totals(): array
{
    $items = get_cart_items();
    $subtotal = 0.0;

    foreach ($items as $item) {
        $subtotal += (float) $item['unit_price'] * (int) $item['quantity'];
    }

    $coupon = function_exists('applied_coupon') ? applied_coupon() : null;
    $discount = ($coupon && function_exists('coupon_discount_amount'))
        ? coupon_discount_amount($coupon, $subtotal)
        : 0.0;
    $merchandise = max(0, $subtotal - $discount);

    $shipping = $merchandise <= 0
        ? 0.0
        : ($merchandise >= FREE_SHIPPING_THRESHOLD ? 0.0 : (float) DEFAULT_SHIPPING);

    return [
        'items'    => $items,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'coupon'   => $coupon,
        'shipping' => $shipping,
        'total'    => $merchandise + $shipping,
    ];
}
