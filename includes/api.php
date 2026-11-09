<?php
/**
 * Art on Denim — API helpers (JSON + bearer tokens)
 * Android / external clients must use these endpoints — never MySQL directly.
 */

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

function api_headers(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
}

/**
 * @param mixed $data
 */
function api_response(bool $success, $data = null, string $message = '', int $status = 200): void
{
    api_headers();
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_error(string $message, int $status = 400, $data = null): void
{
    api_response(false, $data, $message, $status);
}

function api_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

/**
 * @return array<string, mixed>
 */
function api_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return $_POST ?: [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : ($_POST ?: []);
}

function api_ensure_tokens_table(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec(
        "CREATE TABLE IF NOT EXISTS api_tokens (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            device_name VARCHAR(120) DEFAULT NULL,
            expires_at DATETIME NOT NULL,
            last_used_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_api_token_hash (token_hash),
            KEY idx_api_tokens_user (user_id),
            KEY idx_api_tokens_expires (expires_at),
            CONSTRAINT fk_api_tokens_user
              FOREIGN KEY (user_id) REFERENCES users (id)
              ON DELETE CASCADE ON UPDATE CASCADE
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $ready = true;
}

function api_bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $header = $value;
                break;
            }
        }
    }

    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }

    // Fallback for environments that strip Authorization
    $alt = $_SERVER['HTTP_X_AOD_TOKEN'] ?? '';
    return $alt !== '' ? $alt : null;
}

/**
 * Issue a new API token for a user. Returns plaintext token (show once).
 */
function api_issue_token(int $userId, ?string $deviceName = null): string
{
    api_ensure_tokens_table();

    $plain = bin2hex(random_bytes(32));
    $hash = hash('sha256', $plain);
    $expires = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 30)); // 30 days

    $stmt = db()->prepare(
        'INSERT INTO api_tokens (user_id, token_hash, device_name, expires_at)
         VALUES (:uid, :hash, :device, :expires)'
    );
    $stmt->execute([
        ':uid'     => $userId,
        ':hash'    => $hash,
        ':device'  => $deviceName,
        ':expires' => $expires,
    ]);

    return $plain;
}

function api_revoke_token(?string $plainToken): void
{
    if ($plainToken === null || $plainToken === '') {
        return;
    }
    api_ensure_tokens_table();
    $stmt = db()->prepare('DELETE FROM api_tokens WHERE token_hash = :hash');
    $stmt->execute([':hash' => hash('sha256', $plainToken)]);
}

/**
 * Authenticate via bearer token. Sets $_SESSION['user'] for reuse of existing helpers.
 *
 * @return array<string, mixed>
 */
function api_require_user(): array
{
    api_ensure_tokens_table();
    $token = api_bearer_token();
    if ($token === null) {
        api_error('Missing bearer token.', 401);
    }

    $stmt = db()->prepare(
        'SELECT t.id AS token_id, t.expires_at, u.*
         FROM api_tokens t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = :hash
         LIMIT 1'
    );
    $stmt->execute([':hash' => hash('sha256', $token)]);
    $row = $stmt->fetch();

    if (!$row) {
        api_error('Invalid or expired token.', 401);
    }

    if (strtotime((string) $row['expires_at']) < time()) {
        db()->prepare('DELETE FROM api_tokens WHERE id = :id')->execute([':id' => $row['token_id']]);
        api_error('Invalid or expired token.', 401);
    }

    if (($row['status'] ?? '') !== 'active') {
        api_error('Account is not active.', 403);
    }

    db()->prepare('UPDATE api_tokens SET last_used_at = NOW() WHERE id = :id')
        ->execute([':id' => $row['token_id']]);

    $user = auth_public_user($row);
    if (empty($_SESSION['user']) || (int) ($_SESSION['user']['id'] ?? 0) !== (int) $user['id']) {
        session_regenerate_id(true);
    }
    $_SESSION['user'] = $user;
    $_SESSION['api_auth'] = true;
    return $user;
}

/**
 * Optional auth — returns user or null (does not exit).
 *
 * @return array<string, mixed>|null
 */
function api_optional_user(): ?array
{
    $existing = current_user();
    $token = api_bearer_token();
    if ($token === null) {
        return $existing;
    }

    api_ensure_tokens_table();
    $stmt = db()->prepare(
        'SELECT t.id AS token_id, t.expires_at, u.*
         FROM api_tokens t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = :hash
         LIMIT 1'
    );
    $stmt->execute([':hash' => hash('sha256', $token)]);
    $row = $stmt->fetch();

    if (!$row || strtotime((string) $row['expires_at']) < time() || ($row['status'] ?? '') !== 'active') {
        return $existing;
    }

    db()->prepare('UPDATE api_tokens SET last_used_at = NOW() WHERE id = :id')
        ->execute([':id' => $row['token_id']]);

    $user = auth_public_user($row);
    if (empty($_SESSION['user']) || (int) ($_SESSION['user']['id'] ?? 0) !== (int) $user['id']) {
        session_regenerate_id(true);
    }
    $_SESSION['user'] = $user;
    $_SESSION['api_auth'] = true;
    return $user;
}

/**
 * Absolute-ish product image URL for mobile clients.
 */
function api_image_url(?string $path): string
{
    $rel = product_image_url($path);
    if (strncmp($rel, 'http://', 7) === 0 || strncmp($rel, 'https://', 8) === 0) {
        return $rel;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https://' : 'http://') . $host . $rel;
}

/**
 * @param array<string, mixed> $product
 * @return array<string, mixed>
 */
function api_format_product(array $product, bool $detailed = false): array
{
    $compare = isset($product['compare_at_price']) ? (float) $product['compare_at_price'] : null;
    $data = [
        'id'               => (int) $product['id'],
        'sku'              => $product['sku'],
        'name'             => $product['name'],
        'slug'             => $product['slug'],
        'short_description'=> $product['short_description'] ?? null,
        'price'            => (float) $product['price'],
        'compare_at_price' => $compare,
        'currency'         => CURRENCY_CODE,
        'brand'            => $product['brand_name'] ?? null,
        'brand_slug'       => $product['brand_slug'] ?? null,
        'category'         => $product['category_name'] ?? null,
        'category_slug'    => $product['category_slug'] ?? null,
        'gender'           => $product['gender'] ?? 'unisex',
        'is_featured'      => (bool) ($product['is_featured'] ?? false),
        'is_new'           => (bool) ($product['is_new'] ?? false),
        'is_on_sale'       => (bool) ($product['is_on_sale'] ?? false),
        'is_aod_collection'=> (bool) ($product['is_aod_collection'] ?? false),
        'image'            => api_image_url($product['image'] ?? null),
    ];

    if ($detailed) {
        $data['description'] = $product['description'] ?? null;
        $images = get_product_images((int) $product['id']);
        $data['images'] = array_map(static function ($img) {
            return [
                'url' => api_image_url($img['image_path']),
                'alt' => $img['alt_text'] ?? null,
                'is_primary' => (bool) $img['is_primary'],
            ];
        }, $images);

        $inventory = get_product_inventory((int) $product['id']);
        $data['sizes'] = array_map(static function ($row) {
            $available = max(0, (int) $row['quantity'] - (int) $row['reserved']);
            return [
                'size'      => $row['size'],
                'available' => $available,
                'in_stock'  => $available > 0,
            ];
        }, $inventory);
    }

    return $data;
}

function unread_notification_count(int $userId): int
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0'
    );
    $stmt->execute([':uid' => $userId]);
    return (int) $stmt->fetchColumn();
}
