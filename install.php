<?php
/**
 * One-click deploy helper for InfinityFree when FTP/zip upload is broken.
 * Visit /install.php once, then delete this file.
 */
declare(strict_types=1);

@set_time_limit(300);
@ini_set('memory_limit', '512M');

$zipUrl = 'https://github.com/Simiso01/art-on-denim/archive/refs/heads/main.zip';
$target = __DIR__;
$tmpZip = $target . DIRECTORY_SEPARATOR . '_aod_deploy.zip';
$tmpDir = $target . DIRECTORY_SEPARATOR . '_aod_extract';

header('Content-Type: text/plain; charset=utf-8');

function fail(string $msg): void
{
    http_response_code(500);
    echo "FAILED: {$msg}\n";
    exit(1);
}

echo "Downloading project zip...\n";
$ctx = stream_context_create([
    'http' => [
        'timeout' => 180,
        'header'  => "User-Agent: AOD-Installer\r\n",
        'follow_location' => 1,
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);

$data = @file_get_contents($zipUrl, false, $ctx);
if ($data === false || strlen($data) < 1000) {
    fail('Could not download zip from GitHub. Check that the repo is public.');
}
if (@file_put_contents($tmpZip, $data) === false) {
    fail('Could not write zip to disk.');
}
echo 'Downloaded ' . strlen($data) . " bytes.\n";

if (!class_exists('ZipArchive')) {
    fail('ZipArchive extension missing on host.');
}

$zip = new ZipArchive();
if ($zip->open($tmpZip) !== true) {
    fail('Could not open downloaded zip.');
}

if (!is_dir($tmpDir) && !mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
    fail('Could not create extract folder.');
}

if (!$zip->extractTo($tmpDir)) {
    $zip->close();
    fail('Extract failed.');
}
$zip->close();
echo "Extracted archive.\n";

$entries = array_values(array_filter(scandir($tmpDir) ?: [], static fn($n) => $n !== '.' && $n !== '..'));
if (count($entries) !== 1 || !is_dir($tmpDir . DIRECTORY_SEPARATOR . $entries[0])) {
    fail('Unexpected zip layout.');
}

$sourceRoot = $tmpDir . DIRECTORY_SEPARATOR . $entries[0];
$skip = ['.git', '.github', '.idea', 'android', 'docs', 'docker', 'Dockerfile', 'railway.toml', '.dockerignore', 'install.php'];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$copied = 0;
foreach ($iterator as $item) {
    $rel = substr($item->getPathname(), strlen($sourceRoot) + 1);
    $rel = str_replace('\\', '/', $rel);
    $top = explode('/', $rel, 2)[0];
    if (in_array($top, $skip, true) || in_array($rel, $skip, true)) {
        continue;
    }

    $dest = $target . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if ($item->isDir()) {
        if (!is_dir($dest) && !mkdir($dest, 0755, true) && !is_dir($dest)) {
            fail("Could not create directory: {$rel}");
        }
        continue;
    }

    $parent = dirname($dest);
    if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
        fail("Could not create parent for: {$rel}");
    }
    if (!copy($item->getPathname(), $dest)) {
        fail("Could not copy: {$rel}");
    }
    $copied++;
}

echo "Copied {$copied} files into htdocs.\n";

// Cleanup
@unlink($tmpZip);
$cleanup = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($cleanup as $file) {
    $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
}
@rmdir($tmpDir);

echo "DONE. Open / and then DELETE install.php for safety.\n";
