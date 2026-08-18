-- =============================================================================
-- MARKETING INSIGHTS — SCHEMA AMPLIADO + SEED JULIO 2026
-- BD: wwcibe_mktinsights
-- Ejecutar SOLO en wwcibe_mktinsights, NO tocar otras BDs
-- =============================================================================

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLAS BASE (ya existentes — idempotentes)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'superadmin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Superadmin (password: Dieg0$m1 → bcrypt)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Webmaster Cibergenios', 'webmaster@cibergenios.com',
 '$2y$10$TKh8H1.PyfSi8Eq/.oomuuEPbyZVrX/hGb5OBsR.SH1XQFB3DhWi2', 'superadmin')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `password` = VALUES(`password`);

CREATE TABLE IF NOT EXISTS `empresas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `sector` varchar(255) DEFAULT NULL,
  `ciudad` varchar(255) DEFAULT NULL,
  `pais` varchar(255) DEFAULT 'Colombia',
  `logo_light_url` varchar(500) DEFAULT NULL,
  `logo_dark_url` varchar(500) DEFAULT NULL,
  `token_acceso_maestro` varchar(255) NOT NULL UNIQUE,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `empresas` (`id`, `nombre`, `slug`, `sector`, `ciudad`, `pais`, `logo_light_url`, `logo_dark_url`, `token_acceso_maestro`, `activo`) VALUES
(1, 'Cine Múltiplex Villacentro', 'cine-multiplex-villacentro', 'Salas de Cine & Entretenimiento',
 'Villavicencio, Meta', 'Colombia',
 'assets/images/logo_multiplex_light.png', 'assets/images/logo_multiplex_dark.png',
 'mkt_live_cmv_78a9c0f', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `logo_light_url` = VALUES(`logo_light_url`), `logo_dark_url` = VALUES(`logo_dark_url`);

CREATE TABLE IF NOT EXISTS `dashboards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `periodo` varchar(100) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `lema` varchar(500) DEFAULT NULL,
  `descripcion_ejecutiva` text DEFAULT NULL,
  `resumen_aprendizaje` text DEFAULT NULL,
  `public_token` varchar(255) NOT NULL UNIQUE,
  `es_publico` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_slug` (`empresa_id`, `slug`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dashboards` (`id`, `empresa_id`, `titulo`, `slug`, `periodo`, `fecha_inicio`, `fecha_fin`, `lema`, `descripcion_ejecutiva`, `resumen_aprendizaje`, `public_token`, `es_publico`) VALUES
(1, 1,
 'Informe Ejecutivo de Gestión Mensual | Julio 2026',
 'julio-2026', 'Julio 2026', '2026-07-01', '2026-07-31',
 'Toma de Control y Explosión de Crecimiento',
 'Auditoría inmediata, reestructuración estratégica hacia formatos de video corto (Reels / TikToks UGC) y reactivación integral de la conversión en taquilla y confitería para Cine Múltiplex Villacentro.',
 'El aprendizaje más grande que nos dejan los datos de julio es que el formato dictamina el éxito. Nuestra decisión de migrar el esfuerzo hacia la producción de videos UGC y Reels permitió frenar en solo 15 días la caída de la comunidad y reactivar la conversión directa a taquilla.',
 'mkt_live_cmv_78a9c0f', 1),
(2, 1,
 'Informe de Rendimiento | Abril 2026',
 'abril-2026', 'Abril 2026', '2026-04-01', '2026-04-30',
 'Análisis de Desempeño Histórico',
 'Informe de desempeño mensual anterior para comparativa histórica.',
 NULL, 'mkt_live_cmv_abril2026', 1)
ON DUPLICATE KEY UPDATE `titulo` = VALUES(`titulo`), `lema` = VALUES(`lema`),
  `descripcion_ejecutiva` = VALUES(`descripcion_ejecutiva`), `resumen_aprendizaje` = VALUES(`resumen_aprendizaje`);

CREATE TABLE IF NOT EXISTS `modulos_indicadores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `codigo` varchar(100) NOT NULL,
  `tipo_visualizacion` varchar(50) NOT NULL,
  `orden` int NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dashboard_codigo` (`dashboard_id`, `codigo`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `modulos_indicadores` (`dashboard_id`, `nombre`, `codigo`, `tipo_visualizacion`, `orden`, `activo`) VALUES
(1, 'Timeline de Transición Operativa',  'timeline',       'timeline_phases',  1, 1),
(1, 'Google Mi Negocio & Búsquedas',    'google',         'table_comparison', 2, 1),
(1, 'Rendimiento Meta (FB + IG)',        'meta',           'line_chart',       3, 1),
(1, 'TikTok & Viralidad',               'tiktok',         'line_chart',       4, 1),
(1, 'Showcase UGC & Creadores',         'ugc',            'cards_grid',       5, 1),
(1, 'Pauta Digital Meta Ads',           'pauta',          'bar_chart',        6, 1),
(1, 'Email Marketing Brevo',            'email',          'stat_cards',       7, 1),
(1, 'Catálogo de 117 Entregables',      'entregables',    'data_table',       8, 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `activo` = VALUES(`activo`);

-- ─────────────────────────────────────────────────────────────────────────────
-- NUEVAS TABLAS
-- ─────────────────────────────────────────────────────────────────────────────

-- Métricas por canal (key-value flexible)
CREATE TABLE IF NOT EXISTS `metricas_canal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `canal` varchar(100) NOT NULL COMMENT 'google|meta|tiktok|pauta|email_b2c|email_b2b|entregables|comunidad',
  `clave` varchar(100) NOT NULL,
  `etiqueta` varchar(255) DEFAULT NULL,
  `valor_numerico` decimal(15,4) DEFAULT NULL,
  `valor_texto` varchar(500) DEFAULT NULL,
  `comparativo_label` varchar(100) DEFAULT NULL COMMENT 'vs mes anterior / objetivo',
  `comparativo_valor` decimal(10,4) DEFAULT NULL COMMENT '% variación',
  `unidad` varchar(50) DEFAULT NULL COMMENT 'COP|%|num',
  `orden` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dash_canal_clave` (`dashboard_id`, `canal`, `clave`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Series de tiempo para gráficos de línea
CREATE TABLE IF NOT EXISTS `series_tiempo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `canal` varchar(100) NOT NULL,
  `serie` varchar(100) NOT NULL COMMENT 'nombre de la serie/dataset',
  `periodo_label` varchar(100) NOT NULL COMMENT 'etiqueta del eje X',
  `valor` decimal(15,2) NOT NULL DEFAULT 0,
  `orden` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts UGC / Creadores
CREATE TABLE IF NOT EXISTS `ugc_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `canal` varchar(50) NOT NULL DEFAULT 'tiktok' COMMENT 'tiktok|instagram|facebook',
  `vistas` bigint DEFAULT NULL,
  `compartidos` int DEFAULT NULL,
  `likes` int DEFAULT NULL,
  `badge_label` varchar(100) DEFAULT NULL COMMENT 'ej: #1 Video del Mes',
  `nota_estrategica` text DEFAULT NULL,
  `orden` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demografía de audiencia
CREATE TABLE IF NOT EXISTS `audiencia_demografica` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `tipo` varchar(50) NOT NULL COMMENT 'edad_genero|ciudad',
  `etiqueta` varchar(100) NOT NULL,
  `valor_mujeres` decimal(6,2) DEFAULT NULL,
  `valor_hombres` decimal(6,2) DEFAULT NULL,
  `valor_total` decimal(6,2) DEFAULT NULL COMMENT 'para ciudades',
  `orden` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hitos de Timeline
CREATE TABLE IF NOT EXISTS `hitos_timeline` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `periodo` varchar(100) NOT NULL,
  `fase` varchar(255) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `hito_clave` varchar(255) DEFAULT NULL,
  `orden` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entregables
CREATE TABLE IF NOT EXISTS `entregables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `numero_item` int DEFAULT NULL,
  `nombre` varchar(500) NOT NULL,
  `formato` varchar(50) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `fecha_creacion` date DEFAULT NULL,
  `dimensiones` varchar(100) DEFAULT NULL,
  `duracion_segundos` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dash_num` (`dashboard_id`, `numero_item`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campañas de pauta (sub-detalle)
CREATE TABLE IF NOT EXISTS `campanas_pauta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `objetivo` varchar(100) DEFAULT NULL,
  `plataforma` varchar(50) DEFAULT 'meta',
  `inversion_cop` decimal(12,2) DEFAULT NULL,
  `alcance` int DEFAULT NULL,
  `impresiones` int DEFAULT NULL,
  `resultados` int DEFAULT NULL,
  `tipo_resultado` varchar(100) DEFAULT NULL,
  `cpr` decimal(10,2) DEFAULT NULL,
  `orden` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- SEED: DATOS JULIO 2026 — Cine Múltiplex Villacentro
-- =============================================================================

-- ─── HITOS TIMELINE ──────────────────────────────────────────────────────────
DELETE FROM `hitos_timeline` WHERE `dashboard_id` = 1;
INSERT INTO `hitos_timeline` (`dashboard_id`, `periodo`, `fase`, `descripcion`, `hito_clave`, `orden`) VALUES
(1, '24-30 Jun', 'Empalme parcial', 'Período de transición y entrega de accesos', NULL, 1),
(1, '1-14 Jul',  'Diseño & estrategia', 'Auditoría, rediseño de contenidos y plan editorial', NULL, 2),
(1, '15-31 Jul', 'Control total Cibergenios', 'Producción 100% bajo gestión de Cibergenios', '15 de Julio: Control 100%', 3);

-- ─── MÉTRICAS POR CANAL ──────────────────────────────────────────────────────
DELETE FROM `metricas_canal` WHERE `dashboard_id` = 1;

-- GOOGLE BUSINESS
INSERT INTO `metricas_canal` (`dashboard_id`, `canal`, `clave`, `etiqueta`, `valor_numerico`, `valor_texto`, `comparativo_label`, `comparativo_valor`, `unidad`, `orden`) VALUES
(1, 'google', 'opiniones',         'Opiniones Google',            3126,  NULL, 'vs Junio',  NULL, 'num', 1),
(1, 'google', 'busquedas_directas','Búsquedas Directas',          3995,  NULL, 'vs Junio',  NULL, 'num', 2),
(1, 'google', 'vistas_perfil',     'Vistas del Perfil',           6748,  NULL, 'vs Junio',  NULL, 'num', 3),
(1, 'google', 'llamadas',          'Llamadas telefónicas',        156,   NULL, 'vs Junio',  NULL, 'num', 4),
(1, 'google', 'cobertura_opiniones','Cobertura Goal',              88,    NULL, 'Objetivo 3.550', NULL, '%', 5),
(1, 'google', 'cobertura_ig',      'Goal IG Community',           15,    NULL, 'Objetivo 4.650', NULL, '%', 6),
(1, 'google', 'cobertura_fb',      'Goal FB Community',           92,    NULL, 'Objetivo 54.480', NULL, '%', 7),

-- META CONSOLIDADO
(1, 'meta', 'total_visualizaciones', 'Visualizaciones Meta Total', 144000, '107.196 IG | 36.783 FB', 'vs Junio', -20.8, 'num', 1),
(1, 'meta', 'ig_visualizaciones',    'Visualizaciones Instagram',  107196, NULL, NULL, NULL, 'num', 2),
(1, 'meta', 'fb_visualizaciones',    'Visualizaciones Facebook',   36783,  NULL, NULL, NULL, 'num', 3),
(1, 'meta', 'espectadores_unicos',   'Espectadores Únicos',        30838,  NULL, NULL, NULL, 'num', 4),
(1, 'meta', 'comunidad_ig',          'Comunidad Instagram',        4045,   NULL, NULL, NULL, 'num', 5),
(1, 'meta', 'comunidad_fb',          'Comunidad Facebook',         50125,  NULL, NULL, NULL, 'num', 6),
(1, 'meta', 'crecimiento_neto',      'Crecimiento Neto',           289,    '+289 seguidores', NULL, NULL, 'num', 7),
(1, 'meta', 'ig_alcance',            'Alcance Orgánico IG',        28450,  NULL, NULL, NULL, 'num', 8),
(1, 'meta', 'ig_likes',              'Likes IG',                   4812,   NULL, NULL, NULL, 'num', 9),
(1, 'meta', 'ig_comentarios',        'Comentarios IG',             347,    NULL, NULL, NULL, 'num', 10),
(1, 'meta', 'ig_guardados',          'Guardados IG',               1205,   NULL, NULL, NULL, 'num', 11),
(1, 'meta', 'reel_top_vistas',       'Reel Top del Mes (Vistas)',  19500,  'Estreno 30 Jul', NULL, NULL, 'num', 12),
(1, 'meta', 'reel_top_likes',        'Reel Top del Mes (Likes)',   1200,   NULL, NULL, NULL, 'num', 13),
(1, 'meta', 'mujeres_pct',           '% Mujeres Audiencia',        57.6,   NULL, NULL, NULL, '%', 14),
(1, 'meta', 'hombres_pct',           '% Hombres Audiencia',        42.4,   NULL, NULL, NULL, '%', 15),

-- TIKTOK
(1, 'tiktok', 'vistas_7d',     'Vistas (7 días)',           93100, 'Viral', NULL, NULL, 'num', 1),
(1, 'tiktok', 'compartidos',   'Compartidos',               963,   'El verdadero ROI viral', NULL, NULL, 'num', 2),
(1, 'tiktok', 'likes',         'Likes',                     1600,  NULL, NULL, NULL, 'num', 3),
(1, 'tiktok', 'videos_creados','Videos TikTok (período)',   12,    NULL, NULL, NULL, 'num', 4),

-- PAUTA DIGITAL META ADS
(1, 'pauta', 'inversion_cop',  'Inversión Total',           50016,  NULL, NULL, NULL, 'COP', 1),
(1, 'pauta', 'impresiones',    'Impresiones',               20381,  NULL, NULL, NULL, 'num', 2),
(1, 'pauta', 'resultados',     'Resultados (Acciones)',     449,    NULL, NULL, NULL, 'num', 3),
(1, 'pauta', 'cpr',            'Costo por Resultado',       112.82, NULL, NULL, NULL, 'COP', 4),
(1, 'pauta', 'alcance_total',  'Alcance Total',             15141,  NULL, NULL, NULL, 'num', 5),

-- EMAIL MARKETING B2C (Brevo)
(1, 'email_b2c', 'entregados',   'Emails Entregados',    55422, NULL, NULL, NULL, 'num', 1),
(1, 'email_b2c', 'open_rate',    'Tasa de Apertura',     18.0,  '18%', NULL, NULL, '%', 2),
(1, 'email_b2c', 'clics',        'Clics Totales',        249,   NULL, NULL, NULL, 'num', 3),
(1, 'email_b2c', 'cancelaciones','Cancelaciones',        16,    NULL, NULL, NULL, 'num', 4),

-- EMAIL MARKETING B2B (Brevo)
(1, 'email_b2b', 'empresas',     'Empresas Contactadas', 415,   NULL, NULL, NULL, 'num', 1),
(1, 'email_b2b', 'open_rate',    'Tasa de Apertura',     30.84, '30.84%', NULL, NULL, '%', 2),
(1, 'email_b2b', 'prospectos',   'Prospectos Calificados',25,   NULL, NULL, NULL, 'num', 3),
(1, 'email_b2b', 'tasa_respuesta','Tasa de Respuesta',   6.0,   NULL, NULL, NULL, '%', 4),

-- ENTREGABLES RESUMEN
(1, 'entregables', 'total', 'Total Entregables', 117, NULL, NULL, NULL, 'num', 1),
(1, 'entregables', 'mp4',   'Videos MP4',         27,  NULL, NULL, NULL, 'num', 2),
(1, 'entregables', 'jpg',   'Imágenes JPG',        74,  NULL, NULL, NULL, 'num', 3),
(1, 'entregables', 'pdf',   'Documentos PDF',       9,  NULL, NULL, NULL, 'num', 4),
(1, 'entregables', 'otros', 'Otros (Word, etc.)',   7,  NULL, NULL, NULL, 'num', 5);

-- ─── SERIES DE TIEMPO ────────────────────────────────────────────────────────
DELETE FROM `series_tiempo` WHERE `dashboard_id` = 1;

-- Meta — Visualizaciones diarias
INSERT INTO `series_tiempo` (`dashboard_id`, `canal`, `serie`, `periodo_label`, `valor`, `orden`) VALUES
(1, 'meta', 'visualizaciones', '1 jul',               2500,  1),
(1, 'meta', 'visualizaciones', '6 jul',               3200,  2),
(1, 'meta', 'visualizaciones', '11 jul',              2800,  3),
(1, 'meta', 'visualizaciones', '15 jul (Control)',    4800,  4),
(1, 'meta', 'visualizaciones', '20 jul (Pico)',      16800,  5),
(1, 'meta', 'visualizaciones', '26 jul',              2100,  6),
(1, 'meta', 'visualizaciones', '30 jul (Estreno)',   19500,  7),
(1, 'meta', 'visualizaciones', '31 jul',              9200,  8);

-- Meta — Espectadores únicos
INSERT INTO `series_tiempo` (`dashboard_id`, `canal`, `serie`, `periodo_label`, `valor`, `orden`) VALUES
(1, 'meta', 'espectadores_unicos', '1 jul',              800,  1),
(1, 'meta', 'espectadores_unicos', '6 jul',             1200,  2),
(1, 'meta', 'espectadores_unicos', '11 jul',            1500,  3),
(1, 'meta', 'espectadores_unicos', '15 jul (Control)',  1800,  4),
(1, 'meta', 'espectadores_unicos', '20 jul (Pico)',     9500,  5),
(1, 'meta', 'espectadores_unicos', '26 jul',            1100,  6),
(1, 'meta', 'espectadores_unicos', '30 jul (Estreno)',  3800,  7),
(1, 'meta', 'espectadores_unicos', '31 jul',            1900,  8);

-- TikTok — Vistas semanales
INSERT INTO `series_tiempo` (`dashboard_id`, `canal`, `serie`, `periodo_label`, `valor`, `orden`) VALUES
(1, 'tiktok', 'vistas', '1-5 Jul',                  3500, 1),
(1, 'tiktok', 'vistas', '6-10 Jul',                 2800, 2),
(1, 'tiktok', 'vistas', '11-15 Jul',                2100, 3),
(1, 'tiktok', 'vistas', '16-20 Jul',                1900, 4),
(1, 'tiktok', 'vistas', '21-23 Jul',                2400, 5),
(1, 'tiktok', 'vistas', '24-27 Jul (Cibergenios)', 32500, 6),
(1, 'tiktok', 'vistas', '28-31 Jul (Viral)',        47900, 7);

-- ─── UGC POSTS ───────────────────────────────────────────────────────────────
DELETE FROM `ugc_posts` WHERE `dashboard_id` = 1;
INSERT INTO `ugc_posts` (`dashboard_id`, `titulo`, `subtitulo`, `canal`, `vistas`, `compartidos`, `likes`, `badge_label`, `nota_estrategica`, `orden`) VALUES
(1, 'Spiderman Farock',   'Farock × Cine Múltiplex', 'tiktok', 47500, 67,  NULL, '#1 Video del Mes', 'Mayor viralidad orgánica del período. Palanca de UGC.', 1),
(1, 'Moana Farock',       'Farock × Cine Múltiplex', 'tiktok', 42100, 45,  NULL, 'Top 2 UGC',        'Segundo video con mayor reach del período.', 2),
(1, 'Outfit Spiderman',   'Reel UGC Humor',          'instagram', 39500, 100, NULL, 'Top 3 UGC',     '100 compartidos — máximo share del mes.', 3),
(1, 'Spiderman Trabajador','Reel UGC Humor',         'instagram', 23100, 55, NULL,  'Top 4 UGC',     'Formato humor con excelente retención.', 4);

-- ─── DEMOGRAFÍA ──────────────────────────────────────────────────────────────
DELETE FROM `audiencia_demografica` WHERE `dashboard_id` = 1;

-- Edad/Género
INSERT INTO `audiencia_demografica` (`dashboard_id`, `tipo`, `etiqueta`, `valor_mujeres`, `valor_hombres`, `valor_total`, `orden`) VALUES
(1, 'edad_genero', '18-24',  3.3,  2.6,  NULL, 1),
(1, 'edad_genero', '25-34', 26.2, 19.6,  NULL, 2),
(1, 'edad_genero', '35-44', 18.8, 13.9,  NULL, 3),
(1, 'edad_genero', '45-54',  6.9,  4.8,  NULL, 4),
(1, 'edad_genero', '55-64',  1.8,  1.2,  NULL, 5),
(1, 'edad_genero', '65+',    0.6,  0.5,  NULL, 6);

-- Ciudades
INSERT INTO `audiencia_demografica` (`dashboard_id`, `tipo`, `etiqueta`, `valor_mujeres`, `valor_hombres`, `valor_total`, `orden`) VALUES
(1, 'ciudad', 'Villavicencio (Meta)', NULL, NULL, 47.4, 1),
(1, 'ciudad', 'Bogotá D.C.',          NULL, NULL,  6.2, 2),
(1, 'ciudad', 'Acacías (Meta)',        NULL, NULL,  2.3, 3),
(1, 'ciudad', 'Granada (Meta)',        NULL, NULL,  0.9, 4),
(1, 'ciudad', 'Medellín',             NULL, NULL,  0.8, 5),
(1, 'ciudad', 'Restrepo (Meta)',       NULL, NULL,  0.8, 6);

-- ─── CAMPAÑAS DE PAUTA ───────────────────────────────────────────────────────
DELETE FROM `campanas_pauta` WHERE `dashboard_id` = 1;
INSERT INTO `campanas_pauta` (`dashboard_id`, `nombre`, `objetivo`, `plataforma`, `inversion_cop`, `alcance`, `impresiones`, `resultados`, `tipo_resultado`, `cpr`, `orden`) VALUES
(1, 'Promo Tricolor (Tráfico Web)', 'Visitas al sitio web',  'meta', 25008.00, 8445, 11200, 199, 'Clics al sitio web', 125.67, 1),
(1, 'Combos IG (Visitas Perfil)',   'Visitas al perfil IG',  'meta', 25008.00, 6696,  9181, 250, 'Visitas al perfil',   100.03, 2);

-- ─── ENTREGABLES (117 ítems) ─────────────────────────────────────────────────
DELETE FROM `entregables` WHERE `dashboard_id` = 1;
INSERT INTO `entregables` (`dashboard_id`, `numero_item`, `nombre`, `formato`, `categoria`, `fecha_creacion`) VALUES
-- MP4 Videos (27)
(1,1,'Reel Spiderman Farock - UGC Viral','MP4','UGC & Reels','2026-07-28'),
(1,2,'Reel Moana Farock - UGC Viral','MP4','UGC & Reels','2026-07-29'),
(1,3,'Reel Outfit Spiderman - Humor','MP4','UGC & Reels','2026-07-26'),
(1,4,'Reel Spiderman Trabajador - Humor','MP4','UGC & Reels','2026-07-24'),
(1,5,'Reel Promo Intensamente 2 - Estreno','MP4','Promocional','2026-07-30'),
(1,6,'Reel Cartelera Semana 1 Julio','MP4','Cartelera','2026-07-01'),
(1,7,'Reel Cartelera Semana 2 Julio','MP4','Cartelera','2026-07-08'),
(1,8,'Reel Cartelera Semana 3 Julio','MP4','Cartelera','2026-07-15'),
(1,9,'Reel Cartelera Semana 4 Julio','MP4','Cartelera','2026-07-22'),
(1,10,'Video Story Combo Tricolor Promo','MP4','Pauta Digital','2026-07-05'),
(1,11,'Video Story Combo Especial Película','MP4','Pauta Digital','2026-07-12'),
(1,12,'Reel Confitería Premium','MP4','Fidelización','2026-07-18'),
(1,13,'Reel Cumpleaños VIP Múltiplex','MP4','Fidelización','2026-07-10'),
(1,14,'Video Tour Sala VIP','MP4','Institucional','2026-07-03'),
(1,15,'Reel BTS Grabación UGC','MP4','Detrás de Cámaras','2026-07-27'),
(1,16,'Video Testimonial Cliente Satisfecho 1','MP4','Testimonial','2026-07-20'),
(1,17,'Video Testimonial Cliente Satisfecho 2','MP4','Testimonial','2026-07-21'),
(1,18,'Reel Retención 30s - Hook Test A','MP4','Contenido Orgánico','2026-07-16'),
(1,19,'Reel Retención 30s - Hook Test B','MP4','Contenido Orgánico','2026-07-17'),
(1,20,'Video Recap Mes de Julio','MP4','Institucional','2026-07-31'),
(1,21,'Reel Cartelera Adultos Semana 1','MP4','Cartelera','2026-07-01'),
(1,22,'Reel Cartelera Adultos Semana 2','MP4','Cartelera','2026-07-08'),
(1,23,'Reel Cartelera Adultos Semana 3','MP4','Cartelera','2026-07-15'),
(1,24,'Reel Cartelera Adultos Semana 4','MP4','Cartelera','2026-07-22'),
(1,25,'Video Animado Logo Intro','MP4','Institucional','2026-07-02'),
(1,26,'Reel Promoción Sala 3D Estreno','MP4','Promocional','2026-07-29'),
(1,27,'Video Collage UGC Julio Best Of','MP4','UGC & Reels','2026-07-31'),
-- JPG Imágenes (74)
(1,28,'Afiche Digital Intensamente 2','JPG','Cartelera','2026-07-01'),
(1,29,'Afiche Digital Spiderman','JPG','Cartelera','2026-07-01'),
(1,30,'Afiche Digital Moana','JPG','Cartelera','2026-07-01'),
(1,31,'Afiche Digital Cartelera Completa S1','JPG','Cartelera','2026-07-01'),
(1,32,'Afiche Digital Cartelera Completa S2','JPG','Cartelera','2026-07-08'),
(1,33,'Afiche Digital Cartelera Completa S3','JPG','Cartelera','2026-07-15'),
(1,34,'Afiche Digital Cartelera Completa S4','JPG','Cartelera','2026-07-22'),
(1,35,'Post Redes Combo Tricolor','JPG','Pauta Digital','2026-07-05'),
(1,36,'Post Redes Combo Especial Película','JPG','Pauta Digital','2026-07-12'),
(1,37,'Story Combo Tricolor Formato Vertical','JPG','Pauta Digital','2026-07-05'),
(1,38,'Story Combo Especial Formato Vertical','JPG','Pauta Digital','2026-07-12'),
(1,39,'Banner Web Promo Julio','JPG','Pauta Digital','2026-07-03'),
(1,40,'Imagen Portada Facebook Julio','JPG','Redes Sociales','2026-07-01'),
(1,41,'Imagen Portada Instagram Julio','JPG','Redes Sociales','2026-07-01'),
(1,42,'Post Cumpleaños VIP','JPG','Fidelización','2026-07-10'),
(1,43,'Post Confitería Premium Detalle','JPG','Fidelización','2026-07-18'),
(1,44,'Post Testimonial Cliente 1','JPG','Testimonial','2026-07-20'),
(1,45,'Post Testimonial Cliente 2','JPG','Testimonial','2026-07-21'),
(1,46,'Afiche UGC Spiderman Farock','JPG','UGC & Reels','2026-07-28'),
(1,47,'Afiche UGC Moana Farock','JPG','UGC & Reels','2026-07-29'),
(1,48,'Afiche UGC Outfit Spiderman','JPG','UGC & Reels','2026-07-26'),
(1,49,'Afiche UGC Spiderman Trabajador','JPG','UGC & Reels','2026-07-24'),
(1,50,'Infografía Horarios Cartelera S1','JPG','Información','2026-07-01'),
(1,51,'Infografía Horarios Cartelera S2','JPG','Información','2026-07-08'),
(1,52,'Infografía Horarios Cartelera S3','JPG','Información','2026-07-15'),
(1,53,'Infografía Horarios Cartelera S4','JPG','Información','2026-07-22'),
(1,54,'Post Fecha Especial 16 Julio','JPG','Efeméride','2026-07-16'),
(1,55,'Post Fecha Especial 20 Julio Independencia','JPG','Efeméride','2026-07-20'),
(1,56,'Post Fecha Especial 24 Julio Bolívar','JPG','Efeméride','2026-07-24'),
(1,57,'Post Precio Entrada Adulto','JPG','Información','2026-07-04'),
(1,58,'Post Precio Entrada Niño','JPG','Información','2026-07-04'),
(1,59,'Post Precio Confitería','JPG','Información','2026-07-11'),
(1,60,'Story Cartelera Formato Vertical S1','JPG','Cartelera','2026-07-01'),
(1,61,'Story Cartelera Formato Vertical S2','JPG','Cartelera','2026-07-08'),
(1,62,'Story Cartelera Formato Vertical S3','JPG','Cartelera','2026-07-15'),
(1,63,'Story Cartelera Formato Vertical S4','JPG','Cartelera','2026-07-22'),
(1,64,'Miniatura YouTube Intensamente 2','JPG','Plataformas','2026-07-30'),
(1,65,'Miniatura YouTube Spiderman','JPG','Plataformas','2026-07-15'),
(1,66,'Post Pregunta Interactiva Película Favorita','JPG','Engagement','2026-07-09'),
(1,67,'Post Encuesta Combo Preferido','JPG','Engagement','2026-07-14'),
(1,68,'Post Trivia Cine (Película Clásica)','JPG','Engagement','2026-07-19'),
(1,69,'Post Meme Cinema Friendly 1','JPG','Engagement','2026-07-07'),
(1,70,'Post Meme Cinema Friendly 2','JPG','Engagement','2026-07-13'),
(1,71,'Post Meme Cinema Friendly 3','JPG','Engagement','2026-07-21'),
(1,72,'Imagen Aniversario Sala VIP','JPG','Institucional','2026-07-25'),
(1,73,'Post BTS Sesión Grabación UGC','JPG','Detrás de Cámaras','2026-07-27'),
(1,74,'Galería Sala Premium (3 fotos) #1','JPG','Institucional','2026-07-06'),
(1,75,'Galería Sala Premium (3 fotos) #2','JPG','Institucional','2026-07-06'),
(1,76,'Galería Sala Premium (3 fotos) #3','JPG','Institucional','2026-07-06'),
(1,77,'Post Reseña Película Recomendada S1','JPG','Contenido Orgánico','2026-07-02'),
(1,78,'Post Reseña Película Recomendada S2','JPG','Contenido Orgánico','2026-07-09'),
(1,79,'Post Reseña Película Recomendada S3','JPG','Contenido Orgánico','2026-07-16'),
(1,80,'Post Reseña Película Recomendada S4','JPG','Contenido Orgánico','2026-07-23'),
(1,81,'Cover Email B2C Julio Diseño','JPG','Email Marketing','2026-07-01'),
(1,82,'Banner Email B2B Empresas','JPG','Email Marketing','2026-07-01'),
(1,83,'Post Horario Especial Feriado 20J','JPG','Información','2026-07-19'),
(1,84,'Thumbnail TikTok UGC Spiderman','JPG','TikTok','2026-07-28'),
(1,85,'Thumbnail TikTok UGC Moana','JPG','TikTok','2026-07-29'),
(1,86,'Post Agradecimiento 50K Familia FB','JPG','Comunidad','2026-07-15'),
(1,87,'Post Bienvenida Nuevos Seguidores','JPG','Comunidad','2026-07-31'),
(1,88,'Story Poll Película del Mes','JPG','Engagement','2026-07-22'),
(1,89,'Post Cross-Promo TikTok desde IG','JPG','Redes Sociales','2026-07-27'),
(1,90,'Afiche Concurso UGC Participación','JPG','Engagement','2026-07-23'),
(1,91,'Resultado Ganador Concurso UGC','JPG','Engagement','2026-07-30'),
(1,92,'Post Combo Niños + Adultos Bundle','JPG','Fidelización','2026-07-17'),
(1,93,'Story Cuenta Regresiva Estreno','JPG','Promocional','2026-07-28'),
(1,94,'Post Estreno Noche Especial','JPG','Promocional','2026-07-30'),
(1,95,'Imagen Highlight Instagram Stories','JPG','Redes Sociales','2026-07-31'),
(1,96,'Cover Highlight Cartelera','JPG','Redes Sociales','2026-07-01'),
(1,97,'Cover Highlight Promos','JPG','Redes Sociales','2026-07-01'),
(1,98,'Cover Highlight UGC','JPG','Redes Sociales','2026-07-01'),
(1,99,'Post Recordatorio Horario Sábado','JPG','Información','2026-07-05'),
(1,100,'Post Recordatorio Horario Domingo','JPG','Información','2026-07-06'),
(1,101,'Afiche Retiro Película Antigua','JPG','Cartelera','2026-07-15'),
-- PDFs (9)
(1,102,'Informe de Gestión Mensual Julio 2026 PDF','PDF','Reportes','2026-07-31'),
(1,103,'Propuesta Estratégica Q3 2026','PDF','Estrategia','2026-07-15'),
(1,104,'Cronograma Editorial Agosto 2026','PDF','Planificación','2026-07-29'),
(1,105,'Reporte Pauta Digital Meta Ads Julio','PDF','Reportes','2026-07-31'),
(1,106,'Análisis Competencia Cines Villavicencio','PDF','Estrategia','2026-07-20'),
(1,107,'Manual de Marca Actualizado Versión 2','PDF','Institucional','2026-07-10'),
(1,108,'Guion UGC Sesión Spiderman','PDF','Producción','2026-07-26'),
(1,109,'Guion UGC Sesión Moana','PDF','Producción','2026-07-27'),
(1,110,'Brief Creativo Campaña Agosto','PDF','Producción','2026-07-30'),
-- Otros (Word, Excel) (7)
(1,111,'Plan de Contenidos Agosto 2026.docx','DOCX','Planificación','2026-07-28'),
(1,112,'Base de Datos Email B2B Actualizada.xlsx','XLSX','Email Marketing','2026-07-31'),
(1,113,'Registro Métricas Diarias Julio.xlsx','XLSX','Reportes','2026-07-31'),
(1,114,'Presupuesto Pauta Agosto 2026.xlsx','XLSX','Pauta Digital','2026-07-29'),
(1,115,'Checklist Producción UGC Agosto.docx','DOCX','Producción','2026-07-28'),
(1,116,'Acta Reunión Estratégica 15 Jul.docx','DOCX','Institucional','2026-07-15'),
(1,117,'Script Email Campaña Agosto B2C.docx','DOCX','Email Marketing','2026-07-30');
