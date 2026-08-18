@extends('layouts.executive')

@section('title', 'Marketing Insights | Panel de Ingesta de Archivos')

@section('content')
<div style="max-width: 1000px; margin: 20px auto;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
        <div>
            <h1 style="font-family: var(--font-heading); font-size: 26px; font-weight: 800; color: #FFFFFF;">
                📥 Centro de Ingesta & Importación de Datos
            </h1>
            <p style="font-size: 13px; color: var(--text-muted);">
                Dashboard: <strong>{{ $dashboard->titulo ?? 'Informe Julio 2026' }}</strong> • {{ $dashboard->empresa->nombre ?? 'Cine Múltiplex Villacentro' }}
            </p>
        </div>
        <a href="/dashboard/{{ $dashboard->empresa->slug ?? 'cine-multiplex-villacentro' }}/{{ $dashboard->slug ?? 'julio-2026' }}" class="btn-action">
            ← Volver al Dashboard
        </a>
    </div>

    <!-- Card de Carga de Archivos -->
    <div class="content-card" style="margin-bottom: 32px;">
        <h3 style="font-family: var(--font-heading); font-size: 18px; font-weight: 700; margin-bottom: 16px; color: var(--primary-cyan);">
            Subir y Procesar Archivo Plano (CSV / JSON / Meta / Brevo / TikTok)
        </h3>

        <form action="/admin/dashboard/{{ $dashboard->id ?? 1 }}/import" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">
                        Tipo de Insumo / Fuente
                    </label>
                    <select name="tipo_archivo" class="filter-select" style="width: 100%;">
                        <option value="entregables_csv">Catálogo de Entregables (CSV)</option>
                        <option value="pauta_csv">Métricas de Pauta Digital Meta Ads (CSV)</option>
                        <option value="email_csv">Reporte de Email Marketing Brevo (CSV)</option>
                        <option value="master_json">Estructura Maestra Consolidada (JSON)</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">
                        Seleccionar Archivo
                    </label>
                    <input type="file" name="archivo" class="search-input" required style="padding: 8px;">
                </div>
            </div>

            <button type="submit" class="btn-action btn-primary" style="width: 100%; justify-content: center; padding: 12px;">
                <i data-lucide="upload-cloud" style="width: 18px; height: 18px;"></i>
                <span>Procesar e Importar Datos al Dashboard</span>
            </button>
        </form>
    </div>

    <!-- Historial de Ingestas -->
    <div class="content-card">
        <h3 style="font-family: var(--font-heading); font-size: 18px; font-weight: 700; margin-bottom: 16px; color: #FFFFFF;">
            📋 Historial de Ingestas & Sincronizaciones
        </h3>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Archivo Original</th>
                        <th>Tipo de Fuente</th>
                        <th>Registros</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>01/08/2026 10:45</td>
                        <td>entregables_cibergenios_117.csv</td>
                        <td><span class="badge-format badge-jpg">entregables_csv</span></td>
                        <td>117 ítems</td>
                        <td><span class="kpi-badge badge-positive">Completado</span></td>
                    </tr>
                    <tr>
                        <td>01/08/2026 10:46</td>
                        <td>pauta_digital_meta_ads_july_2026.csv</td>
                        <td><span class="badge-format badge-pdf">pauta_csv</span></td>
                        <td>2 campañas</td>
                        <td><span class="kpi-badge badge-positive">Completado</span></td>
                    </tr>
                    <tr>
                        <td>01/08/2026 10:47</td>
                        <td>email_marketing_brevo_july_2026.csv</td>
                        <td><span class="badge-format badge-word">email_csv</span></td>
                        <td>6 métricas</td>
                        <td><span class="kpi-badge badge-positive">Completado</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
