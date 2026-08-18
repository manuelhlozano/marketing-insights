/**
 * MARKETING INSIGHTS - EXECUTIVE CHARTS & GOAL RINGS ENGINE
 * Soporta modo claro/oscuro con alto contraste y colores vivos
 */

document.addEventListener('DOMContentLoaded', () => {
  initAllCharts();
});

function isLightMode() {
  return document.body.classList.contains('light-mode');
}

function getTextColor() {
  return isLightMode() ? '#0F172A' : '#F8FAFC';
}

function getGridColor() {
  return isLightMode() ? 'rgba(0, 0, 0, 0.08)' : 'rgba(255, 255, 255, 0.08)';
}

let chartInstances = {};

function initAllCharts() {
  // Destruir instancias existentes si se cambia de tema
  Object.values(chartInstances).forEach(c => c && typeof c.destroy === 'function' && c.destroy());
  chartInstances = {};

  initGoalRings();
  initMetaGrowthChart();
  initTikTokChart();
  initDemographicsChart();
  initCitiesChart();
  initFormatsChart();
  initVideoRetentionChart();
  initAdCampaignsChart();
}

/**
 * Anillos de Progreso y Metas (Goal Rings)
 */
function initGoalRings() {
  renderRing('ringGoogleGoal', 88, '#EA580C');
  renderRing('ringInstagramGoal', 15, '#C026D3');
  renderRing('ringFacebookGoal', 92, '#1D4ED8');
}

function renderRing(canvasId, percentage, color) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;

  const emptyColor = isLightMode() ? '#E2E8F0' : '#1E293B';

  chartInstances[canvasId] = new Chart(ctx, {
    type: 'doughnut',
    data: {
      datasets: [{
        data: [percentage, 100 - percentage],
        backgroundColor: [color, emptyColor],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '76%',
      plugins: {
        legend: { display: false },
        tooltip: { enabled: false }
      }
    }
  });
}

/**
 * Gráfico 1: Meta Growth (Facebook + Instagram)
 */
function initMetaGrowthChart() {
  const ctx = document.getElementById('chartMetaGrowth');
  if (!ctx) return;

  chartInstances['metaGrowth'] = new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['1 jul', '6 jul', '11 jul', '15 jul (Control)', '20 jul (Pico)', '26 jul', '30 jul (Estreno)', '31 jul'],
      datasets: [
        {
          label: 'Visualizaciones Meta',
          data: [2500, 3200, 2800, 4800, 16800, 2100, 19500, 9200],
          borderColor: '#0284C7',
          backgroundColor: isLightMode() ? 'rgba(2, 132, 199, 0.12)' : 'rgba(2, 132, 199, 0.25)',
          fill: true,
          tension: 0.35,
          borderWidth: 3,
          pointBackgroundColor: '#0284C7',
          pointBorderColor: '#FFFFFF',
          pointBorderWidth: 2,
          pointRadius: 5
        },
        {
          label: 'Espectadores Únicos',
          data: [800, 1200, 1500, 1800, 9500, 1100, 3800, 1900],
          borderColor: '#8B5CF6',
          backgroundColor: 'transparent',
          tension: 0.35,
          borderWidth: 2.5,
          pointBackgroundColor: '#8B5CF6',
          pointRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: { color: getTextColor(), font: { family: "'Inter', sans-serif", weight: '600', size: 12 } }
        },
        tooltip: {
          backgroundColor: '#0F172A',
          titleColor: '#FFFFFF',
          bodyColor: '#E2E8F0',
          padding: 12
        }
      },
      scales: {
        x: {
          grid: { color: getGridColor() },
          ticks: { color: getTextColor(), font: { size: 11, weight: '600' } }
        },
        y: {
          grid: { color: getGridColor() },
          ticks: { color: getTextColor(), font: { size: 11, weight: '600' } }
        }
      }
    }
  });
}

/**
 * Gráfico 2: TikTok Growth
 */
function initTikTokChart() {
  const ctx = document.getElementById('chartTikTokGrowth');
  if (!ctx) return;

  chartInstances['tiktokGrowth'] = new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['1-5 Jul', '6-10 Jul', '11-15 Jul', '16-20 Jul', '21-23 Jul', '24-27 Jul (Cibergenios)', '28-31 Jul (Viral)'],
      datasets: [
        {
          label: 'Visualizaciones TikTok',
          data: [3500, 2800, 2100, 1900, 2400, 32500, 47900],
          borderColor: '#06B6D4',
          backgroundColor: isLightMode() ? 'rgba(6, 182, 212, 0.12)' : 'rgba(6, 182, 212, 0.25)',
          fill: true,
          tension: 0.35,
          borderWidth: 3,
          pointBackgroundColor: '#06B6D4',
          pointBorderColor: '#FFFFFF',
          pointRadius: 5
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: { color: getTextColor(), font: { weight: '600', size: 12 } }
        }
      },
      scales: {
        x: { grid: { color: getGridColor() }, ticks: { color: getTextColor(), font: { weight: '600' } } },
        y: { grid: { color: getGridColor() }, ticks: { color: getTextColor(), font: { weight: '600' } } }
      }
    }
  });
}

/**
 * Gráfico 3: Demografía de Audiencia (Edad y Género)
 */
function initDemographicsChart() {
  const ctx = document.getElementById('chartDemographics');
  if (!ctx) return;

  chartInstances['demographics'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['18-24', '25-34', '35-44', '45-54', '55-64', '65+'],
      datasets: [
        {
          label: 'Mujeres (57.6%)',
          data: [3.3, 26.2, 18.8, 6.9, 1.8, 0.6],
          backgroundColor: '#0284C7',
          borderRadius: 4
        },
        {
          label: 'Hombres (42.4%)',
          data: [2.6, 19.6, 13.9, 4.8, 1.2, 0.5],
          backgroundColor: '#EA580C',
          borderRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { labels: { color: getTextColor(), font: { weight: '600' } } }
      },
      scales: {
        x: { grid: { color: getGridColor() }, ticks: { color: getTextColor(), font: { weight: '600' } } },
        y: {
          grid: { color: getGridColor() },
          ticks: { callback: (v) => `${v}%`, color: getTextColor(), font: { weight: '600' } }
        }
      }
    }
  });
}

/**
 * Gráfico 4: Principales Ciudades
 */
function initCitiesChart() {
  const ctx = document.getElementById('chartCities');
  if (!ctx) return;

  chartInstances['cities'] = new Chart(ctx, {
    type: 'bar',
    indexAxis: 'y',
    data: {
      labels: ['Villavicencio (Meta)', 'Bogotá D.C.', 'Acacías (Meta)', 'Granada (Meta)', 'Medellín', 'Restrepo (Meta)'],
      datasets: [
        {
          label: '% Audiencia',
          data: [47.4, 6.2, 2.3, 0.9, 0.8, 0.8],
          backgroundColor: '#0284C7',
          borderRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: {
          grid: { color: getGridColor() },
          ticks: { callback: (v) => `${v}%`, color: getTextColor(), font: { weight: '600' } }
        },
        y: { grid: { color: getGridColor() }, ticks: { color: getTextColor(), font: { weight: '600' } } }
      }
    }
  });
}

/**
 * Gráfico 5: Distribución de Formatos
 */
function initFormatsChart() {
  const ctx = document.getElementById('chartFormats');
  if (!ctx) return;

  chartInstances['formats'] = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Reels (40% Promo)', 'Cartelera (38%)', 'Fidelización/UGC (22%)'],
      datasets: [{
        data: [40, 38, 22],
        backgroundColor: ['#EA580C', '#0284C7', '#10B981'],
        borderWidth: 2,
        borderColor: isLightMode() ? '#FFFFFF' : '#131B2E'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: { position: 'bottom', labels: { color: getTextColor(), font: { weight: '600', size: 11 } } }
      }
    }
  });
}

/**
 * Gráfico 6: Retención de Video
 */
function initVideoRetentionChart() {
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
          backgroundColor: isLightMode() ? 'rgba(16, 185, 129, 0.1)' : 'rgba(16, 185, 129, 0.2)',
          fill: true,
          tension: 0.3,
          borderWidth: 3
        },
        {
          label: 'Video Estático Tradicional (14% en 15s)',
          data: [100, 56, 35, 23, 14, 9, 4, 1],
          borderColor: '#EF4444',
          borderDash: [5, 5],
          borderWidth: 2
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { labels: { color: getTextColor(), font: { weight: '600' } } } },
      scales: {
        x: { grid: { color: getGridColor() }, ticks: { color: getTextColor(), font: { weight: '600' } } },
        y: {
          grid: { color: getGridColor() },
          ticks: { callback: (v) => `${v}%`, color: getTextColor(), font: { weight: '600' } }
        }
      }
    }
  });
}

/**
 * Gráfico 7: Pauta Digital
 */
function initAdCampaignsChart() {
  const ctx = document.getElementById('chartAdCampaigns');
  if (!ctx) return;

  chartInstances['ads'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Promo Tricolor (Web)', 'Combos IG (Perfil)'],
      datasets: [
        {
          label: 'Alcance',
          data: [8445, 6696],
          backgroundColor: '#0284C7',
          borderRadius: 4,
          yAxisID: 'y'
        },
        {
          label: 'Acciones de Alto Valor',
          data: [199, 250],
          backgroundColor: '#EA580C',
          borderRadius: 4,
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { labels: { color: getTextColor(), font: { weight: '600' } } } },
      scales: {
        y: {
          position: 'left',
          grid: { color: getGridColor() },
          ticks: { color: getTextColor(), font: { weight: '600' } }
        },
        y1: {
          position: 'right',
          grid: { drawOnChartArea: false },
          ticks: { color: '#EA580C', font: { weight: '700' } }
        }
      }
    }
  });
}

// Re-renderizar gráficos al cambiar el tema para actualizar el contraste
window.updateChartsTheme = function() {
  initAllCharts();
};
