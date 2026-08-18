<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Models\Empresa;
use App\Services\MetricsAggregatorService;
use Illuminate\Http\Request;

class DashboardPublicController extends Controller
{
    protected MetricsAggregatorService $metricsService;

    public function __construct(MetricsAggregatorService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Selector global de empresas y dashboards
     */
    public function selector()
    {
        $empresas = Empresa::where('activo', true)->with('dashboardsActivos')->get();
        return view('public.selector', compact('empresas'));
    }

    /**
     * Selector de dashboards de una empresa específica por slug
     */
    public function selectorPorEmpresa(string $empresaSlug)
    {
        $empresa = Empresa::where('slug', $empresaSlug)->where('activo', true)->firstOrFail();
        $dashboards = $empresa->dashboardsActivos;

        // Si solo tiene un dashboard activo, redirigir directamente
        if ($dashboards->count() === 1) {
            $dash = $dashboards->first();
            return redirect("/{$empresa->slug}/{$dash->slug}?token={$dash->public_token}");
        }

        return view('public.selector', compact('empresa', 'dashboards'));
    }

    /**
     * Vista pública del Dashboard para clientes con Token de Invitado
     */
    public function verDashboard(Request $request, string $empresaSlug, string $dashboardSlug)
    {
        $empresa = Empresa::where('slug', $empresaSlug)->firstOrFail();
        
        $dashboard = Dashboard::where('empresa_id', $empresa->id)
            ->where('slug', $dashboardSlug)
            ->firstOrFail();

        $tokenIngresado = $request->query('token');

        // Validación de Token Público o Token Maestro de la Empresa
        if (!$dashboard->es_publico && ($tokenIngresado !== $dashboard->public_token && $tokenIngresado !== $empresa->token_acceso_maestro)) {
            return response()->view('public.acceso_restringido', [
                'empresa' => $empresa,
                'dashboard' => $dashboard
            ], 403);
        }

        $datos = $this->metricsService->compilarDatosDashboard($dashboard);

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json($datos);
        }

        return view('public.dashboard', $datos);
    }
}
