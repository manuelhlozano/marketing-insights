"""
Inserta las métricas de la Auditoría SEO & Rendimiento de cinemultiplex.co en wwcibe_mktinsights.
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

SQL = """
DELETE FROM metricas_canal WHERE dashboard_id = 1 AND canal = 'seo';
INSERT INTO metricas_canal (dashboard_id, canal, clave, etiqueta, valor_numerico, valor_texto, comparativo_label, comparativo_valor, unidad, orden) VALUES
(1, 'seo', 'score_global', 'Score de Salud Web', 86, 'Nivel Óptimo', '27 pruebas aprobadas (75%)', 86, '%', 1),
(1, 'seo', 'tiempo_carga', 'Tiempo de Carga', 0.47, '0.47 segundos', 'Ultra Rápido (<1s)', NULL, 'seg', 2),
(1, 'seo', 'tamano_pagina', 'Tamaño de Página', 185.84, '185.84 kB (HTML Comprimido)', NULL, NULL, 'kB', 3),
(1, 'seo', 'recursos_total', 'Solicitudes HTTP', 45, '2 JS · 2 CSS · 41 Imágenes', NULL, NULL, 'num', 4),
(1, 'seo', 'pruebas_aprobadas', 'Pruebas Aprobadas', 27, '75.0% de tests aprobados', NULL, 75.0, '%', 5),
(1, 'seo', 'enlaces_internos', 'Enlaces Internos', 131, '131 enlaces verificados', NULL, NULL, 'num', 6),
(1, 'seo', 'palabras_contenido', 'Palabras en Contenido', 3048, '3.048 palabras indexadas', NULL, NULL, 'num', 7),
(1, 'seo', 'nodos_dom', 'Nodos del DOM', 929, 'Estructura ligera', NULL, NULL, 'num', 8);

-- Asegurar módulo SEO en modulos_indicadores si no existe
INSERT INTO modulos_indicadores (dashboard_id, nombre, codigo, tipo_visualizacion, orden, activo)
VALUES (1, 'Auditoría Técnica SEO & Rendimiento', 'seo', 'cards_grid', 9, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = 1;
"""

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

if os.path.exists(KEY_FILE):
    ssh.connect(HOST, port=PORT, username=USER, key_filename=KEY_FILE, timeout=15)
else:
    ssh.connect(HOST, port=PORT, username=USER, password='Password99$', timeout=15)
print("Connected SSH!")

sftp = ssh.open_sftp()
remote_sql = '/home/wwcibe/seo_seed.sql'
with sftp.open(remote_sql, 'w') as f:
    f.write(SQL.encode('utf-8'))
sftp.close()

cmd = f"mysql -u '{DB_USER}' -p'{DB_PASS}' '{DB_NAME}' < '{remote_sql}' 2>&1 && echo 'SEO_SEED_SUCCESS' || echo 'SEO_SEED_FAILED'"
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8', errors='ignore')
print("Output:", out)

ssh.exec_command(f"rm -f {remote_sql}")
ssh.close()
print("Done seeding SEO metrics!")
