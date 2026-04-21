<?php

use App\Http\Controllers\KpiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/kpi/summary', [KpiController::class, 'summary']);
    Route::get('/kpi/{kpi}/history', [KpiController::class, 'history']);
    Route::post('/kpi/{kpi}/update', [KpiController::class, 'update'])->middleware('throttle:60,1');
});
