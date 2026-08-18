/**
 * MARKETING INSIGHTS - INTERACTIVE CHARTS ENGINE (Chart.js)
 * Creado con los datos reales de Cine Múltiplex Villacentro (Julio 2026)
 */

document.addEventListener('DOMContentLoaded', () => {
  initMetaGrowthChart();
  initTikTokChart();
  initDemographicsChart();
  initCitiesChart();
  initFormatsChart();
  initVideoRetentionChart();
  initAdCampaignsChart();
});

const chartCommonOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: {
        color: '#9CA3AF',
        font: { family: "'Inter', sans-serif", size: 12 }
      }
    },
    tooltip: {
      backgroundColor: '#1F2937',
      titleColor: '#F9FAFB',
      bodyColor: '#D1D5DB',
      borderColor: 'rgba(255,255,255,0.1)',
      borderWidth: 1,
      padding: 12,
      cornerRadius: 8
    }
  },
  scales: {
    x: {
      grid: { color: 'rgba(255, 255, 255, 0.05)' },
      ticks: { color: '#9CA3AF', font: { family: "'Inter', sans-serif", size: 11 } }
    },
    y: {
      grid: { color: 'rgba(255, 255, 255, 0.05)' },
      ticks: { color: '#9CA3AF', font: { family: "'Inter', sans-serif", size: 11 } }
    }
  }
};

/**
 * Gráfico 1: Meta Growth (Facebook + Instagram Visualizaciones & Espectadores Únicos)
 */
function initMetaGrowthChart() {
  const ctx = document.getElementById('chartMetaGrowth');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['1 jul', '6 jul', '11 jul', '15 jul (Control)', '20 jul (Pico)', '26 jul', '30 jul (Estreno)', '31 jul'],
      datasets: [
        {
          label: 'Visualizaciones Totales Meta',
          data: [2500, 3200, 2800, 4800, 16800, 2100, 19500, 9200],
          borderColor: '#0284C7',
          backgroundColor: 'rgba(2, 132, 199, 0.15)',
          fill: true,
          tension: 0.4,
          borderWidth: 3,
          pointBackgroundColor: '#38BDF8',
          pointRadius: 4
        },
        {
          label: 'Espectadores Únicos',
          data: [800, 1200, 1500, 1800, 9500, 1100, 3800, 1900],
          borderColor: '#8B5CF6',
          backgroundColor: 'rgba(139, 92, 246, 0.05)',
          fill: true,
          tension: 0.4,
          borderWidth: 2,
          pointBackgroundColor: '#A78BFA',
          pointRadius: 3
        }
      ]
    },
    options: {
      ...chartCommonOptions,
      plugins: {
        ...chartCommonOptions.plugins,
        title: {
          display: true,
          text: 'Evolución de Alcance e Impacto Diario (Pico del 20 y 30 de Julio)',
          color: '#E5E7EB',
          font: { family: "'Outfit', sans-serif", size: 14, weight: 600 }
        }
      }
    }
  });
}

/**
 * Gráfico 2: TikTok - Inactividad vs Subida Vertical (Rescate en 7 Días)
 */
function initTikTokChart() {
  const ctx = document.getElementById('chartTikTokGrowth');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['1-5 Jul', '6-10 Jul', '11-15 Jul', '16-20 Jul', '21-23 Jul', '24-27 Jul (Cibergenios)', '28-31 Jul (Viral)'],
      datasets: [
        {
          label: 'Visualizaciones TikTok',
          data: [3500, 2800, 2100, 1900, 2400, 32500, 47900],
          borderColor: '#06B6D4',
          backgroundColor: 'rgba(6, 182, 212, 0.2)',
          fill: true,
          tension: 0.35,
          borderWidth: 3,
          pointBackgroundColor: '#00F2FE',
          pointRadius: 5
        }
      ]
    },
    options: {
      ...chartCommonOptions,
      plugins: {
        ...chartCommonOptions.plugins,
        title: {
          display: true,
          text: 'TikTok: De 2.000 vistas a 93.100 en la última semana de Julio',
          color: '#E5E7EB',
          font: { family: "'Outfit', sans-serif", size: 14, weight: 600 }
        }
      }
    }
  });
}

/**
 * Gráfico 3: Demografía de Audiencia (Pirámide de Edad y Género)
 */
function initDemographicsChart() {
  const ctx = document.getElementById('chartDemographics');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['18-24', '25-34', '35-44', '45-54', '55-64', '65+'],
      datasets: [
        {
          label: 'Mujeres (57.6%)',
          data: [3.3, 26.2, 18.8, 6.9, 1.8, 0.6],
          backgroundColor: '#38BDF8',
          borderRadius: 6
        },
        {
          label: 'Hombres (42.4%)',
          data: [2.6, 19.6, 13.9, 4.8, 1.2, 0.5],
          backgroundColor: '#0284C7',
          borderRadius: 6
        }
      ]
    },
    options: {
      ...chartCommonOptions,
      scales: {
        ...chartCommonOptions.scales,
        y: {
          ...chartCommonOptions.scales.y,
          ticks: { callback: (val) => `${val}%`, color: '#9CA3AF' }
        }
      }
    }
  });
}

/**
 * Gráfico 4: Principales Ciudades (Top Geo Target)
 */
function initCitiesChart() {
  const ctx = document.getElementById('chartCities');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'bar',
    indexAxis: 'y',
    data: {
      labels: ['Villavicencio (Meta)', 'Bogotá D.C.', 'Acacías (Meta)', 'Granada (Meta)', 'Medellín', 'Restrepo (Meta)'],
      datasets: [
        {
          label: '% de la Audiencia',
          data: [47.4, 6.2, 2.3, 0.9, 0.8, 0.8],
          backgroundColor: [
            '#0284C7',
            '#0EA5E9',
            '#38BDF8',
            '#7DD3FC',
            '#BAE6FD',
            '#E0F2FE'
          ],
          borderRadius: 6
        }
      ]
    },
    options: {
      ...chartCommonOptions,
      plugins: {
        ...chartCommonOptions.plugins,
        legend: { display: false }
      },
      scales: {
        ...chartCommonOptions.scales,
        x: {
          ...chartCommonOptions.scales.x,
          ticks: { callback: (val) => `${val}%`, color: '#9CA3AF' }
        }
      }
    }
  });
}

/**
 * Gráfico 5: Distribución de Formatos (Mix de Producción)
 */
function initFormatsChart() {
  const ctx = document.getElementById('chartFormats');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Reels (40% Promo / Video Corto)', 'Cartelera & Mantenimiento (38%)', 'Fidelización & UGC (22%)'],
      datasets: [
        {
          data: [40, 38, 22],
          backgroundColor: ['#F97316', '#0284C7', '#10B981'],
          borderColor: '#111827',
          borderWidth: 3
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: '#9CA3AF', font: { size: 11 } }
        },
        tooltip: {
          callbacks: {
            label: (item) => ` ${item.label}: ${item.raw}% de la producción`
          }
        }
      },
      cutout: '70%'
    }
  });
}

/**
 * Gráfico 6: Curva de Retención de Video (Segundos / Intervalos)
 */
function initVideoRetentionChart() {
  const ctx = document.getElementById('chartVideoRetention');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['0s (Inicio)', '3s (Hook)', '5s', '10s', '15s', '20s', '30s', '45s+'],
      datasets: [
        {
          label: 'Reels UGC & Humor (Spiderman/Moana)',
          data: [100, 92, 85, 74, 68, 59, 48, 36],
          borderColor: '#10B981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          fill: true,
          tension: 0.35,
          borderWidth: 2,
          pointRadius: 4
        },
        {
          label: 'Video Estático Tradicional',
          data: [100, 56, 35, 23, 14, 9, 4, 1],
          borderColor: '#EF4444',
          borderDash: [5, 5],
          tension: 0.35,
          borderWidth: 2,
          pointRadius: 3
        }
      ]
    },
    options: {
      ...chartCommonOptions,
      scales: {
        ...chartCommonOptions.scales,
        y: {
          ...chartCommonOptions.scales.y,
          ticks: { callback: (val) => `${val}%`, color: '#9CA3AF' }
        }
      }
    }
  });
}

/**
 * Gráfico 7: Pauta Digital (Gasto vs Acciones de Alto Valor)
 */
function initAdCampaignsChart() {
  const ctx = document.getElementById('chartAdCampaigns');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Promo Tricolor (Web)', 'Combos IG (Perfil)'],
      datasets: [
        {
          label: 'Alcance (Personas)',
          data: [8445, 6696],
          backgroundColor: 'rgba(2, 132, 199, 0.6)',
          borderRadius: 6,
          yAxisID: 'y'
        },
        {
          label: 'Acciones de Alto Valor',
          data: [199, 250],
          backgroundColor: '#F97316',
          borderRadius: 6,
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      ...chartCommonOptions,
      scales: {
        y: {
          type: 'linear',
          display: true,
          position: 'left',
          grid: { color: 'rgba(255, 255, 255, 0.05)' },
          ticks: { color: '#9CA3AF' }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          grid: { drawOnChartArea: false },
          ticks: { color: '#F97316' }
        }
      }
    }
  });
}
