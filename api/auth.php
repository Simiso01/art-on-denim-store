<?php
/**
 * API — Authentication
 *
 * POST ?action=register
 * POST ?action=login
 * POST ?action=logout   (Bearer required)
 * GET  ?action=me      (Bearer required)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$method = api_method();
$action = $_GET['action'] ?? '';
$body = api_json_body();

try {
    if ($method === 'POST' && $action === 'register') {
        [$errors, $userId] = register_customer([
            'first_name'       => $body['first_name'] ?? '',
            'last_name'        => $body['last_name'] ?? '',
            'email'            => $body['email'] ?? '',
            'phone'            => $body['phone'] ?? '',
            'password'         => $body['password'] ?? '',
            'password_confirm' => $body['password_confirm'] ?? ($body['password'] ?? ''),
        ]);

        if ($errors !== []) {
            api_error('Validation failed.', 422, ['errors' => $errors]);
        }

        $user = find_user_by_id((int) $userId);
        if (!$user) {
            api_error('Registration failed.', 500);
        }

        $token = api_issue_token((int) $user['id'], $body['device_name'] ?? 'android');
        api_response(true, [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in_days' => 30,
            'user' => auth_public_user($user),
        ], 'Account created.', 201);
    }

    if ($method === 'POST' && $action === 'login') {
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            api_error('Email and password are required.', 422);
        }

        $user = find_user_by_email($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            api_error('Incorrect email or password.', 401);
        }
        if (($user['status'] ?? '') !== 'active') {
            api_error('Account is not active.', 403);
        }

        db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')
            ->execute([':id' => (int) $user['id']]);

        $token = api_issue_token((int) $user['id'], $body['device_name'] ?? 'android');
        api_response(true, [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in_days' => 30,
            'user' => auth_public_user($user),
        ], 'Signed in.');
    }

    if ($method === 'POST' && $action === 'logout') {
        $token = api_bearer_token();
        api_require_user();
        api_revoke_token($token);
        api_response(true, null, 'Signed out.');
    }

    if ($method === 'POST' && $action === 'profile') {
        $user = api_require_user();
        $errors = update_customer_profile((int) $user['id'], $body);
        if ($errors !== []) {
            api_error('Validation failed.', 422, ['errors' => $errors]);
        }
        $full = find_user_by_id((int) $user['id']);
        api_response(true, [
            'user' => auth_public_user($full ?: $user),
            'profile' => [
                'address_line1' => $full['address_line1'] ?? null,
                'address_line2' => $full['address_line2'] ?? null,
                'city' => $full['city'] ?? null,
                'province' => $full['province'] ?? null,
                'postal_code' => $full['postal_code'] ?? null,
                'country' => $full['country'] ?? 'South Africa',
            ],
        ], 'Profile saved.');
    }

    if ($method === 'POST' && $action === 'google_handoff') {
        $code = trim((string) ($body['code'] ?? ''));
        $user = consume_google_handoff($code);
        if (!$user) {
            api_error('That Google sign-in link has expired. Try again.', 401);
        }
        $token = api_issue_token((int) $user['id'], $body['device_name'] ?? 'android');
        api_response(true, [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in_days' => 30,
            'user' => auth_public_user($user),
        ], 'Signed in with Google.');
    }

    if ($method === 'GET' && $action === 'me') {
        $user = api_require_user();
        $full = find_user_by_id((int) $user['id']);
        api_response(true, [
            'user' => auth_public_user($full ?: $user),
            'profile' => [
                'address_line1' => $full['address_line1'] ?? null,
                'address_line2' => $full['address_line2'] ?? null,
                'city' => $full['city'] ?? null,
                'province' => $full['province'] ?? null,
                'postal_code' => $full['postal_code'] ?? null,
                'country' => $full['country'] ?? 'South Africa',
            ],
            'unread_notifications' => unread_notification_count((int) $user['id']),
        ]);
    }

    api_error('Unknown auth action. Use register, login, logout, or me.', 404);
} catch (Throwable $e) {
    error_log('AOD API auth: ' . $e->getMessage());
    api_error('Server error.', 500);
}
