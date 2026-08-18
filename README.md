# 📊 Marketing Insights Platform (Laravel & Executive Dashboard)

Plataforma jerárquica de inteligencia, analítica ejecutiva y visualización de marketing multicanal desarrollada con arquitectura multitenant en **Laravel** y frontend interactivo de alto impacto visual (estética inspirada en el informe de gestión de *Cibergenios Agencia Digital SAS* para *Cine Múltiplex Villacentro*).

---

## 🏛️ 1. Arquitectura Jerárquica Multitenant

```
Empresas (Clientes) ──> Usuarios (Roles & Auth) ──> Dashboards (Periodos / Campañas) ──> Módulos & Indicadores
                                                                   └──> 117 Entregables de Producción
                                                                   └──> Ingesta de Archivos (CSV/JSON)
```

- **Empresas / Clientes:** Registro de clientes corporativos (ej. `Cine Múltiplex Villacentro`), marcas, configuraciones de branding y Token Maestro.
- **Dashboards:** Informes de gestión mensual/trimestral por cliente con Token de Invitado público (`mkt_live_...`).
- **Módulos & Indicadores:** Métricas dinámicas, series temporales, curvas de retención, segmentaciones demográficas y embudos de conversión.
- **Catálogo de Entregables:** Inventario interactivo de las 117 piezas gráficas y audiovisuales producidas por la agencia con búsqueda en tiempo real, filtros por categoría y formato.
- **Motor de Ingesta:** Carga y procesamiento automático de archivos planos (Meta Business Suite CSV, Instagram Posts/Stories CSV, TikTok Analytics, Brevo Email Marketing B2C/B2B y Meta Ads).

---

## 🚀 2. Enlaces y Acceso al Dashboard

- **Vista Pública con Token de Invitado para Clientes:**
  `http://localhost:8000/dashboard/cine-multiplex-villacentro/julio-2026?token=mkt_live_cmv_78a9c0f`
- **Selector de Dashboards / Empresas:**
  `http://localhost:8000/dashboards`
- **Panel de Ingesta & Carga de Archivos Planos:**
  `http://localhost:8000/admin/dashboard/1/import`
- **API JSON de Datos por Token:**
  `http://localhost:8000/api/v1/dashboard/token/mkt_live_cmv_78a9c0f`
- **Vista Autónoma / Standalone Client:**
  Abrir directamente `public/index.html` en cualquier navegador web o servidor local.

---

## 📈 3. Resumen de Métricas & Hitos Auditados (Julio 2026)

| Canal / Módulo | Métricas Clave Auditadas | Impacto Comercial en CMV |
| :--- | :--- | :--- |
| **Meta Consolidado** | **144.000 visualizaciones** (107.196 IG / 36.783 FB), 30.838 espectadores únicos (+15.3%). | Exposición masiva de cartelera y confitería. |
| **TikTok** | **93.100 vistas** logradas en 7 días tras toma de control, **963 compartidos** (ROI viral), 1.6K likes. | Activación masiva de recomendación boca a boca. |
| **Comunidad Total** | **54.170 seguidores** (50.125 Facebook / 4.045 Instagram), +289 crecimiento neto (+15.6%). | Freno a la fuga de clientes (94 unfollows controlados). |
| **Producción de Video** | **27 Videos MP4** (40% Promocional, 38% Cartelera, 22% UGC/Fidelización). | Los Reels multiplicaron x5 el impacto frente a fotos estáticas. |
| **Showcase UGC** | *Spiderman Farock* (+47.5K vistas / 67 shares), *Moana* (+42.1K), *Outfit* (+39.5K / 100 shares). | Conexión con público joven mediante talento local y humor. |
| **Email Marketing (Brevo)** | **B2C:** 55.422 entregados (18% Open Rate / 9.976 lecturas, 249 clics). **B2B:** 415 empresas (30.84% Open Rate, 25 prospectos cotización). | Activación de base fidelizada y generación de clientes corporativos. |
| **Pauta Digital** | Inversión $50.016 COP, 20.381 impresiones, **449 acciones de alto valor a $112,82 COP / resultado**. | Máxima eficiencia de micro-presupuesto. |
| **Atención CRM** | Reducción de tiempo de respuesta en Instagram a **40 minutos** (76.2% de índice). | Rescate de intenciones de compra en taquilla. |
| **Entregables** | **117 piezas gráficas y audiovisuales entregadas** (2 días/semana cobertura in situ). | Superación de la cuota operativa base de la agencia. |

---

## 🛠️ 4. Estructura de Directorios

```
MktInsights/
├── app/
│   ├── Http/Controllers/
│   │   ├── DashboardPublicController.php
│   │   ├── DashboardAdminController.php
│   │   ├── IngestionController.php
│   │   └── EntregablesApiController.php
│   ├── Models/
│   │   ├── Empresa.php
│   │   ├── Dashboard.php
│   │   ├── ModuloIndicador.php
│   │   ├── DatoIndicador.php
│   │   ├── Entregable.php
│   │   └── IngestaArchivo.php
│   └── Services/
│       ├── DataIngestionService.php
│       └── MetricsAggregatorService.php
├── database/
│   ├── migrations/
│   └── seeders/
│       └── CineMultiplexJulio2026Seeder.php
├── public/
│   ├── index.html                   <-- Aplicación cliente interactiva completa
│   ├── css/executive-dashboard.css  <-- Sistema de diseño ejecutivo
│   ├── js/dashboard-charts.js       <-- Gráficos Chart.js interactivos
│   ├── js/entregables-filter.js     <-- Buscador y filtros de 117 entregables
│   └── data/raw/                    <-- Insumos CSV y JSON originales
├── resources/
│   └── views/                       <-- Vistas y layouts Blade modulares
└── routes/
    ├── web.php
    └── api.php
```

---

## 💻 5. Ejecución Local

### Opción A: Servidor Web Estándar / Standalone
```bash
# Servir la carpeta public con cualquier servidor web o Node:
npx serve public
# O abrir public/index.html directamente en el navegador
```

### Opción B: Entorno Laravel
```bash
composer install
php artisan migrate --seed
php artisan serve
```

---
*Desarrollado con orgullo para **Cine Múltiplex Villacentro** y **Cibergenios Agencia Digital SAS**.*
