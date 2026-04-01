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

        $host     = $db['host'] ?? '127.0.0.1';
        $port     = $db['port'] ?? '3306';
        $database = $db['database'] ?? '';
        $username = $db['username'] ?? '';
        $password = $db['password'] ?? '';

        if (!$database || !$username) {
            return response()->json([
                'success' => false,
                'message' => 'Database credentials are incomplete.',
            ], 500);
        }

        try {
            $pdo = new \PDO(
                "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
                $username,
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ], 500);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName  = "backup_{$database}_{$timestamp}.sql";

        $callback = function () use ($pdo, $database) {
            $out = fopen('php://output', 'w');

            fwrite($out, "-- Database Backup: {$database}\n");
            fwrite($out, "-- Generated: " . now()->toDateTimeString() . "\n\n");
            fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($out, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n");
                $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                $createSql = $create['Create Table'] ?? array_values($create)[1];
                fwrite($out, $createSql . ";\n\n");

                $stmt = $pdo->query("SELECT * FROM `{$table}`");
                $batch = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $values = array_map(function ($val) use ($pdo) {
                        if ($val === null) return 'NULL';
                        return $pdo->quote($val);
                    }, array_values($row));
                    $batch[] = '(' . implode(', ', $values) . ')';

                    if (count($batch) >= 500) {
                        fwrite($out, "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }
                if (!empty($batch)) {
                    fwrite($out, "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $batch) . ";\n");
                }
                fwrite($out, "\n");
            }

            fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control'       => 'no-store, no-cache',
            'X-Accel-Buffering'   => 'no',
        ]);
    }
}
