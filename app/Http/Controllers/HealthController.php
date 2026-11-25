<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Health endpoint that actively verifies database and queue (Redis)
 * connectivity. Used by load balancers and container orchestrators.
 */
class HealthController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        $healthy = collect($checks)->every(fn ($c) => $c['ok']);

        return response()->json([
            'status' => $healthy ? 'ok' : 'unhealthy',
            'checked_at' => now()->toISOString(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::connection()->ping();
            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}