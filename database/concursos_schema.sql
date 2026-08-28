-- Módulo de Concursos & Sorteos - Marketing Insights
-- Aplicado directamente contra wwcibe_mktinsights (mismo patrón que run_sql_prod.py)

CREATE TABLE IF NOT EXISTS `concursos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `metodologia` text,
  `claim_hours` decimal(8,2) NOT NULL DEFAULT 24,
  `webhook_token` varchar(64) NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_slug` (`empresa_id`, `slug`),
  UNIQUE KEY `webhook_token` (`webhook_token`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `concurso_premios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `concurso_id` bigint unsigned NOT NULL,
  `kit` varchar(20) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `detalle` varchar(500) DEFAULT NULL,
  `orden` int NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `concurso_kit` (`concurso_id`, `kit`),
  FOREIGN KEY (`concurso_id`) REFERENCES `concursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `concurso_leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `concurso_id` bigint unsigned NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `apellido` varchar(150) NOT NULL,
  `documento` varchar(30) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `origen` varchar(30) NOT NULL DEFAULT 'manual',
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `concurso_documento` (`concurso_id`, `documento`),
  FOREIGN KEY (`concurso_id`) REFERENCES `concursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `concurso_sorteos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `concurso_id` bigint unsigned NOT NULL,
  `kit` varchar(20) NOT NULL,
  `lead_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `concurso_kit_sorteo` (`concurso_id`, `kit`),
  FOREIGN KEY (`concurso_id`) REFERENCES `concursos` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lead_id`) REFERENCES `concurso_leads` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `concurso_suplentes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `concurso_id` bigint unsigned NOT NULL,
  `kit` varchar(20) NOT NULL,
  `lead_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`concurso_id`) REFERENCES `concursos` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lead_id`) REFERENCES `concurso_leads` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
