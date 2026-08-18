/**
 * MARKETING INSIGHTS - ENTREGABLES FILTER & SEARCH ENGINE
 * Manejo interactivo de los 117 entregables de producción gráfica y audiovisual
 */

let entregablesData = [];
let currentPage = 1;
const itemsPerPage = 15;

document.addEventListener('DOMContentLoaded', () => {
  loadEntregablesData();
});

async function loadEntregablesData() {
  try {
    const res = await fetch('/data/raw/entregables_cibergenios_117.csv');
    if (!res.ok) {
      // Fallback si no está el endpoint directo
      renderFallbackTable();
      return;
    }
    const csvText = await res.text();
    parseAndRenderEntregables(csvText);
  } catch (err) {
    console.warn('Cargando datos estáticos de respaldo para la tabla...', err);
    renderFallbackTable();
  }
}

function parseAndRenderEntregables(csvText) {
  const lines = csvText.trim().split('\n');
  entregablesData = [];

  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;

    // Manejar comas en CSV
    const parts = line.split(',');
    if (parts.length >= 5) {
      entregablesData.push({
        id: parseInt(parts[0], 10),
        nombre: parts[1],
        formato: parts[2],
        categoria: parts[3],
        fecha: parts[4]
      });
    }
  }

  setupFilterListeners();
  renderEntregablesTable();
}

function setupFilterListeners() {
  const searchInput = document.getElementById('entregablesSearch');
  const categoriaSelect = document.getElementById('entregablesCategoria');
  const formatoSelect = document.getElementById('entregablesFormato');

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      currentPage = 1;
      renderEntregablesTable();
    });
  }

  if (categoriaSelect) {
    categoriaSelect.addEventListener('change', () => {
      currentPage = 1;
      renderEntregablesTable();
    });
  }

  if (formatoSelect) {
    formatoSelect.addEventListener('change', () => {
      currentPage = 1;
      renderEntregablesTable();
    });
  }
}

function getFilteredEntregables() {
  const searchInput = document.getElementById('entregablesSearch');
  const categoriaSelect = document.getElementById('entregablesCategoria');
  const formatoSelect = document.getElementById('entregablesFormato');

  const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
  const categoria = categoriaSelect ? categoriaSelect.value : 'todas';
  const formato = formatoSelect ? formatoSelect.value : 'todos';

  return entregablesData.filter(item => {
    const matchesQuery = !query || item.nombre.toLowerCase().includes(query) || item.categoria.toLowerCase().includes(query);
    const matchesCategoria = categoria === 'todas' || item.categoria.toLowerCase() === categoria.toLowerCase();
    const matchesFormato = formato === 'todos' || item.formato.toUpperCase() === formato.toUpperCase();
    return matchesQuery && matchesCategoria && matchesFormato;
  });
}

function renderEntregablesTable() {
  const tableBody = document.getElementById('entregablesTableBody');
  const countLabel = document.getElementById('entregablesCountLabel');
  if (!tableBody) return;

  const filtered = getFilteredEntregables();

  if (countLabel) {
    countLabel.textContent = `Mostrando ${filtered.length} de ${entregablesData.length} entregables`;
  }

  const totalPages = Math.ceil(filtered.length / itemsPerPage) || 1;
  if (currentPage > totalPages) currentPage = totalPages;

  const startIdx = (currentPage - 1) * itemsPerPage;
  const pageItems = filtered.slice(startIdx, startIdx + itemsPerPage);

  if (pageItems.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" style="text-align: center; padding: 32px; color: var(--text-muted);">
          No se encontraron entregables con los filtros seleccionados.
        </td>
      </tr>
    `;
    renderPagination(0, 1);
    return;
  }

  let html = '';
  pageItems.forEach(item => {
    let badgeClass = 'badge-jpg';
    if (item.formato.toUpperCase() === 'MP4') badgeClass = 'badge-mp4';
    else if (item.formato.toUpperCase() === 'PDF') badgeClass = 'badge-pdf';
    else if (item.formato.toUpperCase().includes('WORD') || item.formato.toUpperCase().includes('DOC')) badgeClass = 'badge-word';

    html += `
      <tr>
        <td style="font-weight: 700; color: var(--text-dim);">#${item.id}</td>
        <td style="font-weight: 600; color: var(--text-main);">${escapeHtml(item.nombre)}</td>
        <td><span class="badge-format ${badgeClass}">${escapeHtml(item.formato)}</span></td>
        <td><span class="badge-category">${escapeHtml(item.categoria)}</span></td>
        <td style="color: var(--text-muted); font-size: 12px;">${escapeHtml(item.fecha)}</td>
      </tr>
    `;
  });

  tableBody.innerHTML = html;
  renderPagination(filtered.length, totalPages);
}

function renderPagination(totalItems, totalPages) {
  const paginationContainer = document.getElementById('entregablesPagination');
  if (!paginationContainer) return;

  if (totalPages <= 1) {
    paginationContainer.innerHTML = '';
    return;
  }

  let html = `
    <div class="pagination-buttons">
      <button class="page-btn" onclick="changeEntregablesPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>Anterior</button>
  `;

  for (let i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
      html += `
        <button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="changeEntregablesPage(${i})">${i}</button>
      `;
    } else if (i === currentPage - 2 || i === currentPage + 2) {
      html += `<span style="padding: 4px; color: var(--text-dim);">...</span>`;
    }
  }

  html += `
      <button class="page-btn" onclick="changeEntregablesPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>Siguiente</button>
    </div>
  `;

  paginationContainer.innerHTML = html;
}

window.changeEntregablesPage = function(page) {
  currentPage = page;
  renderEntregablesTable();
};

function escapeHtml(text) {
  if (!text) return '';
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function renderFallbackTable() {
  // En caso de que se use en preview estático
  entregablesData = [
    { id: 1, nombre: "T&C Polla Colombia vs Ghana V1 MLCG.docx", formato: "Word", categoria: "Concursos", fecha: "01/07/2026" },
    { id: 4, nombre: "Cartelera Semanal Banner Web", formato: "JPG", categoria: "Cartelera", fecha: "02/07/2026" },
    { id: 8, nombre: "Cartelera Semanal Pantallas TV girado", formato: "MP4", categoria: "Cartelera", fecha: "02/07/2026" },
    { id: 9, nombre: "Cartelera Semanal Pantallas Vertical & Reels", formato: "MP4", categoria: "Cartelera", fecha: "02/07/2026" },
    { id: 11, nombre: "Promocion Preventa Moana Feed", formato: "JPG", categoria: "Promoción", fecha: "02/07/2026" },
    { id: 16, nombre: "Imprmible Laberinto Minions CMYK", formato: "PDF", categoria: "POP", fecha: "03/07/2026" },
    { id: 19, nombre: "Videos de promoción 50% (martes y miércoles)", formato: "MP4", categoria: "Promoción", fecha: "07/07/2026" },
    { id: 33, nombre: "Promoción Convenio Policia Nacional", formato: "JPG", categoria: "Convenios", fecha: "14/07/2026" },
    { id: 57, nombre: "Video Promoción Mohana Farock", formato: "MP4", categoria: "Promoción", fecha: "16/07/2026" },
    { id: 61, nombre: "Piezas Promo Kit Spiderman Redes Feed", formato: "JPG", categoria: "Promoción", fecha: "17/07/2026" },
    { id: 81, nombre: "Promocion Convenio Fuerza Aerea Redes Feed", formato: "JPG", categoria: "Convenios", fecha: "23/07/2026" },
    { id: 110, nombre: "Video UGC Spiderman Trabajando", formato: "MP4", categoria: "Promoción", fecha: "29/07/2026" },
    { id: 116, nombre: "Pieza Celebración Llaneridad Corculla Redes Feed", formato: "JPG", categoria: "Otros", fecha: "31/07/2026" }
  ];
  setupFilterListeners();
  renderEntregablesTable();
}
