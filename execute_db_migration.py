"""
Script para migrar y poblar la base de datos wwcibe_mktinsights en cPanel via SSH.
Maneja la verificación de columnas de forma compatible con todas las versiones de MySQL.
"""

import os
import paramiko

HOST = 'sonia.ai.cibergenios.com'
PORT = 22022
USER = 'wwcibe'
KEY_FILE = r'C:\Users\ADMINISTRATIVO\.ssh\id_sonia'

DB_USER = 'wwcibe_mktinsightsR00t'
DB_PASS = "jnLvx.I^AaMf59L%"
DB_NAME = 'wwcibe_mktinsights'

def run_mysql_query(ssh, sql):
    # Escape sql for bash
    escaped_sql = sql.replace('"', '\\"').replace('$', '\\$')
    cmd = f'mysql -u "{DB_USER}" -p"{DB_PASS}" "{DB_NAME}" -e "{escaped_sql}" 2>&1'
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='ignore')
    return out

print("Connecting SSH...")
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

if os.path.exists(KEY_FILE):
    ssh.connect(HOST, port=PORT, username=USER, key_filename=KEY_FILE, timeout=15)
else:
    ssh.connect(HOST, port=PORT, username=USER, password='Password99$', timeout=15)
print("SSH Connected!")

# 1. Verificar columnas en dashboards
cols_out = run_mysql_query(ssh, "SHOW COLUMNS FROM dashboards;")
print("Columnas en dashboards:", cols_out)

if 'lema' not in cols_out:
    print("Agregando columna lema a dashboards...")
    out = run_mysql_query(ssh, "ALTER TABLE dashboards ADD COLUMN lema varchar(500) DEFAULT NULL AFTER periodo;")
    print(out)

if 'resumen_aprendizaje' not in cols_out:
    print("Agregando columna resumen_aprendizaje a dashboards...")
    out = run_mysql_query(ssh, "ALTER TABLE dashboards ADD COLUMN resumen_aprendizaje text DEFAULT NULL AFTER descripcion_ejecutiva;")
    print(out)

# 2. Verificar columnas en entregables
cols_entregables = run_mysql_query(ssh, "SHOW COLUMNS FROM entregables;")
print("Columnas en entregables:", cols_entregables)

if 'numero_item' not in cols_entregables:
    print("Agregando columna numero_item a entregables...")
    out = run_mysql_query(ssh, "ALTER TABLE entregables ADD COLUMN numero_item int DEFAULT NULL AFTER dashboard_id;")
    print(out)

if 'categoria' not in cols_entregables:
    print("Agregando columna categoria a entregables...")
    out = run_mysql_query(ssh, "ALTER TABLE entregables ADD COLUMN categoria varchar(100) NOT NULL DEFAULT 'General' AFTER formato;")
    print(out)

# 3. Subir y ejecutar el SQL limpio de creación de tablas y seeds
CLEAN_SQL = r"""
-- Actualizar dashboards con lema y resumen
UPDATE `dashboards` SET
  `lema` = 'Toma de Control y Explosión de Crecimiento',
  `resumen_aprendizaje` = 'El aprendizaje más grande que nos dejan los datos de julio es que el formato dictamina el éxito. Nuestra decisión de migrar el esfuerzo hacia la producción de videos UGC y Reels permitió frenar en solo 15 días la caída de la comunidad y reactivar la conversión directa a taquilla.'
WHERE slug = 'julio-2026';

-- Modulos Indicadores (Actualizar o Insertar)
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

-- NUEVAS TABLAS
CREATE TABLE IF NOT EXISTS `metricas_canal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `canal` varchar(100) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `etiqueta` varchar(255) DEFAULT NULL,
  `valor_numerico` decimal(15,4) DEFAULT NULL,
  `valor_texto` varchar(500) DEFAULT NULL,
  `comparativo_label` varchar(100) DEFAULT NULL,
  `comparativo_valor` decimal(10,4) DEFAULT NULL,
  `unidad` varchar(50) DEFAULT NULL,
  `orden` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dash_canal_clave` (`dashboard_id`, `canal`, `clave`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `series_tiempo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `canal` varchar(100) NOT NULL,
  `serie` varchar(100) NOT NULL,
  `periodo_label` varchar(100) NOT NULL,
  `valor` decimal(15,2) NOT NULL DEFAULT 0,
  `orden` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ugc_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `canal` varchar(50) NOT NULL DEFAULT 'tiktok',
  `vistas` bigint DEFAULT NULL,
  `compartidos` int DEFAULT NULL,
  `likes` int DEFAULT NULL,
  `badge_label` varchar(100) DEFAULT NULL,
  `nota_estrategica` text DEFAULT NULL,
  `orden` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audiencia_demografica` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_id` bigint unsigned NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `etiqueta` varchar(100) NOT NULL,
  `valor_mujeres` decimal(6,2) DEFAULT NULL,
  `valor_hombres` decimal(6,2) DEFAULT NULL,
  `valor_total` decimal(6,2) DEFAULT NULL,
  `orden` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- SEED DATA para dashboard_id = 1

-- Hitos Timeline
DELETE FROM `hitos_timeline` WHERE `dashboard_id` = 1;
INSERT INTO `hitos_timeline` (`dashboard_id`, `periodo`, `fase`, `descripcion`, `hito_clave`, `orden`) VALUES
(1, '24-30 Jun', 'Empalme parcial', 'Período de transición y entrega de accesos', NULL, 1),
(1, '1-14 Jul', 'Diseño & estrategia', 'Auditoría, rediseño de contenidos y plan editorial', NULL, 2),
(1, '15-31 Jul', 'Control total Cibergenios', 'Producción 100% bajo gestión de Cibergenios', '15 de Julio: Control 100%', 3);

-- Metricas por canal
DELETE FROM `metricas_canal` WHERE `dashboard_id` = 1;
INSERT INTO `metricas_canal` (`dashboard_id`, `canal`, `clave`, `etiqueta`, `valor_numerico`, `valor_texto`, `comparativo_label`, `comparativo_valor`, `unidad`, `orden`) VALUES
(1,'google','opiniones','Opiniones Google',3126,NULL,'vs Junio',NULL,'num',1),
(1,'google','busquedas_directas','Búsquedas Directas',3995,NULL,'vs Junio',NULL,'num',2),
(1,'google','vistas_perfil','Vistas del Perfil',6748,NULL,'vs Junio',NULL,'num',3),
(1,'google','llamadas','Llamadas telefónicas',156,NULL,'vs Junio',NULL,'num',4),
(1,'google','cobertura_opiniones','Cobertura Goal Opiniones',88,NULL,'Objetivo 3.550',NULL,'%',5),
(1,'google','cobertura_ig','Goal IG Community',15,NULL,'Objetivo 4.650',NULL,'%',6),
(1,'google','cobertura_fb','Goal FB Community',92,NULL,'Objetivo 54.480',NULL,'%',7),
(1,'meta','total_visualizaciones','Visualizaciones Meta Total',144000,'107.196 IG | 36.783 FB','vs Junio',-20.8,'num',1),
(1,'meta','ig_visualizaciones','Visualizaciones Instagram',107196,NULL,NULL,NULL,'num',2),
(1,'meta','fb_visualizaciones','Visualizaciones Facebook',36783,NULL,NULL,NULL,'num',3),
(1,'meta','espectadores_unicos','Espectadores Únicos',30838,NULL,NULL,NULL,'num',4),
(1,'meta','comunidad_ig','Comunidad Instagram',4045,NULL,NULL,NULL,'num',5),
(1,'meta','comunidad_fb','Comunidad Facebook',50125,NULL,NULL,NULL,'num',6),
(1,'meta','crecimiento_neto','Crecimiento Neto',289,'+289 seguidores',NULL,NULL,'num',7),
(1,'meta','ig_alcance','Alcance Orgánico IG',28450,NULL,NULL,NULL,'num',8),
(1,'meta','ig_likes','Likes IG',4812,NULL,NULL,NULL,'num',9),
(1,'meta','ig_comentarios','Comentarios IG',347,NULL,NULL,NULL,'num',10),
(1,'meta','ig_guardados','Guardados IG',1205,NULL,NULL,NULL,'num',11),
(1,'meta','reel_top_vistas','Reel Top del Mes (Vistas)',19500,'Estreno 30 Jul',NULL,NULL,'num',12),
(1,'meta','reel_top_likes','Reel Top del Mes (Likes)',1200,NULL,NULL,NULL,'num',13),
(1,'meta','mujeres_pct','% Mujeres Audiencia',57.6,NULL,NULL,NULL,'%',14),
(1,'meta','hombres_pct','% Hombres Audiencia',42.4,NULL,NULL,NULL,'%',15),
(1,'tiktok','vistas_7d','Vistas (7 días)',93100,'Viral',NULL,NULL,'num',1),
(1,'tiktok','compartidos','Compartidos',963,'El verdadero ROI viral',NULL,NULL,'num',2),
(1,'tiktok','likes','Likes',1600,NULL,NULL,NULL,'num',3),
(1,'tiktok','videos_creados','Videos TikTok (período)',12,NULL,NULL,NULL,'num',4),
(1,'pauta','inversion_cop','Inversión Total',50016,NULL,NULL,NULL,'COP',1),
(1,'pauta','impresiones','Impresiones',20381,NULL,NULL,NULL,'num',2),
(1,'pauta','resultados','Resultados (Acciones)',449,NULL,NULL,NULL,'num',3),
(1,'pauta','cpr','Costo por Resultado',112.82,NULL,NULL,NULL,'COP',4),
(1,'pauta','alcance_total','Alcance Total',15141,NULL,NULL,NULL,'num',5),
(1,'email_b2c','entregados','Emails Entregados',55422,NULL,NULL,NULL,'num',1),
(1,'email_b2c','open_rate','Tasa de Apertura',18.0,'18%',NULL,NULL,'%',2),
(1,'email_b2c','clics','Clics Totales',249,NULL,NULL,NULL,'num',3),
(1,'email_b2c','cancelaciones','Cancelaciones',16,NULL,NULL,NULL,'num',4),
(1,'email_b2b','empresas','Empresas Contactadas',415,NULL,NULL,NULL,'num',1),
(1,'email_b2b','open_rate','Tasa de Apertura',30.84,'30.84%',NULL,NULL,'%',2),
(1,'email_b2b','prospectos','Prospectos Calificados',25,NULL,NULL,NULL,'num',3),
(1,'email_b2b','tasa_respuesta','Tasa de Respuesta',6.0,NULL,NULL,NULL,'%',4),
(1,'entregables','total','Total Entregables',117,NULL,NULL,NULL,'num',1),
(1,'entregables','mp4','Videos MP4',27,NULL,NULL,NULL,'num',2),
(1,'entregables','jpg','Imágenes JPG',74,NULL,NULL,NULL,'num',3),
(1,'entregables','pdf','Documentos PDF',9,NULL,NULL,NULL,'num',4),
(1,'entregables','otros','Otros (Word, etc.)',7,NULL,NULL,NULL,'num',5);

-- Series de tiempo Meta
DELETE FROM `series_tiempo` WHERE `dashboard_id` = 1;
INSERT INTO `series_tiempo` (`dashboard_id`, `canal`, `serie`, `periodo_label`, `valor`, `orden`) VALUES
(1,'meta','visualizaciones','1 jul',2500,1),
(1,'meta','visualizaciones','6 jul',3200,2),
(1,'meta','visualizaciones','11 jul',2800,3),
(1,'meta','visualizaciones','15 jul (Control)',4800,4),
(1,'meta','visualizaciones','20 jul (Pico)',16800,5),
(1,'meta','visualizaciones','26 jul',2100,6),
(1,'meta','visualizaciones','30 jul (Estreno)',19500,7),
(1,'meta','visualizaciones','31 jul',9200,8),
(1,'meta','espectadores_unicos','1 jul',800,1),
(1,'meta','espectadores_unicos','6 jul',1200,2),
(1,'meta','espectadores_unicos','11 jul',1500,3),
(1,'meta','espectadores_unicos','15 jul (Control)',1800,4),
(1,'meta','espectadores_unicos','20 jul (Pico)',9500,5),
(1,'meta','espectadores_unicos','26 jul',1100,6),
(1,'meta','espectadores_unicos','30 jul (Estreno)',3800,7),
(1,'meta','espectadores_unicos','31 jul',1900,8),
(1,'tiktok','vistas','1-5 Jul',3500,1),
(1,'tiktok','vistas','6-10 Jul',2800,2),
(1,'tiktok','vistas','11-15 Jul',2100,3),
(1,'tiktok','vistas','16-20 Jul',1900,4),
(1,'tiktok','vistas','21-23 Jul',2400,5),
(1,'tiktok','vistas','24-27 Jul (Cibergenios)',32500,6),
(1,'tiktok','vistas','28-31 Jul (Viral)',47900,7);

-- UGC Posts
DELETE FROM `ugc_posts` WHERE `dashboard_id` = 1;
INSERT INTO `ugc_posts` (`dashboard_id`, `titulo`, `subtitulo`, `canal`, `vistas`, `compartidos`, `likes`, `badge_label`, `nota_estrategica`, `orden`) VALUES
(1,'Spiderman Farock','Farock x Cine Multiple','tiktok',47500,67,905,'#1 Video del Mes','Mayor viralidad organica del periodo. Palanca de UGC.',1),
(1,'Moana Farock','Farock x Cine Multiple','tiktok',42100,45,348,'Top 2 UGC','Segundo video con mayor reach del periodo.',2),
(1,'Outfit Spiderman','Reel UGC Humor','instagram',39500,100,1013,'Top 3 UGC','100 compartidos - maximo share del mes.',3),
(1,'Spiderman Trabajador','Reel UGC Humor','instagram',23100,55,479,'Top 4 UGC','Formato humor con excelente retencion.',4);

-- Demografía
DELETE FROM `audiencia_demografica` WHERE `dashboard_id` = 1;
INSERT INTO `audiencia_demografica` (`dashboard_id`, `tipo`, `etiqueta`, `valor_mujeres`, `valor_hombres`, `valor_total`, `orden`) VALUES
(1,'edad_genero','18-24',3.3,2.6,NULL,1),
(1,'edad_genero','25-34',26.2,19.6,NULL,2),
(1,'edad_genero','35-44',18.8,13.9,NULL,3),
(1,'edad_genero','45-54',6.9,4.8,NULL,4),
(1,'edad_genero','55-64',1.8,1.2,NULL,5),
(1,'edad_genero','65+',0.6,0.5,NULL,6),
(1,'ciudad','Villavicencio (Meta)',NULL,NULL,47.4,1),
(1,'ciudad','Bogota D.C.',NULL,NULL,6.2,2),
(1,'ciudad','Acacias (Meta)',NULL,NULL,2.3,3),
(1,'ciudad','Granada (Meta)',NULL,NULL,0.9,4),
(1,'ciudad','Medellin',NULL,NULL,0.8,5),
(1,'ciudad','Restrepo (Meta)',NULL,NULL,0.8,6);

-- Campañas de pauta
DELETE FROM `campanas_pauta` WHERE `dashboard_id` = 1;
INSERT INTO `campanas_pauta` (`dashboard_id`, `nombre`, `objetivo`, `plataforma`, `inversion_cop`, `alcance`, `impresiones`, `resultados`, `tipo_resultado`, `cpr`, `orden`) VALUES
(1,'Promo Tricolor (Trafico Web)','Visitas al sitio web','meta',25008.00,8445,11200,199,'Clics al sitio web',125.67,1),
(1,'Combos IG (Visitas Perfil)','Visitas al perfil IG','meta',25008.00,6696,9181,250,'Visitas al perfil',100.03,2);

-- Entregables
INSERT IGNORE INTO `entregables` (`dashboard_id`, `numero_item`, `nombre`, `formato`, `categoria`, `fecha_creacion`) VALUES
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
(1,28,'Afiche Digital Intensamente 2','JPG','Cartelera','2026-07-01'),
(1,29,'Afiche Digital Spiderman','JPG','Cartelera','2026-07-01'),
(1,30,'Afiche Digital Moana','JPG','Cartelera','2026-07-01'),
(1,31,'Afiche Digital Cartelera S1','JPG','Cartelera','2026-07-01'),
(1,35,'Post Redes Combo Tricolor','JPG','Pauta Digital','2026-07-05'),
(1,102,'Informe Gestion Mensual Julio 2026','PDF','Reportes','2026-07-31'),
(1,103,'Propuesta Estrategica Q3 2026','PDF','Estrategia','2026-07-15'),
(1,111,'Plan de Contenidos Agosto 2026','DOCX','Planificacion','2026-07-28');
"""

sftp = ssh.open_sftp()
remote_sql = '/home/wwcibe/mkt_clean_seed.sql'
with sftp.open(remote_sql, 'w') as f:
    f.write(CLEAN_SQL.encode('utf-8'))
sftp.close()
print("Clean SQL uploaded!")

cmd = f"mysql -u '{DB_USER}' -p'{DB_PASS}' '{DB_NAME}' < '{remote_sql}' 2>&1 && echo 'SQL_SUCCESS' || echo 'SQL_FAILED'"
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8', errors='ignore')
print("SQL Execution Output:", out)

# Verificación
verify_sql = "SELECT (SELECT COUNT(*) FROM metricas_canal) as metricas, (SELECT COUNT(*) FROM ugc_posts) as ugc, (SELECT COUNT(*) FROM series_tiempo) as series, (SELECT COUNT(*) FROM hitos_timeline) as hitos, (SELECT COUNT(*) FROM entregables) as entregables;"
print("Counts:", run_mysql_query(ssh, verify_sql))

# Cleanup
ssh.exec_command(f"rm -f {remote_sql}")
ssh.close()
print("Done!")
