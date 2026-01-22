<?php
/**
 * Emergency cache clear script
 * Access: /clear-cache.php?key=YOUR_SECRET_KEY
 * This bypasses Laravel routing when route cache is stale
 * Uses Laravel Artisan facade instead of exec() for compatibility
 */

// Secret key for security
$secretKey = 'bangalio2024clear';

// Verify key
if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Invalid key']));
}

$results = [];

// Clear OPcache first (before loading Laravel)
if (function_exists('opcache_reset')) {
    opcache_reset();
    $results[] = 'OPcache cleared';
} else {
    $results[] = 'OPcache not available';
}

try {
    // Bootstrap Laravel
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Clear caches using Artisan
    $commands = [
        'cache:clear',
        'config:clear',
        'route:clear',
        'view:clear',
    ];

    foreach ($commands as $cmd) {
        try {
            Illuminate\Support\Facades\Artisan::call($cmd);
            $output = Illuminate\Support\Facades\Artisan::output();
            $results[] = $cmd . ' - OK: ' . trim($output);
        } catch (Exception $e) {
            $results[] = $cmd . ' - Failed: ' . $e->getMessage();
        }
    }

    // Rebuild caches
    $rebuildCommands = [
        'config:cache',
        'route:cache',
    ];

    foreach ($rebuildCommands as $cmd) {
        try {
            Illuminate\Support\Facades\Artisan::call($cmd);
            $output = Illuminate\Support\Facades\Artisan::output();
            $results[] = $cmd . ' - OK: ' . trim($output);
        } catch (Exception $e) {
            $results[] = $cmd . ' - Failed: ' . $e->getMessage();
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Cache cleared successfully',
        'results' => $results,
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'results' => $results,
    ]);
}
