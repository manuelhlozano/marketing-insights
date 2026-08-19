/**
 * MARKETING INSIGHTS — CHARTS ENGINE
 * Recibe datos de dashboard-engine.js vía window.renderChartsFromData(data)
 * Soporta modo claro/oscuro con alto contraste y colores vivos
 */

let chartInstances = {};

function isLightMode()  { return document.body.classList.contains('light-mode'); }
function getTextColor() { return isLightMode() ? '#0F172A' : '#F8FAFC'; }
function getGridColor() { return isLightMode() ? 'rgba(0,0,0,0.08)' : 'rgba(255,255,255,0.08)'; }
function getBorderColor(){ return isLightMode() ? '#FFFFFF' : '#131B2E'; }

function destroyAll() {
  Object.values(chartInstances).forEach(c => c?.destroy?.());
  chartInstances = {};
}

// ─── Entry point llamado por dashboard-engine.js ──────────────────────────────
window.renderChartsFromData = function(data) {
  destroyAll();
  const googleGoals = window._googleGoals || { opiniones: 88, ig: 15, fb: 92 };
  renderGoalRings(googleGoals);
  renderMetaChart(data.series_meta || {}, data.comparativo);
  renderTikTokChart(data.series_tiktok || {}, data.comparativo);
  renderDemograficsChart(data.demografica?.edad_genero || {});
  renderCiudadesChart(data.demografica?.ciudades       || {});
  renderFormatsChart(data.entregables_summary          || {});
  renderRetentionChart();
  renderAdsChart(data.campanas_pauta                   || []);
};

// Re-render al cambiar tema (llamado desde toggleTheme())
window.updateChartsTheme = function() {
  if (window._lastDashboardData) {
    window.renderChartsFromData(window._lastDashboardData);
  }
};

// ─── ANILLOS DE META ─────────────────────────────────────────────────────────
function renderGoalRings(goals) {
  renderRing('ringGoogleGoal',    goals.opiniones || 88, '#EA580C');
  renderRing('ringInstagramGoal', goals.ig        || 15, '#C026D3');
  renderRing('ringFacebookGoal',  goals.fb        || 92, '#1D4ED8');
}

function renderRing(canvasId, pct, color) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;
  const emptyColor = isLightMode() ? '#E2E8F0' : '#1E293B';
  chartInstances[canvasId] = new Chart(ctx, {
    type: 'doughnut',
    data: {
      datasets: [{
        data: [pct, 100 - pct],
        backgroundColor: [color, emptyColor],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '76%',
      plugins: { legend: { display: false }, tooltip: { enabled: false } }
    }
  });
}

// ─── META GROWTH CHART ───────────────────────────────────────────────────────
function renderMetaChart(seriesMeta, comparativo) {
  const ctx = document.getElementById('chartMetaGrowth');
  if (!ctx) return;

  const isCmp = window._isComparisonMode === true && comparativo && comparativo.disponible;

  const labels = seriesMeta.visualizaciones?.labels
    || ['1 jul','6 jul','11 jul','15 jul (Control)','20 jul (Pico)','26 jul','30 jul (Estreno)','31 jul'];
  const visData = seriesMeta.visualizaciones?.data
    || [2500, 3200, 2800, 4800, 16800, 2100, 19500, 9200];
  const unicosData = seriesMeta.espectadores?.data
    || [800, 1200, 1500, 1800, 9500, 1100, 3800, 1900];

  const datasets = [
    {
      label: isCmp ? 'Julio 2026 (Cibergenios)' : 'Visualizaciones Meta (Julio)',
      data: visData,
      borderColor: '#0284C7',
      backgroundColor: isLightMode() ? 'rgba(2,132,199,0.12)' : 'rgba(2,132,199,0.25)',
      fill: true, tension: 0.35, borderWidth: 3,
      pointBackgroundColor: '#0284C7', pointBorderColor: '#FFFFFF', pointBorderWidth: 2, pointRadius: 5
    },
    {
      label: 'Espectadores Únicos (Julio)',
      data: unicosData,
      borderColor: '#8B5CF6', backgroundColor: 'transparent',
      tension: 0.35, borderWidth: 2.5,
      pointBackgroundColor: '#8B5CF6', pointRadius: 4
    }
  ];

  if (isCmp && comparativo.series_previas?.visualizaciones?.data) {
    const junFull = comparativo.series_previas.visualizaciones.data;
    const junSample = [
      junFull[0] || 7763,
      junFull[5] || 2860,
      junFull[10] || 3732,
      junFull[14] || 4791,
      junFull[16] || 16560,
      junFull[22] || 17376,
      junFull[27] || 2218,
      junFull[29] || 1446
    ];

    datasets.push({
      label: 'Junio 2026 (Línea Base)',
      data: junSample,
      borderColor: '#94A3B8',
      backgroundColor: 'transparent',
      borderDash: [6, 4],
      tension: 0.35,
      borderWidth: 2.5,
      pointBackgroundColor: '#94A3B8',
      pointRadius: 4
    });
  }

  chartInstances['metaGrowth'] = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets
    },
    options: baseLineOptions('Visualizaciones')
  });
}


// ─── TIKTOK CHART ────────────────────────────────────────────────────────────
function renderTikTokChart(seriesTiktok, comparativo) {
  const ctx = document.getElementById('chartTikTokGrowth');
  if (!ctx) return;

  const isCmp = window._isComparisonMode === true && comparativo && comparativo.disponible;

  const labels = seriesTiktok.vistas?.labels
    || ['1-5 Jul','6-10 Jul','11-15 Jul','16-20 Jul','21-23 Jul','24-27 Jul (Cibergenios)','28-31 Jul (Viral)'];
  const data   = seriesTiktok.vistas?.data
    || [3500, 2800, 2100, 1900, 2400, 32500, 47900];

  const datasets = [{
    label: isCmp ? 'Julio 2026 (Cibergenios)' : 'Visualizaciones TikTok',
    data,
    borderColor: '#06B6D4',
    backgroundColor: isLightMode() ? 'rgba(6,182,212,0.12)' : 'rgba(6,182,212,0.25)',
    fill: true, tension: 0.35, borderWidth: 3,
    pointBackgroundColor: '#06B6D4', pointBorderColor: '#FFFFFF', pointBorderWidth: 2, pointRadius: 5
  }];

  if (isCmp && comparativo.series_previas_tiktok?.vistas?.data) {
    const junFull = comparativo.series_previas_tiktok.vistas.data;
    const junSample = [
      Math.round((junFull.slice(0,5).reduce((a,b)=>a+b,0))/5),
      Math.round((junFull.slice(5,10).reduce((a,b)=>a+b,0))/5),
      Math.round((junFull.slice(10,15).reduce((a,b)=>a+b,0))/5),
      Math.round((junFull.slice(15,20).reduce((a,b)=>a+b,0))/5),
      Math.round((junFull.slice(20,23).reduce((a,b)=>a+b,0))/3),
      Math.round((junFull.slice(23,27).reduce((a,b)=>a+b,0))/4),
      Math.round((junFull.slice(27,30).reduce((a,b)=>a+b,0))/3)
    ];

    datasets.push({
      label: 'Junio 2026 (Promedio Diario Línea Base)',
      data: junSample,
      borderColor: '#94A3B8',
      backgroundColor: 'transparent',
      borderDash: [6, 4],
      tension: 0.35,
      borderWidth: 2.5,
      pointBackgroundColor: '#94A3B8',
      pointRadius: 4
    });
  }

  chartInstances['tiktok'] = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets
    },
    options: baseLineOptions('Vistas')
  });
}


// ─── DEMOGRAFÍA EDAD/GÉNERO ───────────────────────────────────────────────────
function renderDemograficsChart(edadGenero) {
  const ctx = document.getElementById('chartDemographics');
  if (!ctx) return;

  const labels  = edadGenero.labels  || ['18-24','25-34','35-44','45-54','55-64','65+'];
  const mujeres = edadGenero.mujeres || [3.3, 26.2, 18.8, 6.9, 1.8, 0.6];
  const hombres = edadGenero.hombres || [2.6, 19.6, 13.9, 4.8, 1.2, 0.5];
  const pctM = mujeres.reduce((a,b)=>a+b,0).toFixed(1);
  const pctH = hombres.reduce((a,b)=>a+b,0).toFixed(1);

  chartInstances['demographics'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: `Mujeres (${pctM}%)`, data: mujeres, backgroundColor: '#0284C7', borderRadius: 4 },
        { label: `Hombres (${pctH}%)`, data: hombres, backgroundColor: '#EA580C', borderRadius: 4 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { labels: { color: getTextColor(), font: { weight: '600' } } } },
      scales: {
        x: { grid: { color: getGridColor() }, ticks: { color: getTextColor(), font: { weight: '600' } } },
        y: { grid: { color: getGridColor() }, ticks: { callback: v => `${v}%`, color: getTextColor(), font: { weight: '600' } } }
      }
    }
  });
}

// ─── CIUDADES ────────────────────────────────────────────────────────────────
function renderCiudadesChart(ciudades) {
  const ctx = document.getElementById('chartCities');
  if (!ctx) return;

  const labels = ciudades.labels  || ['Villavicencio (Meta)','Bogotá D.C.','Acacías (Meta)','Granada (Meta)','Medellín','Restrepo (Meta)'];
  const data   = ciudades.valores || [47.4, 6.2, 2.3, 0.9, 0.8, 0.8];

  chartInstances['cities'] = new Chart(ctx, {
    type: 'bar', indexAxis: 'y',
    data: {
      labels,
      datasets: [{ label: '% Audiencia', data, backgroundColor: '#0284C7', borderRadius: 4 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: getGridColor() }, ticks: { callback: v => `${v}%`, color: getTextColor(), font: { weight: '600' } } },
        y: { grid: { color: getGridColor() }, ticks: { color: getTextColor(), font: { weight: '600' } } }
      }
    }
  });
}

// ─── FORMATOS ENTREGABLES ─────────────────────────────────────────────────────
function renderFormatsChart(summary) {
  const ctx = document.getElementById('chartFormats');
  if (!ctx) return;

  const getV = (k) => summary?.[k]?.valor ?? null;
  const mp4   = getV('mp4')   || 27;
  const jpg   = getV('jpg')   || 74;
  const pdf   = getV('pdf')   || 9;
  const otros = getV('otros') || 7;
  const total = mp4 + jpg + pdf + otros;
  const pct = (n) => +((n / total) * 100).toFixed(1);

  chartInstances['formats'] = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: [`MP4 (${pct(mp4)}%)`, `JPG (${pct(jpg)}%)`, `PDF (${pct(pdf)}%)`, `Otros (${pct(otros)}%)`],
      datasets: [{
        data: [mp4, jpg, pdf, otros],
        backgroundColor: ['#EA580C', '#0284C7', '#10B981', '#8B5CF6'],
        borderWidth: 2,
        borderColor: getBorderColor()
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '60%',
      plugins: { legend: { position: 'bottom', labels: { color: getTextColor(), font: { weight: '600', size: 11 } } } }
    }
  });
}

// ─── RETENCIÓN DE VIDEO ───────────────────────────────────────────────────────
function renderRetentionChart() {
  const ctx = document.getElementById('chartVideoRetention');
  if (!ctx) return;

  chartInstances['retention'] = new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['0s', '3s (Hook)', '5s', '10s', '15s', '20s', '30s', '45s+'],
      datasets: [
        {
          label: 'Reels UGC & Humor (68% en 15s)',
          data: [100, 92, 85, 74, 68, 59, 48, 36],
          borderColor: '#10B981',
          backgroundColor: isLightMode() ? 'rgba(16,185,129,0.1)' : 'rgba(16,185,129,0.2)',
          fill: true, tension: 0.3, borderWidth: 3
        },
        {
          label: 'Video Estático Tradicional (14% en 15s)',
          data: [100, 56, 35, 23, 14, 9, 4, 1],
          borderColor: '#EF4444',
          borderDash: [5, 5],
          borderWidth: 2,
          fill: false
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { labels: { color: getTextColor(), font: { weight: '600' } } } },
      scales: {
        x: { grid: { color: getGridColor() }, ticks: { color: getTextColor(), font: { weight: '600' } } },
        y: { grid: { color: getGridColor() }, ticks: { callback: v => `${v}%`, color: getTextColor(), font: { weight: '600' } }, min: 0, max: 100 }
      }
    }
  });
}

// ─── PAUTA CAMPAÑAS ───────────────────────────────────────────────────────────
function renderAdsChart(campanas) {
  const ctx = document.getElementById('chartAdCampaigns');
  if (!ctx) return;

  const labels     = campanas.map(c => c.nombre)      || ['Promo Tricolor (Web)', 'Combos IG (Perfil)'];
  const alcances   = campanas.map(c => c.alcance)     || [8445, 6696];
  const resultados = campanas.map(c => c.resultados)  || [199, 250];

  chartInstances['ads'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Alcance', data: alcances, backgroundColor: '#0284C7', borderRadius: 4, yAxisID: 'y' },
        { label: 'Resultados', data: resultados, backgroundColor: '#EA580C', borderRadius: 4, yAxisID: 'y1' }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { labels: { color: getTextColor(), font: { weight: '600' } } } },
      scales: {
        y:  { position: 'left',  grid: { color: getGridColor() }, ticks: { color: getTextColor(), font: { weight: '600' } } },
        y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { color: '#EA580C', font: { weight: '700' } } }
      }
    }
  });
}

// ─── Opciones base para gráficos de línea ─────────────────────────────────────
function baseLineOptions(yLabel) {
  return {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { labels: { color: getTextColor(), font: { family: "'Inter', sans-serif", weight: '600', size: 12 } } },
      tooltip: { backgroundColor: '#0F172A', titleColor: '#FFFFFF', bodyColor: '#E2E8F0', padding: 12 }
    },
    scales: {
      x: { grid: { color: getGridColor() }, ticks: { color: getTextColor(), font: { size: 11, weight: '600' } } },
      y: { grid: { color: getGridColor() }, ticks: { color: getTextColor(), font: { size: 11, weight: '600' } } }
    }
  };
}
