<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\Dashboard;
use App\Models\Entregable;
use App\Models\ModuloIndicador;
use App\Models\DatoIndicador;

class CineMultiplexJulio2026Seeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Empresa
        $empresa = Empresa::updateOrCreate(
            ['slug' => 'cine-multiplex-villacentro'],
            [
                'nombre' => 'Cine Múltiplex Villacentro',
                'sector' => 'Entretenimiento & Salas de Cine',
                'ciudad' => 'Villavicencio, Meta',
                'pais' => 'Colombia',
                'token_acceso_maestro' => 'mkt_live_cmv_78a9c0f',
                'activo' => true
            ]
        );

        // 2. Crear Dashboard de Julio 2026
        $dashboard = Dashboard::updateOrCreate(
            [
                'empresa_id' => $empresa->id,
                'slug' => 'julio-2026'
            ],
            [
                'titulo' => 'Informe de Gestión Mensual | Julio 2026',
                'periodo_label' => 'Julio 2026',
                'periodo_inicio' => '2026-07-01',
                'periodo_fin' => '2026-07-31',
                'public_token' => 'mkt_live_cmv_78a9c0f',
                'es_publico' => true,
                'agencia_nombre' => 'Cibergenios Agencia Digital SAS',
                'resumen_ejecutivo' => 'El aprendizaje más grande que nos dejan los datos de julio es que el formato dictamina el éxito. Nuestra decisión de migrar el esfuerzo hacia la producción de videos UGC y Reels permitió frenar en solo 15 días la caída de la comunidad y reactivar la conversión directa a taquilla.',
                'fases_timeline' => [
                    ['periodo' => '24-30 Jun', 'fase' => 'Empalme parcial'],
                    ['periodo' => '1-14 Jul', 'fase' => 'Diseño parcial'],
                    ['periodo' => '15-31 Jul', 'fase' => 'Control total Cibergenios']
                ],
                'kpis_destacados' => [
                    'visualizaciones_meta' => 144000,
                    'vistas_tiktok' => 93100,
                    'comunidad_total' => 54170,
                    'entregables_creados' => 117
                ],
                'compromisos_futuros' => [
                    ['titulo' => 'Garantía de Escalamiento', 'desc' => 'Objetivo mínimo de +10% de crecimiento sostenido.'],
                    ['titulo' => 'Consolidación del Formato Rey', 'desc' => 'Cuota de 4 videos de alto impacto semanales.'],
                    ['titulo' => 'Resolución Técnica de Infraestructura', 'desc' => 'Optimización de servidores en correos masivos.']
                ],
                'estado' => 'publicado'
            ]
        );

        // 3. Crear Módulos e Indicadores
        $modMeta = ModuloIndicador::create([
            'dashboard_id' => $dashboard->id,
            'canal' => 'meta_consolidado',
            'titulo_modulo' => 'Ecosistema Meta (Facebook & Instagram)',
            'icono' => 'eye',
            'orden' => 1
        ]);

        DatoIndicador::create([
            'modulo_id' => $modMeta->id,
            'clave_metrica' => 'meta_visualizaciones',
            'etiqueta' => 'Visualizaciones Meta',
            'valor_actual' => 144000,
            'subetiqueta' => '107.196 Instagram | 36.783 Facebook',
            'porcentaje_variacion' => -20.8,
            'status_color' => 'verde'
        ]);

        // 4. Ingestar entregables desde CSV si existe
        $csvPath = public_path('data/raw/entregables_cibergenios_117.csv');
        if (file_exists($csvPath)) {
            $handle = fopen($csvPath, 'r');
            fgetcsv($handle); // skip header
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row[0])) continue;
                Entregable::updateOrCreate(
                    [
                        'dashboard_id' => $dashboard->id,
                        'numero_item' => (int)$row[0]
                    ],
                    [
                        'nombre' => $row[1],
                        'formato' => $row[2],
                        'categoria' => $row[3],
                        'fecha_entrega' => '2026-07-15'
                    ]
                );
            }
            fclose($handle);
        }
    }
}
