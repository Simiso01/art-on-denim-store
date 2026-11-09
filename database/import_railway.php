<?php
/**
 * One-shot schema + seed import for Railway MySQL.
 * Runs when IMPORT_SCHEMA=1 on container start.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$pdo = db();

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if (count($tables) > 0) {
    fwrite(STDOUT, "Schema already present (" . count($tables) . " tables). Skipping import.\n");
    exit(0);
}

$sqlFile = __DIR__ . '/art_on_denim.sql';
if (!is_readable($sqlFile)) {
    fwrite(STDERR, "Missing schema file: {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Could not read schema file.\n");
    exit(1);
}

// Railway already provides a database — drop CREATE/USE statements.
$sql = preg_replace('/CREATE DATABASE IF NOT EXISTS[^;]+;/i', '', $sql);
$sql = preg_replace('/USE\s+`?art_on_denim`?\s*;/i', '', $sql);

fwrite(STDOUT, "Importing schema...\n");
$pdo->exec($sql);

// Apply migrations if present
$migrations = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($migrations);
foreach ($migrations as $migration) {
    $mSql = file_get_contents($migration);
    if ($mSql === false || trim($mSql) === '') {
        continue;
    }
    try {
        $pdo->exec($mSql);
        fwrite(STDOUT, "Applied migration: " . basename($migration) . "\n");
    } catch (Throwable $e) {
        fwrite(STDOUT, "Migration skip/warn " . basename($migration) . ": " . $e->getMessage() . "\n");
    }
}

fwrite(STDOUT, "Import complete.\n");
