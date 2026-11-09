<?php
/**
 * Art on Denim — Logout
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_verify()) {
        logout_user();
        // Restart session for flash messaging after destroy
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
        flash('success', 'You have been signed out.');
    }
}

redirect('index.php');
