<?php
/**
 * Admin logout
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    logout_user();
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
    flash('success', 'Signed out of admin.');
}

redirect('admin/login.php');
