<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Services\DataIngestionService;
use Illuminate\Http\Request;

class IngestionController extends Controller
{
    protected DataIngestionService $ingestionService;

    public function __construct(DataIngestionService $ingestionService)
    {
        $this->ingestionService = $ingestionService;
    }

    /**
     * Muestra el panel de importación de archivos
     */
    public function index(int $dashboardId)
    {
        $dashboard = Dashboard::with('empresa', 'ingestas')->findOrFail($dashboardId);
        return view('admin.import', compact('dashboard'));
    }

    /**
     * Procesa la subida de un archivo plano CSV o JSON
     */
    public function procesarArchivo(Request $request, int $dashboardId)
    {
        $dashboard = Dashboard::findOrFail($dashboardId);

        $request->validate([
            'archivo' => 'required|file',
            'tipo_archivo' => 'required|string|in:entregables_csv,pauta_csv,email_csv,master_json'
        ]);

        $file = $request->file('archivo');
        $tipo = $request->input('tipo_archivo');
        $path = $file->storeAs('imports', time() . '_' . $file->getClientOriginalName());
        $fullPath = storage_path('app/' . $path);

        try {
            switch ($tipo) {
                case 'entregables_csv':
                    $res = $this->ingestionService->ingestarEntregablesCsv($dashboard, $fullPath);
                    break;
                case 'pauta_csv':
                    $res = $this->ingestionService->ingestarPautaCsv($dashboard, $fullPath);
                    break;
                case 'email_csv':
                    $res = $this->ingestionService->ingestarEmailMarketingCsv($dashboard, $fullPath);
                    break;
                case 'master_json':
                    $res = $this->ingestionService->sincronizarDesdeJsonMaestro($dashboard, $fullPath);
                    break;
                default:
                    throw new \Exception("Tipo de archivo no soportado.");
            }

            return back()->with('success', 'Archivo procesado e importado con éxito: ' . json_encode($res));
        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }
}
