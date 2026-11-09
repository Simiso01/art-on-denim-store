<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!google_is_configured()) {
    http_response_code(503);
    echo 'Google sign-in needs config/google.local.php on the server.';
    exit;
}

begin_google_login(!empty($_GET['handoff']));
