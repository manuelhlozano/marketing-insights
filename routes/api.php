<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardPublicController;
use App\Http\Controllers\EntregablesApiController;

/*
|--------------------------------------------------------------------------
| API Routes - Marketing Insights
|--------------------------------------------------------------------------
*/

// Consulta de datos de dashboard por Token
Route::get('/v1/dashboard/token/{token}', [DashboardPublicController::class, 'apiDatosPorToken']);

// Búsqueda y filtrado de entregables en tiempo real
Route::get('/v1/dashboard/{dashboardId}/entregables', [EntregablesApiController::class, 'index']);
