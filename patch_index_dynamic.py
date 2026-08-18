"""
Patches index.html:
1. Reemplaza valores hardcodeados por IDs para que el JS los rellene
2. Agrega dashboardLoader
3. Añade dashboard-engine.js como script
4. Añade IDs a todos los valores numéricos/texto que debe rellenar el motor
"""

import re

with open('public/index.html', 'r', encoding='utf-8') as f:
    content = f.read()

# ─── 1. Header: titulo, subtitulo, label token ────────────────────────────────

# brandReportSub: rellenar por el motor
content = content.replace(
    '<p id="brandReportSub">Informe Ejecutivo de Gestión Mensual | <strong>Julio 2026</strong> • Por Cibergenios Agencia Digital SAS</p>',
    '<p id="brandReportSub"><span id="dashTitulo">Cargando...</span> • Por Cibergenios Agencia Digital SAS</p>'
)

# Limpiar token hardcodeado
content = content.replace(
    '<span id="labelToken">Token: mkt_live_cmv_78a9c0f</span>',
    '<span id="labelToken"></span>'
)

# ─── 2. Hero section: lema y descripcion ─────────────────────────────────────
content = content.replace(
    '<section class="hero-banner" id="sectionTimeline">',
    '<section class="hero-banner" id="sectionTimeline" style="display:none">'
)
content = content.replace(
    '      <div class="hero-meta">Informe de Gestión Mensual | Julio 2026</div>\r\n      <h2 class="hero-title">Toma de Control y Explosión de Crecimiento</h2>\r\n      <p class="hero-subtitle">\r\n        Auditoría inmediata, reestructuración estratégica hacia formatos de video corto (Reels / TikToks UGC) y reactivación integral de la conversión en taquilla y confitería para Cine Múltiplex Villacentro.\r\n      </p>',
    '      <div class="hero-meta" id="dashPeriodo">Julio 2026</div>\r\n      <h2 class="hero-title" id="dashLema">Cargando...</h2>\r\n      <p class="hero-subtitle" id="dashDescripcion"></p>'
)

# Timeline hardcodeado -> renderizado dinámico en timelineContainer
old_timeline = '''        <div class="timeline-bar-wrapper">
        <div class="timeline-bar-title">
          <span><i data-lucide="rocket" style="width: 16px; height: 16px;"></i> Transición Acelerada y Cobertura Total</span>
          <span style="color: var(--text-dim); font-size: 12px; font-weight: 700;">Hito Clave: 15 de Julio (Control 100%)</span>
        </div>
        <div class="timeline-track">
          <div class="timeline-segment segment-empalme" title="24-30 Jun (Empalme parcial)">24-30 Jun (Empalme)</div>
          <div class="timeline-segment segment-diseno" title="1-14 Jul (Diseño parcial)">1-14 Jul (Diseño)</div>
          <div class="timeline-segment segment-control" title="15-31 Jul (Control total Cibergenios)">
            15-31 Jul (Control Total Cibergenios)
          </div>
        </div>
      </div>'''

new_timeline = '''        <div class="timeline-bar-wrapper">
        <div class="timeline-bar-title">
          <span><i data-lucide="rocket" style="width: 16px; height: 16px;"></i> Transición Acelerada y Cobertura Total</span>
        </div>
        <div id="timelineContainer" class="timeline-track"></div>
      </div>'''

content = content.replace(old_timeline, new_timeline)

# ─── 3. KPI Cards: añadir IDs a los valores numéricos ────────────────────────

# KPI Meta Visualizaciones
content = content.replace(
    '          <span class="kpi-number">144.000</span>\r\n          <span class="kpi-badge badge-positive">FB + IG</span>\r\n        </div>\r\n        <p class="kpi-subtext">107.196 en Instagram | 36.783 en Facebook</p>',
    '          <span class="kpi-number" id="kpiMetaVisualizaciones">—</span>\r\n          <span class="kpi-badge badge-positive">FB + IG</span>\r\n        </div>\r\n        <p class="kpi-subtext" id="kpiMetaSub">—</p>'
)

# KPI TikTok
content = content.replace(
    '          <span class="kpi-number">93.100</span>\r\n          <span class="kpi-badge badge-viral">Viral</span>\r\n        </div>\r\n        <p class="kpi-subtext">963 compartidos (El verdadero ROI viral)</p>',
    '          <span class="kpi-number" id="kpiTiktokVistas">—</span>\r\n          <span class="kpi-badge badge-viral">Viral</span>\r\n        </div>\r\n        <p class="kpi-subtext" id="kpiTiktokSub">—</p>'
)

# KPI Comunidad
content = content.replace(
    '          <span class="kpi-number">54.170</span>\r\n          <span class="kpi-badge badge-neutral">+289 neto</span>\r\n        </div>\r\n        <p class="kpi-subtext">50.125 en Facebook | 4.045 en Instagram</p>',
    '          <span class="kpi-number" id="kpiComunidad">—</span>\r\n          <span class="kpi-badge badge-neutral" id="kpiComunidadNeto">Cargando</span>\r\n        </div>\r\n        <p class="kpi-subtext" id="kpiComunidadSub">—</p>'
)

# KPI Entregables
content = content.replace(
    '          <span class="kpi-number">117</span>\r\n          <span class="kpi-badge badge-info">Producciones</span>\r\n        </div>\r\n        <p class="kpi-subtext">27 MP4 | 74 JPG | 9 PDF | Word</p>',
    '          <span class="kpi-number" id="kpiEntregables">—</span>\r\n          <span class="kpi-badge badge-info">Producciones</span>\r\n        </div>\r\n        <p class="kpi-subtext" id="kpiEntregablesSub">—</p>'
)

# ─── 4. Google Metrics ────────────────────────────────────────────────────────
replacements_google = [
    ('3.126', 'googleOpiniones'),
    ('3.995', 'googleBusquedas'),
    ('6.748', 'googleVistas'),
    ('156',   'googleLlamadas'),
]
for (old_val, new_id) in replacements_google:
    old_pattern = f'<div class="kpi-value">{old_val}</div>'
    new_pattern = f'<div class="kpi-value" id="{new_id}">—</div>'
    content = content.replace(old_pattern, new_pattern, 1)

# ─── 5. Meta Metrics ─────────────────────────────────────────────────────────
meta_replacements = [
    ('144.000', 'metaTotalVis'),
    ('107.196', 'metaIgVis'),
    ('36.783',  'metaFbVis'),
    ('30.838',  'metaUnicos'),
    ('4.045',   'metaComunidadIg'),
    ('50.125',  'metaComunidadFb'),
    ('+289',    'metaCrecimiento'),
    ('28.450',  'metaAlcance'),
    ('4.812',   'metaLikes'),
    ('347',     'metaComentarios'),
    ('1.205',   'metaGuardados'),
    ('19.500',  'metaReelVistas'),
    ('1.200',   'metaReelLikes'),
    ('57,6%',   'metaMujeres'),
    ('42,4%',   'metaHombres'),
]
for (old_val, new_id) in meta_replacements:
    # Try table cell patterns
    for pattern in [
        f'<td class="kpi-value-cell"><strong>{old_val}</strong></td>',
        f'<strong>{old_val}</strong>',
        f'>{old_val}<',
    ]:
        if pattern in content:
            if pattern.startswith('<td'):
                content = content.replace(pattern, f'<td class="kpi-value-cell"><strong id="{new_id}">—</strong></td>', 1)
            elif pattern.startswith('<strong'):
                content = content.replace(pattern, f'<strong id="{new_id}">—</strong>', 1)
            break

# ─── 6. TikTok ───────────────────────────────────────────────────────────────
tiktok_replacements = [
    ('93.100',  'tiktokVistas'),
    ('963',     'tiktokCompartidos'),
    ('1.600',   'tiktokLikes'),
]
for (old_val, new_id) in tiktok_replacements:
    for pattern in [
        f'<div class="kpi-value">{old_val}</div>',
        f'<strong>{old_val}</strong>',
    ]:
        if pattern in content:
            if 'kpi-value' in pattern:
                content = content.replace(pattern, f'<div class="kpi-value" id="{new_id}">—</div>', 1)
            else:
                content = content.replace(pattern, f'<strong id="{new_id}">—</strong>', 1)
            break

# ─── 7. Pauta Digital ────────────────────────────────────────────────────────
pauta_replacements = [
    ('$50.016 COP',  'pautaInversion'),
    ('20.381',       'pautaImpresiones'),
    ('449',          'pautaResultados'),
    ('$112,82 COP',  'pautaCPR'),
    ('15.141',       'pautaAlcance'),
]
for (old_val, new_id) in pauta_replacements:
    pattern = f'<div class="kpi-value">{old_val}</div>'
    if pattern in content:
        content = content.replace(pattern, f'<div class="kpi-value" id="{new_id}">—</div>', 1)

# ─── 8. Tabla de campañas de pauta: añadir id al tbody ───────────────────────
content = content.replace(
    '<table class="data-table" id="pautaTable">',
    '<table class="data-table" id="pautaTable"><tbody id="pautaCampanasTable"></tbody>',
    1
)
# Eliminar filas hardcodeadas de campañas si existen (limpiar entre tbody tags)
# (No hace pattern match complejo — lo deja al renderizado JS)

# ─── 9. Email Marketing ──────────────────────────────────────────────────────
email_replacements = [
    ('55.422', 'emailB2cEntregados'),
    ('18%',    'emailB2cOpenRate'),
    ('249',    'emailB2cClics'),
    ('16',     'emailB2cCancelaciones'),
    ('415',    'emailB2bEmpresas'),
    ('30,84%', 'emailB2bOpenRate'),
    ('25',     'emailB2bProspectos'),
    ('6%',     'emailB2bRespuesta'),
]
for (old_val, new_id) in email_replacements:
    for pattern in [
        f'<div class="kpi-value">{old_val}</div>',
        f'<strong>{old_val}</strong>',
    ]:
        if pattern in content:
            if 'kpi-value' in pattern:
                content = content.replace(pattern, f'<div class="kpi-value" id="{new_id}">—</div>', 1)
            else:
                content = content.replace(pattern, f'<strong id="{new_id}">—</strong>', 1)
            break

# ─── 10. Entregables summary ─────────────────────────────────────────────────
entregables_replacements = [
    ('117', 'entregablesTotal'),
    ('27',  'entregablesMp4'),
    ('74',  'entregablesJpg'),
    ('9',   'entregablesPdf'),
    ('7',   'entregablesOtros'),
]
for (old_val, new_id) in entregables_replacements:
    for pattern in [
        f'<div class="kpi-value">{old_val}</div>',
        f'<strong>{old_val}</strong>',
    ]:
        if pattern in content:
            if 'kpi-value' in pattern:
                content = content.replace(pattern, f'<div class="kpi-value" id="{new_id}">—</div>', 1)
            else:
                content = content.replace(pattern, f'<strong id="{new_id}">—</strong>', 1)
            break

# ─── 11. UGC grid: añadir id al contenedor ───────────────────────────────────
content = content.replace(
    '<div class="ugc-cards-grid">',
    '<div class="ugc-cards-grid" id="ugcGrid">',
    1
)

# ─── 12. Entregables tbody ────────────────────────────────────────────────────
if 'id="entregablesTableBody"' not in content:
    content = content.replace(
        '<tbody id="entregablesBody">',
        '<tbody id="entregablesTableBody">',
        1
    )
    if 'id="entregablesTableBody"' not in content:
        # Try finding the entregables table
        content = content.replace(
            '<table class="data-table" id="tablaEntregables">',
            '<table class="data-table" id="tablaEntregables"><!-- tbody injected by JS -->',
            1
        )

# ─── 13. Resumen aprendizaje ─────────────────────────────────────────────────
content = content.replace(
    'El aprendizaje más grande que nos dejan los datos de julio es que el formato dictamina el éxito.',
    '<span id="dashResumen">El aprendizaje más grande que nos dejan los datos de julio es que el formato dictamina el éxito.</span>',
    1
)

# ─── 14. Loader overlay (antes del contenido del dashboard) ──────────────────
loader_html = '''
  <!-- LOADER: mostrado mientras la API carga -->
  <div id="dashboardLoader" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
       background:var(--bg-main); z-index:9999; align-items:center; justify-content:center; flex-direction:column; gap:16px;">
    <div style="width:48px;height:48px;border:4px solid var(--border-color);border-top-color:var(--accent-primary);border-radius:50%;animation:spin 0.8s linear infinite"></div>
    <p style="color:var(--text-muted);font-family:'Inter',sans-serif;font-weight:600">Cargando dashboard...</p>
  </div>
  <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
  <div id="dashboardContent">
'''

# Insertar loader justo después del auth guard, antes del dashboard-container
content = content.replace(
    '  <div class="dashboard-container">',
    loader_html + '  <div class="dashboard-container">',
    1
)

# Cerrar dashboardContent div antes de </body>
content = content.replace('</body>', '  </div><!-- /dashboardContent -->\n</body>', 1)

# ─── 15. Scripts: añadir dashboard-engine.js ────────────────────────────────
content = content.replace(
    '  <script src="js/dashboard-charts.js"></script>\r\n  <script src="js/entregables-filter.js"></script>',
    '  <script src="js/dashboard-charts.js"></script>\r\n  <script src="js/dashboard-engine.js"></script>\r\n  <script src="js/entregables-filter.js"></script>'
)

# ─── 16. Simplificar script inline: quitar lógica hardcodeada ────────────────
# Quitar el bloque de parse de URL params y logos que ahora maneja el engine
old_inline = '''    // Cargar logos locales guardados desde el admin si existen
    const savedLogoLight = localStorage.getItem('logo_light_data');
    if (savedLogoLight) {
      document.getElementById('brandLogoLight').src = savedLogoLight;
    }
    const savedLogoDark = localStorage.getItem('logo_dark_data');
    if (savedLogoDark) {
      document.getElementById('brandLogoDark').src = savedLogoDark;
    }

    // Parse URL params
    const urlParams = new URLSearchParams(window.location.search);
    const empresaSlug = urlParams.get('empresa');
    const dashboardSlug = urlParams.get('dashboard');
    const token = urlParams.get('token');
    const isAdminLogged = localStorage.getItem('mkt_admin_logged') === 'true';

    // Mostrar u ocultar el enlace de Admin según sesión
    const adminLink = document.querySelector('a[href="admin/"]');
    if (!isAdminLogged && adminLink) adminLink.style.display = 'none';

    if (token) {
      document.getElementById('labelToken').textContent = `Token: ${token}`;
    } else if (isAdminLogged) {
      document.getElementById('labelToken').textContent = 'Admin Autenticado';
    }

    // Aplicar configuración de visibilidad guardada desde el Admin
    const savedToggles = localStorage.getItem('mkt_dashboard_toggles');
    if (savedToggles) {
      try {
        const toggles = JSON.parse(savedToggles);
        const sectionMap = {
          timeline: 'sectionTimeline',
          google: 'sectionGoogle',
          meta: 'sectionMeta',
          tiktok: 'sectionTikTok',
          ugc: 'sectionUgc',
          pauta: 'sectionPauta',
          email: 'sectionEmail',
          entregables: 'sectionEntregables'
        };
        Object.entries(sectionMap).forEach(([key, id]) => {
          if (toggles[key] === false) {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
          }
        });
      } catch (e) {
        console.warn('Error leyendo toggles', e);
      }
    }'''

new_inline = '''    // Mostrar/ocultar enlace de Admin según sesión
    const adminLink = document.querySelector('a[href="admin/"]');
    if (localStorage.getItem('mkt_admin_logged') !== 'true' && adminLink) adminLink.style.display = 'none';'''

content = content.replace(old_inline, new_inline)

with open('public/index.html', 'w', encoding='utf-8') as f:
    f.write(content)

# Verificar IDs insertados
import re
ids = re.findall(r'id="(kpi|dash|google|meta|tiktok|pauta|email|entregable|ugc|timeline|dashboard)[^"]*"', content)
print("IDs de datos insertados (%d):" % len(ids))
for id in sorted(set(ids)):
    print("  -", id)

print("\n[OK] index.html actualizado con placeholders dinámicos")
