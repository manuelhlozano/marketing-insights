/**
 * MARKETING INSIGHTS — DASHBOARD ENGINE
 * Fetcha la API y renderiza todo el contenido dinámicamente en el DOM
 */

(function () {
  'use strict';

  const params       = new URLSearchParams(window.location.search);
  const empresaSlug  = params.get('empresa')   || 'cine-multiplex-villacentro';
  const dashSlug     = params.get('dashboard') || 'julio-2026';
  const token        = params.get('token')     || '';

  const API_URL = `/api/data.php?action=dashboard&empresa=${encodeURIComponent(empresaSlug)}&dashboard=${encodeURIComponent(dashSlug)}&token=${encodeURIComponent(token)}`;

  // ─── Utilidades ──────────────────────────────────────────────────────────────
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
  const fmt = (n, dec = 0) => n == null ? '—' : Number(n).toLocaleString('es-CO', { minimumFractionDigits: dec, maximumFractionDigits: dec });
  const fmtCOP = (n) => n == null ? '—' : `$${fmt(n)} COP`;
  const fmtPct = (n) => n == null ? '—' : `${fmt(n, 1)}%`;
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val ?? '—'; };
  const setHTML = (id, html) => { const el = document.getElementById(id); if (el) el.innerHTML = html; };
  const val = (obj, key) => obj?.[key]?.valor ?? null;
  const txt = (obj, key) => obj?.[key]?.texto ?? null;

  // ─── Loader ───────────────────────────────────────────────────────────────────
  function showLoader() {
    const loader = document.getElementById('dashboardLoader');
    if (loader) loader.style.display = 'flex';
    const container = document.getElementById('dashboardContent');
    if (container) container.style.display = 'none';
  }

  function hideLoader() {
    const loader = document.getElementById('dashboardLoader');
    if (loader) loader.style.display = 'none';
    const container = document.getElementById('dashboardContent');
    if (container) container.style.display = '';
    document.body.style.display = '';
  }

  function showError(msg) {
    const el = document.getElementById('dashboardLoader');
    if (el) el.innerHTML = `
      <div style="text-align:center; padding:40px; color:var(--text-muted);">
        <i data-lucide="alert-triangle" style="width:48px;height:48px;color:#EF4444;margin-bottom:16px"></i>
        <h3 style="color:var(--text-primary);margin-bottom:8px">Error al cargar el dashboard</h3>
        <p>${msg}</p>
        <button onclick="window.location.reload()" style="margin-top:16px;padding:8px 20px;background:var(--accent-primary);color:#fff;border:none;border-radius:8px;cursor:pointer">Reintentar</button>
      </div>`;
    if (typeof lucide !== 'undefined') lucide.createIcons();
  }

  // ─── FETCH & RENDER ───────────────────────────────────────────────────────────
  async function loadDashboard() {
    showLoader();
    try {
      const res = await fetch(API_URL);
      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.error || `HTTP ${res.status}`);
      }
      const data = await res.json();
      renderAll(data);
      hideLoader();
      if (typeof lucide !== 'undefined') lucide.createIcons();
    } catch (e) {
      console.error('[Dashboard Engine]', e);
      showError(e.message || 'No se pudo conectar con el servidor');
    }
  }

  // ─── RENDER PRINCIPAL ─────────────────────────────────────────────────────────
  function renderAll(data) {
    window._lastDashboardData = data;
    renderHeader(data);
    renderKpis(data);
    renderTimeline(data.hitos || []);
    renderGoogle(data.google || {});
    renderMeta(data.meta || {});
    renderTikTok(data.tiktok || {});
    renderUgc(data.ugc || []);
    renderPauta(data.pauta || {}, data.campanas_pauta || []);
    renderEmail(data.email_b2c || {}, data.email_b2b || {});
    renderEntregables(data.entregables || [], data.entregables_summary || {});
    renderResumen(data.dashboard || {});
    applyModulosToggle(data.modulos_activos || []);

    // Inicializar gráficos con datos reales de la API
    if (typeof window.renderChartsFromData === 'function') {
      window.renderChartsFromData(data);
    }
  }

  // ─── HEADER ───────────────────────────────────────────────────────────────────
  function renderHeader(data) {
    const { empresa, dashboard } = data;
    if (!empresa || !dashboard) return;

    set('dashTitulo', dashboard.titulo);
    set('dashPeriodo', dashboard.periodo);
    set('dashLema', dashboard.lema);
    set('dashDescripcion', dashboard.descripcion);
    set('dashEmpresa', empresa.nombre);
    set('labelToken', token ? `Token: ${token}` : (localStorage.getItem('mkt_admin_logged') === 'true' ? 'Admin Autenticado' : ''));

    // Logos
    const logoLight = document.getElementById('brandLogoLight');
    const logoDark  = document.getElementById('brandLogoDark');
    const savedLight = localStorage.getItem('logo_light_data');
    const savedDark  = localStorage.getItem('logo_dark_data');

    if (logoLight) logoLight.src = savedLight || empresa.logo_light_url || '';
    if (logoDark)  logoDark.src  = savedDark  || empresa.logo_dark_url  || '';

    document.title = `${empresa.nombre} | ${dashboard.titulo}`;
    // Breadcrumb de empresa en la URL (para UX, sin recargar)
    const newUrl = `/${empresa.slug}/${dashboard.slug}${token ? '?token=' + token : ''}`;
    if (window.history && window.location.pathname !== `/${empresa.slug}/${dashboard.slug}`) {
      window.history.replaceState({}, '', newUrl);
    }
  }

  // ─── 4 KPI HEROES ─────────────────────────────────────────────────────────────
  function renderKpis(data) {
    const m = data.meta || {};
    const t = data.tiktok || {};
    const e = data.entregables_summary || {};

    set('kpiMetaVisualizaciones', fmt(val(m, 'total_visualizaciones')));
    set('kpiMetaSub', txt(m, 'total_visualizaciones') || `${fmt(val(m, 'ig_visualizaciones'))} IG | ${fmt(val(m, 'fb_visualizaciones'))} FB`);

    set('kpiTiktokVistas', fmt(val(t, 'vistas_7d')));
    set('kpiTiktokSub', `${fmt(val(t, 'compartidos'))} compartidos | ${fmt(val(t, 'likes'))} likes`);

    const comunidad = (val(m, 'comunidad_ig') || 0) + (val(m, 'comunidad_fb') || 0);
    set('kpiComunidad', fmt(comunidad));
    set('kpiComunidadSub', `+${fmt(val(m, 'crecimiento_neto'))} crecimiento neto`);

    set('kpiEntregables', fmt(val(e, 'total')));
    set('kpiEntregablesSub', `${fmt(val(e, 'mp4'))} MP4 · ${fmt(val(e, 'jpg'))} JPG · ${fmt(val(e, 'pdf'))} PDF`);
  }

  // ─── TIMELINE ─────────────────────────────────────────────────────────────────
  function renderTimeline(hitos) {
    const container = document.getElementById('timelineContainer');
    if (!container || !hitos.length) return;
    container.innerHTML = hitos.map((h, i) => `
      <div class="timeline-phase ${i === hitos.length - 1 ? 'phase-active' : ''}">
        <div class="phase-dot"></div>
        <div class="phase-content">
          <div class="phase-label">${h.periodo}</div>
          <div class="phase-title">${h.fase}</div>
          ${h.descripcion ? `<div class="phase-desc">${h.descripcion}</div>` : ''}
          ${h.hito_clave  ? `<div class="phase-badge"><i data-lucide="star" style="width:12px;height:12px;display:inline"></i> Hito Clave: ${h.hito_clave}</div>` : ''}
        </div>
      </div>`).join('');
  }

  // ─── GOOGLE ───────────────────────────────────────────────────────────────────
  function renderGoogle(g) {
    set('googleOpiniones',  fmt(val(g, 'opiniones')));
    set('googleBusquedas',  fmt(val(g, 'busquedas_directas')));
    set('googleVistas',     fmt(val(g, 'vistas_perfil')));
    set('googleLlamadas',   fmt(val(g, 'llamadas')));

    // Ring goals
    window._googleGoals = {
      opiniones: val(g, 'cobertura_opiniones') || 88,
      ig:        val(g, 'cobertura_ig')        || 15,
      fb:        val(g, 'cobertura_fb')        || 92,
    };
  }

  // ─── META ─────────────────────────────────────────────────────────────────────
  function renderMeta(m) {
    set('metaTotalVis',   fmt(val(m, 'total_visualizaciones')));
    set('metaIgVis',      fmt(val(m, 'ig_visualizaciones')));
    set('metaFbVis',      fmt(val(m, 'fb_visualizaciones')));
    set('metaUnicos',     fmt(val(m, 'espectadores_unicos')));
    set('metaComunidadIg',fmt(val(m, 'comunidad_ig')));
    set('metaComunidadFb',fmt(val(m, 'comunidad_fb')));
    set('metaCrecimiento',`+${fmt(val(m, 'crecimiento_neto'))}`);
    set('metaAlcance',    fmt(val(m, 'ig_alcance')));
    set('metaLikes',      fmt(val(m, 'ig_likes')));
    set('metaComentarios',fmt(val(m, 'ig_comentarios')));
    set('metaGuardados',  fmt(val(m, 'ig_guardados')));
    set('metaReelVistas', fmt(val(m, 'reel_top_vistas')));
    set('metaReelLikes',  fmt(val(m, 'reel_top_likes')));
    set('metaMujeres',    fmtPct(val(m, 'mujeres_pct')));
    set('metaHombres',    fmtPct(val(m, 'hombres_pct')));
  }

  // ─── TIKTOK ───────────────────────────────────────────────────────────────────
  function renderTikTok(t) {
    set('tiktokVistas',    fmt(val(t, 'vistas_7d')));
    set('tiktokCompartidos',fmt(val(t, 'compartidos')));
    set('tiktokLikes',     fmt(val(t, 'likes')));
    set('tiktokVideos',    fmt(val(t, 'videos_creados')));
  }

  // ─── UGC / CONTENIDO VIRAL ───────────────────────────────────────────────────
  function renderUgc(ugc) {
    const container = document.getElementById('ugcGrid');
    if (!container) return;

    // Calcular total de vistas dinámicamente
    const totalViews = ugc.reduce((sum, u) => sum + (u.vistas || 0), 0);
    const badgeEl = document.getElementById('ugcHeaderBadge');
    if (badgeEl) {
      badgeEl.textContent = `+${fmt(totalViews)} visualizaciones en ${ugc.length} piezas clave de UGC`;
    }

    container.innerHTML = ugc.map((u, i) => {
      const badges = ['#1 Video del Mes', 'Top 2 UGC', 'Top 3 UGC', 'Top 4 UGC'];
      const rankBadge = u.badge_label || badges[i] || `Pieza #${i+1}`;
      const isTikTok = u.canal === 'tiktok';
      const canalLabel = isTikTok ? 'TikTok Viral' : 'Instagram Reel';
      const canalClass = isTikTok ? 'pill-tiktok' : 'pill-instagram';
      const canalIcon = isTikTok ? 'music' : 'camera';

      return `
        <div class="ugc-exec-card">
          <div class="ugc-exec-header">
            <span class="ugc-rank-badge">${rankBadge}</span>
            <span class="channel-header-pill ${canalClass}" style="padding: 3px 10px; font-size: 11.5px;">
              <i data-lucide="${canalIcon}" style="width: 12px; height: 12px;"></i>
              ${canalLabel}
            </span>
          </div>

          <h4 class="ugc-exec-title">${u.titulo}</h4>
          <p class="ugc-exec-desc">${u.nota_estrategica || u.subtitulo || ''}</p>

          <div class="ugc-exec-metrics">
            <div class="ugc-metric-pill">
              <span class="ugc-metric-val"><i data-lucide="eye" style="width: 13px; height: 13px; display: inline;"></i> ${fmt(u.vistas)}</span>
              <span class="ugc-metric-lbl">Visualizaciones</span>
            </div>
            <div class="ugc-metric-pill highlight-orange">
              <span class="ugc-metric-val"><i data-lucide="share-2" style="width: 13px; height: 13px; display: inline;"></i> ${fmt(u.compartidos)}</span>
              <span class="ugc-metric-lbl">Compartidos</span>
            </div>
            ${u.likes ? `
            <div class="ugc-metric-pill">
              <span class="ugc-metric-val"><i data-lucide="heart" style="width: 13px; height: 13px; display: inline;"></i> ${fmt(u.likes)}</span>
              <span class="ugc-metric-lbl">Me Gusta</span>
            </div>` : ''}
          </div>
        </div>`;
    }).join('');
  }



  // ─── PAUTA ────────────────────────────────────────────────────────────────────
  function renderPauta(p, campanas) {
    set('pautaInversion',  fmtCOP(val(p, 'inversion_cop')));
    set('pautaImpresiones',fmt(val(p, 'impresiones')));
    set('pautaResultados', fmt(val(p, 'resultados')));
    set('pautaCPR',        fmtCOP(val(p, 'cpr')));
    set('pautaAlcance',    fmt(val(p, 'alcance_total')));

    const container = document.getElementById('pautaCampanasTable');
    if (!container || !campanas.length) return;
    container.innerHTML = campanas.map(c => `
      <tr>
        <td><strong>${c.nombre}</strong><br><small style="color:var(--text-muted)">${c.objetivo || ''}</small></td>
        <td>${fmtCOP(c.inversion_cop)}</td>
        <td>${fmt(c.alcance)}</td>
        <td>${fmt(c.resultados)}</td>
        <td>${c.tipo_resultado || ''}</td>
        <td><strong>${fmtCOP(c.cpr)}</strong></td>
      </tr>`).join('');
  }

  // ─── EMAIL ────────────────────────────────────────────────────────────────────
  function renderEmail(b2c, b2b) {
    // B2C
    set('emailB2cEntregados', fmt(val(b2c, 'entregados')));
    set('emailB2cOpenRate',   fmtPct(val(b2c, 'open_rate')));
    set('emailB2cClics',      fmt(val(b2c, 'clics')));
    set('emailB2cCancelaciones', fmt(val(b2c, 'cancelaciones')));
    // B2B
    set('emailB2bEmpresas',   fmt(val(b2b, 'empresas')));
    set('emailB2bOpenRate',   fmtPct(val(b2b, 'open_rate')));
    set('emailB2bProspectos', fmt(val(b2b, 'prospectos')));
    set('emailB2bRespuesta',  fmtPct(val(b2b, 'tasa_respuesta')));
  }

  // ─── ENTREGABLES ──────────────────────────────────────────────────────────────
  function renderEntregables(list, summary) {
    set('entregablesTotal', fmt(val(summary, 'total')));
    set('entregablesMp4',   fmt(val(summary, 'mp4')));
    set('entregablesJpg',   fmt(val(summary, 'jpg')));
    set('entregablesPdf',   fmt(val(summary, 'pdf')));
    set('entregablesOtros', fmt(val(summary, 'otros')));

    if (typeof window.initEntregablesFromApi === 'function') {
      window.initEntregablesFromApi(list);
    }
  }

  // ─── RESUMEN EJECUTIVO ────────────────────────────────────────────────────────
  function renderResumen(dashboard) {
    set('dashResumen', dashboard.resumen);
  }

  // ─── MÓDULOS TOGGLE ───────────────────────────────────────────────────────────
  function applyModulosToggle(activos) {
    // Aplicar toggles guardados en localStorage (del admin) sobre la lista de módulos activos de BD
    const savedToggles = localStorage.getItem('mkt_dashboard_toggles');
    const overrides = savedToggles ? JSON.parse(savedToggles) : {};

    const sectionMap = {
      timeline:    'sectionTimeline',
      google:      'sectionGoogle',
      meta:        'sectionMeta',
      tiktok:      'sectionTikTok',
      ugc:         'sectionUgc',
      pauta:       'sectionPauta',
      email:       'sectionEmail',
      entregables: 'sectionEntregables',
    };

    Object.entries(sectionMap).forEach(([codigo, sectionId]) => {
      const el = document.getElementById(sectionId);
      if (!el) return;
      const activoEnBd    = activos.includes(codigo);
      const activoEnAdmin = overrides[codigo] !== false;
      if (!activoEnBd || !activoEnAdmin) {
        el.style.display = 'none';
      } else {
        el.style.display = '';
      }
    });

  }

  // ─── Arrancar al cargar el DOM ────────────────────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadDashboard);
  } else {
    loadDashboard();
  }

  // Exponer reload para uso externo
  window.reloadDashboard = loadDashboard;

})();
