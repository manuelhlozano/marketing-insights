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
     * Muestra el selector de clientes / dashboards o acceso mediante token
     */
    public function selector()
    {
        $empresas = Empresa::where('activo', true)->with('dashboards')->get();
        return view('public.selector', compact('empresas'));
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

    /**
     * Endpoint API para consultar datos del dashboard por token
     */
    public function apiDatosPorToken(Request $request, string $token)
    {
        $dashboard = Dashboard::where('public_token', $token)->first();

        if (!$dashboard) {
            $empresa = Empresa::where('token_acceso_maestro', $token)->first();
            if ($empresa) {
                $dashboard = $empresa->dashboards()->latest()->first();
            }
        }

        if (!$dashboard) {
            return response()->json(['error' => 'Token inválido o dashboard no encontrado'], 404);
        }

        $datos = $this->metricsService->compilarDatosDashboard($dashboard);
        return response()->json($datos);
    }
}
