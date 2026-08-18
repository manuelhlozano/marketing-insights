@extends('layouts.executive')

@section('title', 'Marketing Insights | Selector de Clientes & Dashboards')

@section('content')
<div style="max-width: 900px; margin: 40px auto; text-align: center;">

    <div class="brand-icon" style="width: 64px; height: 64px; font-size: 32px; margin: 0 auto 20px;">📊</div>
    <h1 style="font-family: var(--font-heading); font-size: 36px; font-weight: 900; margin-bottom: 10px; color: #FFFFFF;">
        Marketing Insights Platform
    </h1>
    <p style="font-size: 16px; color: var(--text-muted); margin-bottom: 40px;">
        Plataforma jerárquica de inteligencia, analítica ejecutiva y visualización de marketing multicanal.
    </p>

    <!-- Card de Acceso por Token -->
    <div class="content-card" style="margin-bottom: 40px; text-align: left;">
        <h3 style="font-family: var(--font-heading); font-size: 18px; font-weight: 700; margin-bottom: 12px; color: var(--primary-cyan);">
            🔑 Acceso Rápido con Token de Invitado
        </h3>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
            Si eres un cliente y cuentas con un enlace o token privado, ingrésalo a continuación para visualizar tu informe:
        </p>
        <form action="#" onsubmit="handleTokenSubmit(event)" style="display: flex; gap: 12px;">
            <input type="text" id="inputToken" class="search-input" placeholder="Ej: mkt_live_cmv_78a9c0f" value="mkt_live_cmv_78a9c0f" style="flex: 1;">
            <button type="submit" class="btn-action btn-primary">Acceder al Dashboard</button>
        </form>
    </div>

    <!-- Directorio de Empresas y Dashboards Públicos -->
    <div style="text-align: left;">
        <h3 style="font-family: var(--font-heading); font-size: 20px; font-weight: 700; margin-bottom: 20px; color: #FFFFFF;">
            🏢 Empresas & Dashboards Activos
        </h3>

        <div class="ugc-grid">
            <div class="ugc-card" style="border-top: 4px solid var(--primary-blue);">
                <div class="ugc-body" style="padding: 24px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <span style="font-size: 28px;">🎬</span>
                        <div>
                            <h4 style="font-size: 18px; font-weight: 800; color: #FFFFFF;">Cine Múltiplex Villacentro</h4>
                            <span style="font-size: 12px; color: var(--text-muted);">Entretenimiento & Salas de Cine</span>
                        </div>
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
                        Informe de gestión mensual auditado correspondiente a Julio de 2026 (Meta, TikTok, Brevo, Ads y 117 entregables).
                    </p>
                    <a href="/dashboard/cine-multiplex-villacentro/julio-2026?token=mkt_live_cmv_78a9c0f" class="btn-action btn-primary" style="justify-content: center;">
                        Ver Dashboard de Julio 2026 ➔
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function handleTokenSubmit(e) {
    e.preventDefault();
    const token = document.getElementById('inputToken').value.trim();
    if (token) {
        window.location.href = `/dashboard/cine-multiplex-villacentro/julio-2026?token=${encodeURIComponent(token)}`;
    }
}
</script>
@endsection
