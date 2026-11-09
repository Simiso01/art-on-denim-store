<?php
/**
 * API — Order tracking alias
 * Prefer: api/orders.php?action=track&number=...&email=...
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$_GET['action'] = 'track';
require __DIR__ . '/orders.php';
