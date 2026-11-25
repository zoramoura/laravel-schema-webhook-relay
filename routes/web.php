<?php

use App\Http\Controllers\DlqController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\IngestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Public ingestion endpoint (tenant-scoped) and the Inertia admin console.
|
*/

// Health check — verifies database + queue (Redis) connectivity.
Route::get('/health', [HealthController::class, 'show'])->name('health');

// ---------------------------------------------------------------------------
# Public webhook ingestion gateway — tenant scoped, no CSRF/session required.
# ---------------------------------------------------------------------------
Route::post('/ingest/{tenant_slug}', [IngestController::class, 'store'])
    ->name('ingest.store');

// ---------------------------------------------------------------------------
# Inertia admin console (tenant/operator dashboard).
# ---------------------------------------------------------------------------
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::get('/dlq', [DlqController::class, 'index'])->name('dlq.index');
Route::post('/dlq/{delivery}/replay', [DlqController::class, 'replay'])->name('dlq.replay');
Route::post('/dlq/bulk-replay', [DlqController::class, 'bulkReplay'])->name('dlq.bulk-replay');
