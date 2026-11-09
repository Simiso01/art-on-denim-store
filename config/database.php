<?php
/**
 * Art on Denim — PDO Database Connection
 */

declare(strict_types=1);

if (basename($_SERVER['PHP_SELF'] ?? '') === 'database.php') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/config.php';

/**
 * Database credentials — local WAMP defaults, overridden by env or hosted detection.
 * Do not commit production credentials to public repositories when possible.
 */
$hostName = $_SERVER['HTTP_HOST'] ?? '';
$isInfinityFree = str_contains($hostName, 'infinityfree')
    || str_contains($hostName, 'site.je')
    || str_contains($hostName, 'rf.gd')
    || str_contains($hostName, 'great-site.net')
    || str_contains($hostName, 'lovestoblog.com');

if (getenv('MYSQLHOST') || getenv('DB_HOST')) {
    define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_PORT', getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'art_on_denim');
    define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('MYSQLPASSWORD') !== false && getenv('MYSQLPASSWORD') !== ''
        ? getenv('MYSQLPASSWORD')
        : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));
} elseif ($isInfinityFree) {
    define('DB_HOST', 'sql213.infinityfree.com');
    define('DB_PORT', '3306');
    define('DB_NAME', 'if0_43397931_aod');
    define('DB_USER', 'if0_43397931');
    define('DB_PASS', 'dHT9FDKepD88HQ');
} else {
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_NAME', 'art_on_denim');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO instance (singleton).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
        error_log('AOD DB Error: ' . $e->getMessage());
        http_response_code(503);
        exit('Service temporarily unavailable. Please try again later.');
    }

    return $pdo;
}
