"""Patch preciso para index.html usando patrones LF reales del archivo."""

with open('public/index.html', 'r', encoding='utf-8') as f:
    content = f.read()

# Normalizar a LF para trabajar
content = content.replace('\r\n', '\n').replace('\r', '\n')

# ─── KPI Cards ───────────────────────────────────────────────────────────────
kpi_replacements = {
    # Meta Visualizaciones
    '<span class="kpi-number">144.000</span>': '<span class="kpi-number" id="kpiMetaVisualizaciones">—</span>',
    '107.196 en Instagram | 36.783 en Facebook': '<span id="kpiMetaSub">107.196 en Instagram | 36.783 en Facebook</span>',
    # TikTok
    '<span class="kpi-number">93.100</span>': '<span class="kpi-number" id="kpiTiktokVistas">—</span>',
    '963 compartidos (El verdadero ROI viral)': '<span id="kpiTiktokSub">963 compartidos | 1.600 likes</span>',
    # Comunidad
    '<span class="kpi-number">54.170</span>': '<span class="kpi-number" id="kpiComunidad">—</span>',
    '+289 seguidores': '+<span id="kpiComunidadNeto">289</span> crecimiento neto',
    '50.125 en Facebook | 4.045 en Instagram': '<span id="kpiComunidadSub">50.125 en Facebook | 4.045 en Instagram</span>',
    # Entregables
    '<span class="kpi-number">117</span>': '<span class="kpi-number" id="kpiEntregables">—</span>',
    '27 MP4 | 74 JPG | 9 PDF | Word': '<span id="kpiEntregablesSub">27 MP4 | 74 JPG | 9 PDF | Word</span>',
}
for old, new in kpi_replacements.items():
    if old in content:
        content = content.replace(old, new, 1)
        print("OK KPI:", old[:40])
    else:
        print("MISS KPI:", old[:40])

# ─── Hero Section ─────────────────────────────────────────────────────────────
hero_replacements = {
    '<div class="hero-meta">Informe de Gestión Mensual | Julio 2026</div>':
        '<div class="hero-meta" id="dashPeriodo">Julio 2026</div>',
    '<h2 class="hero-title">Toma de Control y Explosión de Crecimiento</h2>':
        '<h2 class="hero-title" id="dashLema">Toma de Control y Explosión de Crecimiento</h2>',
    'Auditoría inmediata, reestructuración estratégica hacia formatos de video corto (Reels / TikToks UGC) y reactivación integral de la conversión en taquilla y confitería para Cine Múltiplex Villacentro.':
        '<span id="dashDescripcion">Auditoría inmediata, reestructuración estratégica hacia formatos de video corto (Reels / TikToks UGC) y reactivación integral de la conversión en taquilla y confitería para Cine Múltiplex Villacentro.</span>',
    # Header info
    '<span id="labelToken"></span>':
        '<span id="labelToken"></span>',
    'Informe Ejecutivo de Gestión Mensual | <strong>Julio 2026</strong> • Por Cibergenios Agencia Digital SAS':
        '<span id="dashTitulo">Informe Ejecutivo de Gestión Mensual | Julio 2026</span> • Por Cibergenios Agencia Digital SAS',
}
for old, new in hero_replacements.items():
    if old in content:
        content = content.replace(old, new, 1)
        print("OK HERO:", old[:50])

# ─── Timeline container ───────────────────────────────────────────────────────
# Replace hardcoded timeline track with dynamic container
old_track = """        <div class="timeline-track">
          <div class="timeline-segment segment-empalme" title="24-30 Jun (Empalme parcial)">24-30 Jun (Empalme)</div>
          <div class="timeline-segment segment-diseno" title="1-14 Jul (Diseño parcial)">1-14 Jul (Diseño)</div>
          <div class="timeline-segment segment-control" title="15-31 Jul (Control total Cibergenios)">
            15-31 Jul (Control Total Cibergenios)
          </div>
        </div>"""
new_track = '        <div class="timeline-track" id="timelineContainer"></div>'
if old_track in content:
    content = content.replace(old_track, new_track, 1)
    print("OK TIMELINE: replaced hardcoded track with dynamic container")
else:
    print("MISS TIMELINE: track not found, adding id to existing track")
    content = content.replace('<div class="timeline-track">', '<div class="timeline-track" id="timelineContainer">', 1)

# ─── UGC grid ID ─────────────────────────────────────────────────────────────
if 'id="ugcGrid"' not in content:
    content = content.replace('<div class="ugc-cards-grid">', '<div class="ugc-cards-grid" id="ugcGrid">', 1)
    print("OK UGC: added id to ugc grid")

# ─── Add dashboard-engine.js ─────────────────────────────────────────────────
if 'dashboard-engine.js' not in content:
    content = content.replace(
        '  <script src="js/dashboard-charts.js"></script>',
        '  <script src="js/dashboard-charts.js"></script>\n  <script src="js/dashboard-engine.js"></script>'
    )
    print("OK SCRIPTS: dashboard-engine.js added")
else:
    print("OK SCRIPTS: dashboard-engine.js already present")

# ─── Entregables tbody id ─────────────────────────────────────────────────────
if 'entregablesTableBody' not in content:
    # Find the entregables table body
    for pattern in ['<tbody id="entregablesBody">', '<tbody>']:
        if pattern in content:
            content = content.replace(pattern, '<tbody id="entregablesTableBody">', 1)
            print("OK ENTREGABLES tbody: id added")
            break

# ─── Save ─────────────────────────────────────────────────────────────────────
with open('public/index.html', 'w', encoding='utf-8', newline='') as f:
    f.write(content)

# Final check
checks = {
    'kpiMetaVisualizaciones': 'id="kpiMetaVisualizaciones"',
    'kpiTiktokVistas': 'id="kpiTiktokVistas"',
    'kpiComunidad': 'id="kpiComunidad"',
    'kpiEntregables': 'id="kpiEntregables"',
    'dashLema': 'id="dashLema"',
    'dashPeriodo': 'id="dashPeriodo"',
    'timelineContainer': 'id="timelineContainer"',
    'ugcGrid': 'id="ugcGrid"',
    'dashboardLoader': 'id="dashboardLoader"',
    'dashboard-engine': 'dashboard-engine.js',
}
print("\n=== Verificacion final ===")
for name, pattern in checks.items():
    print("  %s: %s" % (name, "OK" if pattern in content else "MISSING"))
