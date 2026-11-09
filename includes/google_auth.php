<?php
/**
 * Google OpenID Connect. The client secret stays in config/google.local.php
 * and is never sent to the Android app.
 */

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

function google_config(): array
{
    $path = BASE_PATH . '/config/google.local.php';
    if (!is_file($path)) {
        return [];
    }
    $config = include $path;
    return is_array($config) ? $config : [];
}

function google_is_configured(): bool
{
    $config = google_config();
    return ($config['client_id'] ?? '') !== '' && ($config['client_secret'] ?? '') !== '';
}

function ensure_google_schema(): void
{
    $pdo = db();
    $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_sub'")->fetch();
    if (!$column) {
        $pdo->exec('ALTER TABLE users ADD COLUMN google_sub VARCHAR(64) NULL AFTER email');
        $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uq_users_google_sub (google_sub)');
    }
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS oauth_handoff (
            code_hash CHAR(64) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            expires_at DATETIME NOT NULL,
            PRIMARY KEY (code_hash),
            KEY idx_oauth_handoff_user (user_id),
            CONSTRAINT fk_oauth_handoff_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function google_redirect_uri(): string
{
    $config = google_config();
    if (!empty($config['redirect_uri'])) {
        return (string) $config['redirect_uri'];
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https://' : 'http://') . $host . url('pages/google-callback.php');
}

function begin_google_login(bool $handoff): void
{
    if (!google_is_configured()) {
        throw new RuntimeException('Google sign-in is not configured.');
    }
    ensure_google_schema();
    $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    $_SESSION['google_verifier'] = $verifier;
    $_SESSION['google_state'] = bin2hex(random_bytes(16));
    $_SESSION['google_handoff'] = $handoff;

    $config = google_config();
    $query = http_build_query([
        'client_id' => $config['client_id'],
        'redirect_uri' => google_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $_SESSION['google_state'],
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
        'prompt' => 'select_account',
    ]);
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    exit;
}

function finish_google_login(string $code, string $state): array
{
    if (!google_is_configured()) {
        return [null, 'Google sign-in is not configured.'];
    }
    if ($state === '' || !hash_equals((string) ($_SESSION['google_state'] ?? ''), $state)) {
        return [null, 'Google sign-in could not be confirmed. Start again.'];
    }
    $verifier = (string) ($_SESSION['google_verifier'] ?? '');
    unset($_SESSION['google_state'], $_SESSION['google_verifier']);
    if ($verifier === '') {
        return [null, 'Google sign-in expired. Start again.'];
    }

    $config = google_config();
    $payload = http_build_query([
        'code' => $code,
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => google_redirect_uri(),
        'grant_type' => 'authorization_code',
        'code_verifier' => $verifier,
    ]);
    $raw = google_http('https://oauth2.googleapis.com/token', $payload);
    $token = json_decode($raw, true);
    $idToken = is_array($token) ? (string) ($token['id_token'] ?? '') : '';
    if ($idToken === '') {
        return [null, 'Google did not return an identity token.'];
    }

    $info = json_decode(google_http('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken)), true);
    if (!is_array($info) || ($info['aud'] ?? '') !== $config['client_id']) {
        return [null, 'Google identity token was not issued for this store.'];
    }
    if (($info['email_verified'] ?? '') !== 'true' && ($info['email_verified'] ?? false) !== true) {
        return [null, 'Google has not verified that email address.'];
    }

    ensure_google_schema();
    $user = find_or_create_google_user($info);
    return [$user, null];
}

function find_or_create_google_user(array $info): array
{
    $sub = (string) ($info['sub'] ?? '');
    $email = strtolower(trim((string) ($info['email'] ?? '')));
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE google_sub = :sub OR email = :email LIMIT 1');
    $stmt->execute([':sub' => $sub, ':email' => $email]);
    $user = $stmt->fetch();
    if ($user) {
        if (($user['status'] ?? '') !== 'active') {
            throw new RuntimeException('Account is not active.');
        }
        if (empty($user['google_sub'])) {
            $pdo->prepare('UPDATE users SET google_sub = :sub, email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = :id')
                ->execute([':sub' => $sub, ':id' => (int) $user['id']]);
            $user['google_sub'] = $sub;
        }
        return $user;
    }

    $first = trim((string) ($info['given_name'] ?? 'Google'));
    $last = trim((string) ($info['family_name'] ?? 'Customer'));
    $hash = password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
    $insert = $pdo->prepare(
        'INSERT INTO users (first_name, last_name, email, google_sub, password_hash, role, status, email_verified_at)
         VALUES (:first, :last, :email, :sub, :hash, :role, :status, NOW())'
    );
    $insert->execute([
        ':first' => $first !== '' ? $first : 'Google',
        ':last' => $last !== '' ? $last : 'Customer',
        ':email' => $email,
        ':sub' => $sub,
        ':hash' => $hash,
        ':role' => 'customer',
        ':status' => 'active',
    ]);
    $created = find_user_by_id((int) $pdo->lastInsertId());
    if (!$created) {
        throw new RuntimeException('Could not create the Google account.');
    }
    return $created;
}

function issue_google_handoff(int $userId): string
{
    ensure_google_schema();
    $code = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    db()->prepare('INSERT INTO oauth_handoff (code_hash, user_id, expires_at) VALUES (:hash, :user, DATE_ADD(NOW(), INTERVAL 5 MINUTE))')
        ->execute([
            ':hash' => hash('sha256', $code),
            ':user' => $userId,
        ]);
    return $code;
}

function consume_google_handoff(string $code): ?array
{
    if ($code === '') {
        return null;
    }
    ensure_google_schema();
    $hash = hash('sha256', $code);
    $stmt = db()->prepare(
        'SELECT user_id FROM oauth_handoff WHERE code_hash = :hash AND expires_at >= NOW() LIMIT 1'
    );
    $stmt->execute([':hash' => $hash]);
    $row = $stmt->fetch();
    db()->prepare('DELETE FROM oauth_handoff WHERE code_hash = :hash')->execute([':hash' => $hash]);
    if (!$row) {
        return null;
    }
    return find_user_by_id((int) $row['user_id']);
}

function google_http(string $url, ?string $body = null): string
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    }
    $raw = curl_exec($ch);
    curl_close($ch);
    return is_string($raw) ? $raw : '';
}
