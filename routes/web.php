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

// Selector / Directorio Principal
Route::get('/', [DashboardPublicController::class, 'selector'])->name('home');

// Panel de Administración Maestro (CRUD Empresas, Dashboards, Toggles & Impersonación)
Route::get('/admin', function () {
    return file_get_contents(public_path('admin.html'));
})->name('admin.index');

// Impersonación directa desde el Admin
Route::get('/admin/impersonar/{empresaSlug}/{dashboardSlug}', function ($empresaSlug, $dashboardSlug) {
    return redirect("/{$empresaSlug}/{$dashboardSlug}?token=mkt_live_cmv_78a9c0f");
})->name('admin.impersonar');

// Ingesta de Archivos Planos
Route::get('/admin/dashboard/{dashboardId}/import', [IngestionController::class, 'index'])->name('admin.import');
Route::post('/admin/dashboard/{dashboardId}/import', [IngestionController::class, 'procesarArchivo'])->name('admin.import.process');

// 1. Selector de Dashboards por Empresa (ej. /cine-multiplex-villacentro)
Route::get('/{empresaSlug}', [DashboardPublicController::class, 'selectorPorEmpresa'])->name('empresa.selector');

// 2. Vista Pública de Dashboard por Empresa y Periodo con Token (ej. /cine-multiplex-villacentro/julio-2026?token=...)
Route::get('/{empresaSlug}/{dashboardSlug}', [DashboardPublicController::class, 'verDashboard'])->name('dashboard.public');
