<?php

namespace App\Services;

use App\Events\SystemMonitorEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SystemMonitorService
{
    public function broadcastSystemStats()
    {
        $stats = [
            'database' => $this->getDatabaseStats(),
            'cache' => $this->getCacheStats(),
            'memory' => $this->getMemoryStats(),
            'api_health' => $this->getApiHealthStats(),
        ];

        broadcast(new SystemMonitorEvent('system', 'stats', $stats));
        
        return $stats;
    }

    private function getDatabaseStats()
    {
        try {
            $start = microtime(true);
            $result = DB::select('SELECT 1');
            $duration = (microtime(true) - $start) * 1000;
            
            return [
                'status' => 'connected',
                'response_time_ms' => round($duration, 2),
                'connections' => DB::select('SELECT count(*) as count FROM pg_stat_activity')[0]->count ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getCacheStats()
    {
        try {
            Cache::put('health_check', 'ok', 60);
            $value = Cache::get('health_check');
            
            return [
                'status' => $value === 'ok' ? 'connected' : 'error',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getMemoryStats()
    {
        return [
            'usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'limit' => ini_get('memory_limit'),
        ];
    }

    private function getApiHealthStats()
    {
        return [
            'uptime_seconds' => $this->getUptime(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }

    private function getUptime()
    {
        $uptimeFile = storage_path('app/uptime.txt');
        
        if (!file_exists($uptimeFile)) {
            file_put_contents($uptimeFile, time());
            return 0;
        }
        
        $startTime = (int) file_get_contents($uptimeFile);
        return time() - $startTime;
    }
}
