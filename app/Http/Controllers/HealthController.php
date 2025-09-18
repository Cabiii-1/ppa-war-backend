<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    /**
     * Basic health check endpoint
     */
    public function basic()
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'version' => '1.0.0'
        ], 200);
    }

    /**
     * Comprehensive health check including database connections
     */
    public function detailed()
    {
        $checks = [
            'app' => $this->checkApplication(),
            'database' => $this->checkDatabases(),
            'cache' => $this->checkCache(),
        ];

        $overall = collect($checks)->every(fn($check) => $check['status'] === 'healthy');

        return response()->json([
            'status' => $overall ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'checks' => $checks
        ], $overall ? 200 : 503);
    }

    private function checkApplication(): array
    {
        return [
            'status' => 'healthy',
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
            'timezone' => config('app.timezone'),
        ];
    }

    private function checkDatabases(): array
    {
        $databases = ['mysql', 'sso_db', 'employee_db'];
        $results = [];

        foreach ($databases as $connection) {
            try {
                $start = microtime(true);
                DB::connection($connection)->select('SELECT 1 as test');
                $duration = round((microtime(true) - $start) * 1000, 2);

                $results[$connection] = [
                    'status' => 'healthy',
                    'response_time_ms' => $duration
                ];
            } catch (\Exception $e) {
                $results[$connection] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage()
                ];
            }
        }

        $allHealthy = collect($results)->every(fn($result) => $result['status'] === 'healthy');

        return [
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'connections' => $results
        ];
    }

    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 60);
            $value = Cache::get($key);
            Cache::forget($key);

            return [
                'status' => $value === 'test' ? 'healthy' : 'unhealthy',
                'driver' => config('cache.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'driver' => config('cache.default')
            ];
        }
    }
}