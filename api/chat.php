<?php
/**
 * Shopping assistant — answers from Art on Denim catalogue and policy.
 * POST JSON { "message": "..." }
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (api_method() !== 'POST') {
    api_error('Method not allowed.', 405);
}

$hits = (int) ($_SESSION['chat_hits'] ?? 0);
if ($hits > 40) {
    api_error('The assistant is pausing for a moment. Try again shortly.', 429);
}
$_SESSION['chat_hits'] = $hits + 1;

$body = api_json_body();
$message = trim((string) ($body['message'] ?? ''));

try {
    $result = studio_chat_reply($message);
    $cards = [];
    foreach ($result['products'] as $product) {
        $cards[] = [
            'id' => (int) $product['id'],
            'name' => (string) $product['name'],
            'price' => money((float) $product['price']),
            'url' => url('pages/product.php?id=' . (int) $product['id']),
        ];
    }
    api_response(true, [
        'reply' => $result['reply'],
        'products' => $cards,
    ]);
} catch (Throwable $e) {
    error_log('AOD chat: ' . $e->getMessage());
    api_error('The assistant could not answer just now.', 500);
}
