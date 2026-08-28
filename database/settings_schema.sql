-- Configuración global de la app (reCAPTCHA, etc.) - key/value simple
-- Aplicado directamente contra wwcibe_mktinsights (mismo patrón que run_sql_prod.py)

CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES
  ('recaptcha_enabled', '0'),
  ('recaptcha_site_key', ''),
  ('recaptcha_secret_key', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
