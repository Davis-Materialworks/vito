<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Spatie\RouteAttributes\Attributes\Get;

class HealthController extends Controller
{
    #[Get('api/health', name: 'api.health')]
    public function __invoke(): JsonResponse
    {
        $dbHealthy = true;
        $dbLatencyMs = 0;

        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $dbLatencyMs = round((microtime(true) - $start) * 1000);
        } catch (\Throwable) {
            $dbHealthy = false;
        }

        $redisHealthy = false;
        $redisLatencyMs = 0;

        try {
            $start = microtime(true);
            Redis::connection()->ping();
            $redisLatencyMs = round((microtime(true) - $start) * 1000);
            $redisHealthy = true;
        } catch (\Throwable) {
        }

        $queueHealthy = true;
        $pendingJobs = 0;

        try {
            $pendingJobs = DB::table('jobs')->count();
        } catch (\Throwable) {
            $queueHealthy = false;
        }

        $schedulerLastRun = Cache::get('schedule-last-run');

        $allHealthy = $dbHealthy && $redisHealthy && $queueHealthy;

        return response()->json([
            'success' => $allHealthy,
            'version' => config('app.version', 'unknown'),
            'database' => [
                'connected' => $dbHealthy,
                'latency_ms' => $dbLatencyMs,
            ],
            'redis' => [
                'connected' => $redisHealthy,
                'latency_ms' => $redisLatencyMs,
            ],
            'queue' => [
                'healthy' => $queueHealthy,
                'pending_jobs' => $pendingJobs,
            ],
            'scheduler' => [
                'last_run' => $schedulerLastRun,
            ],
        ], $allHealthy ? 200 : 503);
    }
}
