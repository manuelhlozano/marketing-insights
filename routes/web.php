<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardPublicController;
use App\Http\Controllers\IngestionController;
use App\Http\Controllers\EntregablesApiController;

/*
|--------------------------------------------------------------------------
| Web Routes - Marketing Insights
|--------------------------------------------------------------------------
*/

Route::get('/', [DashboardPublicController::class, 'selector'])->name('home');

// Selector de Clientes / Empresas y Dashboards
Route::get('/dashboards', [DashboardPublicController::class, 'selector'])->name('dashboards.selector');

// Vista Pública del Dashboard con Token de Invitado para Clientes
Route::get('/dashboard/{empresaSlug}/{dashboardSlug}', [DashboardPublicController::class, 'verDashboard'])
    ->name('dashboard.public');

// Panel de Ingesta & Carga de Archivos Planos
Route::get('/admin/dashboard/{dashboardId}/import', [IngestionController::class, 'index'])
    ->name('admin.import');
Route::post('/admin/dashboard/{dashboardId}/import', [IngestionController::class, 'procesarArchivo'])
    ->name('admin.import.process');
