<?php

namespace App\Services;

use App\Models\Dashboard;
use App\Models\Entregable;

class MetricsAggregatorService
{
    /**
     * Compila y devuelve la estructura completa de datos para la vista del dashboard
     */
    public function compilarDatosDashboard(Dashboard $dashboard): array
    {
        $rawJsonPath = public_path('data/raw/dashboard_master_data.json');
        $masterData = [];
        if (file_exists($rawJsonPath)) {
            $masterData = json_decode(file_get_contents($rawJsonPath), true) ?? [];
        }

        $entregables = Entregable::where('dashboard_id', $dashboard->id)
            ->orderBy('numero_item')
            ->get();

        // Categorías y conteos de entregables
        $desgloseFormatos = [
            'MP4' => $entregables->where('formato', 'MP4')->count(),
            'JPG' => $entregables->where('formato', 'JPG')->count(),
            'PDF' => $entregables->where('formato', 'PDF')->count(),
            'Word' => $entregables->where('formato', 'Word')->count(),
        ];

        $desgloseCategorias = $entregables->groupBy('categoria')->map(fn($group) => $group->count());

        return [
            'empresa' => $dashboard->empresa,
            'dashboard' => $dashboard,
            'kpis' => $masterData['kpis_principales'] ?? $dashboard->kpis_destacados ?? [],
            'fases_timeline' => $masterData['periodo']['fases_timeline'] ?? $dashboard->fases_timeline ?? [],
            'resumen_ejecutivo' => $dashboard->resumen_ejecutivo ?? ($masterData['resumen_ejecutivo'] ?? ''),
            'demografia_meta' => $masterData['demografia_meta'] ?? [],
            'comparativa_formatos' => $masterData['comparativa_formatos'] ?? [],
            'showcase_ugc' => $masterData['showcase_ugc'] ?? [],
            'pauta_digital' => $masterData['pauta_digital'] ?? [],
            'email_marketing' => $masterData['email_marketing'] ?? [],
            'atencion_mensajeria' => $masterData['atencion_mensajeria'] ?? [],
            'series_temporales' => $masterData['series_temporales_julio'] ?? [],
            'compromisos_futuros' => $masterData['compromisos_agosto_2026'] ?? $dashboard->compromisos_futuros ?? [],
            'entregables_total' => $entregables->count(),
            'entregables_formatos' => $desgloseFormatos,
            'entregables_categorias' => $desgloseCategorias,
            'entregables_lista' => $entregables
        ];
    }
}
