<?php

namespace App\Services;

use App\Models\Dashboard;
use App\Models\Entregable;
use App\Models\ModuloIndicador;
use App\Models\DatoIndicador;
use App\Models\IngestaArchivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataIngestionService
{
    /**
     * Parsea e ingesta el CSV de 117 Entregables
     */
    public function ingestarEntregablesCsv(Dashboard $dashboard, string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("El archivo no existe: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle, 1000, ',');
        $procesados = 0;

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (empty($data[0]) || !is_numeric($data[0])) continue;

                $numero = (int)$data[0];
                $nombre = trim($data[1] ?? '');
                $formato = trim($data[2] ?? '');
                $categoria = trim($data[3] ?? '');
                $fechaStr = trim($data[4] ?? '');

                // Convertir fecha dd/mm/YYYY a Y-m-d
                $fecha = null;
                if (!empty($fechaStr)) {
                    $parts = explode('/', $fechaStr);
                    if (count($parts) === 3) {
                        $fecha = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
                    }
                }

                Entregable::updateOrCreate(
                    [
                        'dashboard_id' => $dashboard->id,
                        'numero_item' => $numero
                    ],
                    [
                        'nombre' => $nombre,
                        'formato' => $formato,
                        'categoria' => $categoria,
                        'fecha_entrega' => $fecha ?? now(),
                        'destacado' => in_array($categoria, ['Promoción', 'Concursos']) && $formato === 'MP4'
                    ]
                );

                $procesados++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        IngestaArchivo::create([
            'dashboard_id' => $dashboard->id,
            'nombre_archivo_original' => basename($filePath),
            'tipo_fuente' => 'entregables_csv',
            'ruta_almacenamiento' => $filePath,
            'registros_procesados' => $procesados,
            'resumen_ingesta' => ['total_items' => $procesados],
            'estado' => 'completado'
        ]);

        return ['status' => 'success', 'procesados' => $procesados];
    }

    /**
     * Parsea e ingesta el CSV de Pauta Digital (Meta Ads)
     */
    public function ingestarPautaCsv(Dashboard $dashboard, string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("El archivo no existe: {$filePath}");
        }

        $modulo = ModuloIndicador::firstOrCreate(
            ['dashboard_id' => $dashboard->id, 'canal' => 'pauta'],
            ['titulo_modulo' => 'Pauta Digital & Campañas de Tráfico', 'icono' => 'dollar-sign', 'orden' => 6]
        );

        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle, 1000, ',');
        $filas = [];

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            if (empty($data[0]) || trim($data[0]) === '') continue;
            $filas[] = [
                'campana' => trim($data[0]),
                'gasto_cop' => (float)str_replace(['$', '.', ','], ['', '', '.'], $data[1] ?? '0'),
                'alcance' => (int)str_replace('.', '', $data[2] ?? '0'),
                'impresiones' => (int)str_replace('.', '', $data[3] ?? '0'),
                'clics' => (int)str_replace('.', '', $data[4] ?? '0'),
                'resultados' => (int)str_replace('.', '', $data[5] ?? '0'),
                'tipo_resultado' => trim($data[6] ?? ''),
                'costo_por_resultado' => (float)str_replace(['$', '.', ','], ['', '', '.'], $data[7] ?? '0')
            ];
        }
        fclose($handle);

        DatoIndicador::updateOrCreate(
            ['modulo_id' => $modulo->id, 'clave_metrica' => 'resumen_pauta_campanas'],
            [
                'etiqueta' => 'Desglose de Campañas Pagadas',
                'tipo_dato' => 'tabla_campanas',
                'valor_actual' => 50016,
                'subetiqueta' => 'Micro-inversión de alta eficiencia ($112.82 COP por resultado)',
                'datos_serie_o_desglose' => $filas
            ]
        );

        return ['status' => 'success', 'filas' => count($filas)];
    }

    /**
     * Parsea e ingesta el CSV de Email Marketing (Brevo B2C/B2B)
     */
    public function ingestarEmailMarketingCsv(Dashboard $dashboard, string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("El archivo no existe: {$filePath}");
        }

        $modulo = ModuloIndicador::firstOrCreate(
            ['dashboard_id' => $dashboard->id, 'canal' => 'email'],
            ['titulo_modulo' => 'Email Marketing & Fidelización (Brevo)', 'icono' => 'mail', 'orden' => 7]
        );

        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle, 1000, ',');
        $metricas = [];

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            if (empty($data[0]) || trim($data[0]) === '') continue;
            $metricas[] = [
                'tipo_campana' => trim($data[0]),
                'publico' => trim($data[1]),
                'metrica' => trim($data[2]),
                'resultado' => trim($data[3]),
                'impacto' => trim($data[4] ?? '')
            ];
        }
        fclose($handle);

        DatoIndicador::updateOrCreate(
            ['modulo_id' => $modulo->id, 'clave_metrica' => 'email_brevo_metricas'],
            [
                'etiqueta' => 'Rendimiento de Email Marketing',
                'tipo_dato' => 'email_b2b_b2c',
                'valor_actual' => 55422,
                'subetiqueta' => 'B2C: 18% Open Rate | B2B: 30.84% Open Rate',
                'datos_serie_o_desglose' => $metricas
            ]
        );

        return ['status' => 'success', 'metricas' => count($metricas)];
    }

    /**
     * Ingesta y sincroniza el JSON Maestro con todas las fuentes
     */
    public function sincronizarDesdeJsonMaestro(Dashboard $dashboard, string $jsonFilePath): array
    {
        if (!file_exists($jsonFilePath)) {
            throw new \Exception("Archivo JSON maestro no encontrado: {$jsonFilePath}");
        }

        $json = json_decode(file_get_contents($jsonFilePath), true);
        if (!$json) {
            throw new \Exception("Error al decodificar JSON maestro.");
        }

        $dashboard->update([
            'resumen_ejecutivo' => $json['resumen_ejecutivo'] ?? $dashboard->resumen_ejecutivo,
            'fases_timeline' => $json['periodo']['fases_timeline'] ?? $dashboard->fases_timeline,
            'kpis_destacados' => $json['kpis_principales'] ?? $dashboard->kpis_destacados,
            'compromisos_futuros' => $json['compromisos_agosto_2026'] ?? $dashboard->compromisos_futuros
        ]);

        return ['status' => 'success', 'mensaje' => 'Dashboard sincronizado exitosamente'];
    }
}
