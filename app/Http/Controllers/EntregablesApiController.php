<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Models\Entregable;
use Illuminate\Http\Request;

class EntregablesApiController extends Controller
{
    /**
     * Devuelve la lista filtrada de entregables en tiempo real
     */
    public function index(Request $request, int $dashboardId)
    {
        $dashboard = Dashboard::findOrFail($dashboardId);

        $query = Entregable::where('dashboard_id', $dashboard->id);

        if ($request->filled('categoria') && $request->categoria !== 'todas') {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('formato') && $request->formato !== 'todos') {
            $query->where('formato', $request->formato);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where('nombre', 'like', "%{$search}%");
        }

        $entregables = $query->orderBy('numero_item')->get();

        return response()->json([
            'total' => $entregables->count(),
            'items' => $entregables
        ]);
    }
}
