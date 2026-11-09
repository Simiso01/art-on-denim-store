<?php
/**
 * Art on Denim — Customer authentication
 */

declare(strict_types=1);

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Re-load the logged-in user from DB; clear session if invalid/banned.
 *
 * @return array<string, mixed>|null
 */
function refresh_session_user(): ?array
{
    if (empty($_SESSION['user']['id'])) {
        return null;
    }

    $full = find_user_by_id((int) $_SESSION['user']['id']);
    if (!$full || ($full['status'] ?? '') !== 'active') {
        $_SESSION['user'] = null;
        unset($_SESSION['user']);
        return null;
    }

    $_SESSION['user'] = auth_public_user($full);
    return $_SESSION['user'];
}

function require_login(): void
{
    $user = refresh_session_user();
    if ($user === null) {
        flash('error', 'Please sign in to continue.');
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('pages/account.php');
        redirect('pages/login.php');
    }
}

function require_guest(): void
{
    if (refresh_session_user() !== null) {
        redirect('pages/account.php');
    }
}

/**
 * @param array<string, mixed> $user
 * @return array<string, mixed>
 */
function auth_public_user(array $user): array
{
    return [
        'id'         => (int) $user['id'],
        'first_name' => (string) $user['first_name'],
        'last_name'  => (string) $user['last_name'],
        'email'      => (string) $user['email'],
        'phone'      => $user['phone'] ?? null,
        'role'       => (string) $user['role'],
        'status'     => (string) $user['status'],
    ];
}

/**
 * @param array<string, mixed> $user
 */
function login_user(array $user, bool $regenerate = true): void
{
    if ($regenerate) {
        session_regenerate_id(true);
    }

    $_SESSION['user'] = auth_public_user($user);

    $stmt = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $stmt->execute([':id' => (int) $user['id']]);

    if (function_exists('get_or_create_cart')) {
        get_or_create_cart();
        refresh_cart_count();
    }
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

/**
 * @return array<string, mixed>|null
 */
function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => strtolower(trim($email))]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * @return array<string, mixed>|null
 */
function find_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function login_attempt_key(string $email): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return 'login_attempts_' . hash('sha256', strtolower($email) . '|' . $ip);
}

function login_is_locked(string $email): bool
{
    $key = login_attempt_key($email);
    $data = $_SESSION[$key] ?? null;
    if (!is_array($data)) {
        return false;
    }
    $count = (int) ($data['count'] ?? 0);
    $until = (int) ($data['locked_until'] ?? 0);
    if ($until > time()) {
        return true;
    }
    if ($until > 0 && $until <= time()) {
        unset($_SESSION[$key]);
        return false;
    }
    return false;
}

function login_register_failure(string $email): void
{
    $key = login_attempt_key($email);
    $data = $_SESSION[$key] ?? ['count' => 0, 'locked_until' => 0];
    $data['count'] = (int) $data['count'] + 1;
    if ($data['count'] >= LOGIN_MAX_ATTEMPTS) {
        $data['locked_until'] = time() + LOGIN_LOCKOUT_SECONDS;
    }
    $_SESSION[$key] = $data;
}

function login_clear_failures(string $email): void
{
    unset($_SESSION[login_attempt_key($email)]);
}

/**
 * Attempt login. Returns error message or null on success.
 */
function attempt_login(string $email, string $password): ?string
{
    $email = strtolower(trim($email));

    if ($email === '' || $password === '') {
        return 'Please enter your email and password.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email address.';
    }

    if (login_is_locked($email)) {
        return 'Too many failed attempts. Please try again in 15 minutes.';
    }

    $user = find_user_by_email($email);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        login_register_failure($email);
        return 'Incorrect email or password.';
    }

    if (($user['status'] ?? '') !== 'active') {
        return 'Your account is not active. Please contact support.';
    }

    login_clear_failures($email);
    login_user($user);
    return null;
}

/**
 * @param array<string, string> $data
 * @return array{0: array<string, string>, 1: int|null}
 */
function register_customer(array $data): array
{
    $errors = [];

    $first = trim($data['first_name'] ?? '');
    $last  = trim($data['last_name'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $phone = trim($data['phone'] ?? '');
    $password = $data['password'] ?? '';
    $confirm  = $data['password_confirm'] ?? '';

    if ($first === '' || mb_strlen($first) > 80) {
        $errors['first_name'] = 'Please enter a valid first name.';
    }
    if ($last === '' || mb_strlen($last) > 80) {
        $errors['last_name'] = 'Please enter a valid last name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errors['email'] = 'Invalid email address.';
    } elseif (find_user_by_email($email)) {
        $errors['email'] = 'An account with this email already exists.';
    }
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,30}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (strlen($password) > PASSWORD_MAX_LENGTH) {
        $errors['password'] = 'Password must be ' . PASSWORD_MAX_LENGTH . ' characters or fewer.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must include letters and numbers.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    if ($errors !== []) {
        return [$errors, null];
    }

    $hash = password_hash($password, PASSWORD_ALGO);

    $stmt = db()->prepare(
        'INSERT INTO users (first_name, last_name, email, phone, password_hash, role, status)
         VALUES (:first_name, :last_name, :email, :phone, :password_hash, :role, :status)'
    );

    $stmt->execute([
        ':first_name'    => $first,
        ':last_name'     => $last,
        ':email'         => $email,
        ':phone'         => $phone !== '' ? $phone : null,
        ':password_hash' => $hash,
        ':role'          => 'customer',
        ':status'        => 'active',
    ]);

    return [[], (int) db()->lastInsertId()];
}

/**
 * Change password for logged-in user. Returns field errors.
 *
 * @return array<string, string>
 */
function change_password(int $userId, string $current, string $new, string $confirm): array
{
    $errors = [];
    $user = find_user_by_id($userId);
    if (!$user) {
        return ['form' => 'Account not found.'];
    }

    if (!password_verify($current, $user['password_hash'])) {
        $errors['current_password'] = 'Current password is incorrect.';
    }
    if (strlen($new) < 8) {
        $errors['new_password'] = 'Password must be at least 8 characters.';
    } elseif (strlen($new) > PASSWORD_MAX_LENGTH) {
        $errors['new_password'] = 'Password must be ' . PASSWORD_MAX_LENGTH . ' characters or fewer.';
    } elseif (!preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
        $errors['new_password'] = 'Password must include letters and numbers.';
    }
    if ($new !== $confirm) {
        $errors['new_password_confirm'] = 'Passwords do not match.';
    }

    if ($errors !== []) {
        return $errors;
    }

    $hash = password_hash($new, PASSWORD_ALGO);
    db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')
        ->execute([':hash' => $hash, ':id' => $userId]);

    // Revoke API tokens after password change
    try {
        if (function_exists('api_ensure_tokens_table')) {
            api_ensure_tokens_table();
            db()->prepare('DELETE FROM api_tokens WHERE user_id = :uid')->execute([':uid' => $userId]);
        }
    } catch (Throwable $e) {
        error_log('AOD revoke tokens: ' . $e->getMessage());
    }

    return [];
}
