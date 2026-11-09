<?php
/**
 * Art on Denim — Bootstrap (load on every page)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/wishlist.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/admin-helpers.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/studio.php';
require_once __DIR__ . '/google_auth.php';

try {
    ensure_studio_schema();
} catch (Throwable $e) {
    error_log('AOD studio schema: ' . $e->getMessage());
}
