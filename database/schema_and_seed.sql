-- =============================================================================
-- MARKETING INSIGHTS - ESQUEMA Y DATOS INICIALES (wwcibe_mktinsights)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'superadmin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Superadmin Cibergenios', 'webmaster@cibergenios.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

CREATE TABLE IF NOT EXISTS `empresas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
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
(1, 'Cine Múltiplex Villacentro', 'cine-multiplex-villacentro', 'Salas de Cine & Entretenimiento', 'Villavicencio, Meta', 'Colombia', 'assets/images/logo_multiplex_light.png', 'assets/images/logo_multiplex_dark.png', 'mkt_live_cmv_78a9c0f', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

CREATE TABLE IF NOT EXISTS `dashboards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint(20) unsigned NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `periodo` varchar(100) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `descripcion_ejecutiva` text DEFAULT NULL,
  `public_token` varchar(255) NOT NULL UNIQUE,
  `es_publico` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dashboards` (`id`, `empresa_id`, `titulo`, `slug`, `periodo`, `fecha_inicio`, `fecha_fin`, `descripcion_ejecutiva`, `public_token`, `es_publico`) VALUES
(1, 1, 'Informe Ejecutivo de Gestión Mensual | Julio 2026', 'julio-2026', 'Julio 2026', '2026-07-01', '2026-07-31', 'Auditoría inmediata, reestructuración estratégica hacia formatos de video corto (Reels / TikToks UGC) y reactivación integral de la conversión.', 'mkt_live_cmv_78a9c0f', 1),
(2, 1, 'Informe de Rendimiento | Abril 2026', 'abril-2026', 'Abril 2026', '2026-04-01', '2026-04-30', 'Informe de desempeño mensual anterior para comparativa histórica.', 'mkt_live_cmv_abril2026', 1)
ON DUPLICATE KEY UPDATE `titulo` = VALUES(`titulo`);

CREATE TABLE IF NOT EXISTS `modulos_indicadores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint(20) unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `codigo` varchar(100) NOT NULL,
  `tipo_visualizacion` varchar(50) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `modulos_indicadores` (`id`, `dashboard_id`, `nombre`, `codigo`, `tipo_visualizacion`, `orden`, `activo`) VALUES
(1, 1, 'Timeline de Transición Operativa', 'timeline', 'timeline_phases', 1, 1),
(2, 1, 'Google Mi Negocio & Búsquedas', 'google_business', 'table_comparison', 2, 1),
(3, 1, 'Rendimiento Meta (FB + IG)', 'meta_social', 'line_chart', 3, 1),
(4, 1, 'TikTok & Viralidad', 'tiktok_social', 'line_chart', 4, 1),
(5, 1, 'Showcase UGC & Creadores', 'ugc_showcase', 'cards_grid', 5, 1),
(6, 1, 'Pauta Digital Meta Ads', 'meta_ads', 'bar_chart', 6, 1),
(7, 1, 'Email Marketing Brevo', 'email_brevo', 'stat_cards', 7, 1),
(8, 1, 'Catálogo de 117 Entregables', 'entregables', 'data_table', 8, 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

CREATE TABLE IF NOT EXISTS `entregables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint(20) unsigned NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `formato` varchar(50) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `fecha_creacion` date NOT NULL,
  `dimensiones` varchar(100) DEFAULT NULL,
  `duracion_segundos` int(11) DEFAULT NULL,
  `url_archivo` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
