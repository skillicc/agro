<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

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
}
