<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (api_method() !== 'POST') {
    api_error('Method not allowed.', 405);
}

$body = api_json_body();
$name = trim((string) ($body['name'] ?? ''));
$email = trim((string) ($body['email'] ?? ''));
$subject = trim((string) ($body['subject'] ?? ''));
$message = trim((string) ($body['message'] ?? ''));
$errors = [];

if ($name === '' || mb_strlen($name) > 120) {
    $errors['name'] = 'Please enter your name.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email address.';
}
if ($subject === '' || mb_strlen($subject) > 200) {
    $errors['subject'] = 'Please enter a subject.';
}
if ($message === '' || mb_strlen($message) < 10) {
    $errors['message'] = 'Please enter a message (at least 10 characters).';
}
if ($errors !== []) {
    api_error('Validation failed.', 422, ['errors' => $errors]);
}

try {
    $stmt = db()->prepare(
        'INSERT INTO contact_messages (name, email, subject, message, ip_address)
         VALUES (:name, :email, :subject, :message, :ip)'
    );
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    api_response(true, null, 'Message sent. The studio will reply by email.', 201);
} catch (Throwable $e) {
    error_log('AOD API contact: ' . $e->getMessage());
    api_error('Server error.', 500);
}
