<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SystemController extends Controller
{
    public function clearCache(Request $request)
    {
        $results = [];

        try {
            // Clear application cache
            Artisan::call('cache:clear');
            $results[] = 'Application cache cleared';

            // Clear config cache
            Artisan::call('config:clear');
            $results[] = 'Config cache cleared';

            // Clear route cache
            Artisan::call('route:clear');
            $results[] = 'Route cache cleared';

            // Clear view cache
            Artisan::call('view:clear');
            $results[] = 'View cache cleared';

            // Clear OPcache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $results[] = 'OPcache cleared';
            } else {
                $results[] = 'OPcache not available';
            }

            // Optimize (rebuild caches for production)
            Artisan::call('config:cache');
            $results[] = 'Config cached';

            Artisan::call('route:cache');
            $results[] = 'Route cached';

            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully',
                'details' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing cache: ' . $e->getMessage(),
                'details' => $results,
            ], 500);
        }
    }

    public function downloadDatabaseBackup(Request $request)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $connection = config('database.default');
        $db = config("database.connections.{$connection}");

        if (!$db || ($db['driver'] ?? null) !== 'mysql') {
            return response()->json([
                'success' => false,
                'message' => 'Only MySQL backup is supported.',
            ], 422);
        }

        if (!function_exists('exec')) {
            return response()->json([
                'success' => false,
                'message' => 'Server does not allow database backup execution.',
            ], 500);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = storage_path('app/backups');
        $fileName = "database_backup_{$timestamp}.sql";
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;
        $errorPath = $backupDir . DIRECTORY_SEPARATOR . "database_backup_{$timestamp}.err";

        File::ensureDirectoryExists($backupDir);

        $host = $db['host'] ?? '127.0.0.1';
        $port = (string) ($db['port'] ?? '3306');
        $database = $db['database'] ?? '';
        $username = $db['username'] ?? '';
        $password = $db['password'] ?? '';

        if (!$database || !$username) {
            return response()->json([
                'success' => false,
                'message' => 'Database credentials are incomplete.',
            ], 500);
        }

        $commandParts = [
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--lock-tables=false',
            '--host=' . escapeshellarg($host),
            '--port=' . escapeshellarg($port),
            '--user=' . escapeshellarg($username),
        ];

        if ($password !== '') {
            $commandParts[] = '--password=' . escapeshellarg($password);
        }

        $commandParts[] = escapeshellarg($database);

        $command = implode(' ', $commandParts)
            . ' > ' . escapeshellarg($filePath)
            . ' 2> ' . escapeshellarg($errorPath);

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || !File::exists($filePath)) {
            $errorMessage = File::exists($errorPath)
                ? trim((string) File::get($errorPath))
                : 'Unknown backup error.';

            File::delete([$filePath, $errorPath]);

            return response()->json([
                'success' => false,
                'message' => 'Database backup failed.',
                'details' => $errorMessage,
            ], 500);
        }

        File::delete($errorPath);

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/sql',
        ])->deleteFileAfterSend(true);
    }
}
