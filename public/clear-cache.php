<?php
/**
 * Emergency cache clear script
 * Access: /clear-cache.php?key=YOUR_SECRET_KEY
 * This bypasses Laravel routing when route cache is stale
 */

// Secret key for security - change this!
$secretKey = 'bangalio2024clear';

// Verify key
if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid key']));
}

// Change to Laravel root
chdir(dirname(__DIR__));

$results = [];

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    $results[] = 'OPcache cleared';
} else {
    $results[] = 'OPcache not available';
}

// Run artisan commands
$commands = [
    'php artisan cache:clear',
    'php artisan config:clear',
    'php artisan route:clear',
    'php artisan view:clear',
    'php artisan config:cache',
    'php artisan route:cache',
];

foreach ($commands as $cmd) {
    $output = [];
    $returnCode = 0;
    exec($cmd . ' 2>&1', $output, $returnCode);
    $results[] = $cmd . ' - ' . ($returnCode === 0 ? 'OK' : 'Failed: ' . implode(' ', $output));
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Cache cleared',
    'results' => $results,
]);
