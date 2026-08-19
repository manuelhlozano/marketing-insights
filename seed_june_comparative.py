"""
Seeds June 2026 data into MySQL database wwcibe_mktinsights for comparative analysis.
"""

import os
import json
import paramiko

HOST = 'sonia.ai.cibergenios.com'
PORT = 22022
USER = 'wwcibe'
KEY_FILE = r'C:\Users\ADMINISTRATIVO\.ssh\id_sonia'

DB_USER = 'wwcibe_mktinsightsR00t'
DB_PASS = "jnLvx.I^AaMf59L%"
DB_NAME = 'wwcibe_mktinsights'

# Load consolidated data
with open('data_comparativo_junio_2026.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

sql_statements = []

# 1. Dashboard de Junio
sql_statements.append("""
INSERT INTO dashboards (empresa_id, nombre, periodo_mes_anio, slug, token_publico, lema, activo)
VALUES (1, 'Cine Múltiplex - Junio 2026', 'Junio 2026', 'junio-2026', 'mkt_live_cmv_jun2026', 'Periodo Previo y Transición Operativa', 1)
ON DUPLICATE KEY UPDATE lema = VALUES(lema), activo = 1;
""")

# Obtenemos el id del dashboard
# Para SQL podemos usar variables
sql_statements.append("""
SET @dash_junio = (SELECT id FROM dashboards WHERE slug = 'junio-2026' LIMIT 1);
DELETE FROM metricas_canal WHERE dashboard_id = @dash_junio;
DELETE FROM series_tiempo WHERE dashboard_id = @dash_junio;
""")

# 2. Métricas de Canal de Junio
metricas = [
    # Meta general
    ('@dash_junio', 'meta', 'visualizaciones_totales', 'Visualizaciones Meta', 172200, '172.2K', 'Mes Previo', None, 'num', 1),
    ('@dash_junio', 'meta', 'alcance_total', 'Alcance Total Meta', 56867, '56.8K', 'Mes Previo', None, 'num', 2),
    ('@dash_junio', 'meta', 'interacciones_totales', 'Interacciones Totales', 5700, '5.7K', 'Mes Previo', None, 'num', 3),
    ('@dash_junio', 'meta', 'clics_enlace_total', 'Clics en Enlace a Cartelera', 15, '15 clics', 'Mes Previo', None, 'num', 4),
    ('@dash_junio', 'meta', 'espectadores_unicos', 'Espectadores Únicos', 44020, '44.0K', 'Mes Previo', None, 'num', 5),
    ('@dash_junio', 'meta', 'seguidores_totales', 'Seguidores al Cierre', 50129, '50.129', 'Mes Previo', None, 'num', 6),
    ('@dash_junio', 'meta', 'crecimiento_neto', 'Crecimiento Neto', 51, '+51 seguidores', 'Mes Previo', None, 'num', 7),
    # Facebook
    ('@dash_junio', 'facebook', 'visualizaciones', 'Visualizaciones Facebook', 96924, '96.9K', '100% Orgánico', None, 'num', 1),
    ('@dash_junio', 'facebook', 'reproducciones_3s', 'Reproducciones 3s', 11500, '11.5K', 'Videos FB', None, 'num', 2),
    ('@dash_junio', 'facebook', 'interacciones', 'Interacciones FB', 830, '830', 'Mes Previo', None, 'num', 3),
    ('@dash_junio', 'facebook', 'visitas_pagina', 'Visitas a la Página', 4316, '4.3K', 'Mes Previo', None, 'num', 4),
    ('@dash_junio', 'facebook', 'clics_enlace', 'Clics en Enlace FB', 9, '9 clics', 'Mes Previo', None, 'num', 5),
    ('@dash_junio', 'facebook', 'seguidores_ganados', 'Nuevos Seguidores FB', 116, '+116', 'Mes Previo', None, 'num', 6),
    # Instagram
    ('@dash_junio', 'instagram', 'visualizaciones', 'Visualizaciones Instagram', 117367, '117.4K', '100% Orgánico', None, 'num', 1),
    ('@dash_junio', 'instagram', 'alcance', 'Alcance Instagram', 48900, '48.9K', 'Mes Previo', None, 'num', 2),
    ('@dash_junio', 'instagram', 'visitas_perfil', 'Visitas al Perfil', 1933, '1.9K', 'Mes Previo', None, 'num', 3),
    ('@dash_junio', 'instagram', 'clics_enlace', 'Clics en Enlace IG', 6, '6 clics', 'Mes Previo', None, 'num', 4),
    ('@dash_junio', 'instagram', 'seguidores_ganados', 'Nuevos Seguidores IG', 442, '+442', 'Mes Previo', None, 'num', 5),
    # Atención al cliente
    ('@dash_junio', 'atencion', 'tiempo_respuesta_fb', 'Tiempo de Respuesta Facebook', 925, '15 h 25 min', 'Atención FB', None, 'min', 1),
    ('@dash_junio', 'atencion', 'tiempo_respuesta_ig', 'Tiempo de Respuesta Instagram', 0.6, '36 segundos', 'Atención IG', None, 'seg', 2),
    ('@dash_junio', 'atencion', 'indice_respuesta_fb', 'Índice de Respuesta FB', 50.0, '50.0%', 'Atención FB', None, '%', 3),
    ('@dash_junio', 'atencion', 'indice_respuesta_ig', 'Índice de Respuesta IG', 90.6, '90.6%', 'Atención IG', None, '%', 4),

]

sql_metricas_values = []
for m in metricas:
    dash, canal, clave, etiqueta, vnum, vtxt, clbl, cval, unidad, orden = m
    vnum_str = str(vnum) if vnum is not None else 'NULL'
    cval_str = str(cval) if cval is not None else 'NULL'
    sql_metricas_values.append(f"({dash}, '{canal}', '{clave}', '{etiqueta}', {vnum_str}, '{vtxt}', '{clbl}', {cval_str}, '{unidad}', {orden})")

sql_statements.append("INSERT INTO metricas_canal (dashboard_id, canal, clave, etiqueta, valor_numerico, valor_texto, comparativo_label, comparativo_valor, unidad, orden) VALUES\n" + ",\n".join(sql_metricas_values) + ";")

# 3. Series de Tiempo Diarias de Junio
# Alcance
alcance_list = data['batch_1']['alcance_diario_meta']
series_sql = []
for i, item in enumerate(alcance_list):
    day_lbl = f"{int(item['fecha'].split('-')[2])} jun"
    series_sql.append(f"(@dash_junio, 'meta', 'alcance', '{day_lbl}', '{item['fecha']}', {item['alcance']}, {i+1})")

# Visualizaciones diarias FB y Meta (desde batch 5)
vis_fb_list = [2339, 6158, 2613, 2086, 2775, 1703, 1679, 1300, 1192, 1167, 2615, 3542, 1634, 2113, 1206, 1285, 9065, 4155, 1795, 5497, 4364, 1618, 15545, 6167, 2173, 2587, 2293, 2264, 2063, 1931]
vis_meta_list = [7763, 7965, 6593, 5397, 4844, 2860, 3326, 3477, 4683, 2367, 3732, 4395, 3535, 6717, 4791, 5726, 16560, 10280, 6944, 8229, 7084, 5173, 17376, 5442, 3262, 4778, 3481, 2218, 1796, 1446]

for i in range(30):
    f_str = f"2026-06-{i+1:02d}"
    d_lbl = f"{i+1} jun"
    series_sql.append(f"(@dash_junio, 'meta', 'visualizaciones', '{d_lbl}', '{f_str}', {vis_meta_list[i]}, {i+1})")
    series_sql.append(f"(@dash_junio, 'facebook', 'visualizaciones', '{d_lbl}', '{f_str}', {vis_fb_list[i]}, {i+1})")

# Interacciones diarias
interac_list = data['batch_3']['interacciones_diarias']
for i, item in enumerate(interac_list):
    day_lbl = f"{int(item['fecha'].split('-')[2])} jun"
    series_sql.append(f"(@dash_junio, 'meta', 'interacciones', '{day_lbl}', '{item['fecha']}', {item['interacciones']}, {i+1})")

# Espectadores diarios
espec_list = data['batch_3']['espectadores_diarios']
for i, item in enumerate(espec_list):
    day_lbl = f"{int(item['fecha'].split('-')[2])} jun"
    series_sql.append(f"(@dash_junio, 'meta', 'espectadores', '{day_lbl}', '{item['fecha']}', {item['espectadores']}, {i+1})")

# Clics FB y Clics IG
clics_fb = data['batch_2']['facebook_clics_enlace_diario']
for i, item in enumerate(clics_fb):
    day_lbl = f"{int(item['fecha'].split('-')[2])} jun"
    series_sql.append(f"(@dash_junio, 'facebook', 'clics', '{day_lbl}', '{item['fecha']}', {item['clics']}, {i+1})")

clics_ig = data['batch_3']['instagram_clics_enlace_diario']
for i, item in enumerate(clics_ig):
    day_lbl = f"{int(item['fecha'].split('-')[2])} jun"
    series_sql.append(f"(@dash_junio, 'instagram', 'clics', '{day_lbl}', '{item['fecha']}', {item['clics']}, {i+1})")

# Seguidores diarios IG y FB
segs_ig = data['batch_4']['seguidores_diarios_instagram']
for i, item in enumerate(segs_ig):
    day_lbl = f"{int(item['fecha'].split('-')[2])} jun"
    series_sql.append(f"(@dash_junio, 'instagram', 'seguidores', '{day_lbl}', '{item['fecha']}', {item['seguidores']}, {i+1})")

segs_fb = data['batch_4']['seguidores_diarios_facebook']
for i, item in enumerate(segs_fb):
    day_lbl = f"{int(item['fecha'].split('-')[2])} jun"
    series_sql.append(f"(@dash_junio, 'facebook', 'seguidores', '{day_lbl}', '{item['fecha']}', {item['seguidores']}, {i+1})")

# Visitas de FB e IG
visitas_fb = [98, 153, 172, 104, 122, 119, 173, 177, 110, 98, 70, 83, 102, 150, 112, 56, 286, 149, 102, 153, 134, 99, 585, 351, 120, 126, 105, 140, 113, 154]
visitas_ig = [26, 29, 41, 32, 30, 31, 42, 20, 34, 31, 34, 28, 27, 34, 39, 50, 228, 128, 56, 59, 51, 52, 242, 88, 44, 85, 82, 57, 56, 42]

for i in range(30):
    f_str = f"2026-06-{i+1:02d}"
    d_lbl = f"{i+1} jun"
    series_sql.append(f"(@dash_junio, 'facebook', 'visitas', '{d_lbl}', '{f_str}', {visitas_fb[i]}, {i+1})")
    series_sql.append(f"(@dash_junio, 'instagram', 'visitas', '{d_lbl}', '{f_str}', {visitas_ig[i]}, {i+1})")

sql_statements.append("INSERT INTO series_tiempo (dashboard_id, canal, serie, periodo_label, fecha, valor, orden) VALUES\n" + ",\n".join(series_sql) + ";")

FULL_SQL = "\n".join(sql_statements)

with open('seed_junio.sql', 'w', encoding='utf-8') as f:
    f.write(FULL_SQL)

print(f"Generated seed_junio.sql with {len(FULL_SQL)} bytes.")

# SSH Execute
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

if os.path.exists(KEY_FILE):
    ssh.connect(HOST, port=PORT, username=USER, key_filename=KEY_FILE, timeout=15)
else:
    ssh.connect(HOST, port=PORT, username=USER, password='Password99$', timeout=15)
print("Connected SSH!")

sftp = ssh.open_sftp()
remote_sql = '/home/wwcibe/seed_junio.sql'
sftp.put('seed_junio.sql', remote_sql)
sftp.close()

cmd = f"mysql -u '{DB_USER}' -p'{DB_PASS}' '{DB_NAME}' < '{remote_sql}' 2>&1 && echo 'JUNIO_SEED_SUCCESS' || echo 'JUNIO_SEED_FAILED'"
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8', errors='ignore')
print("Output:", out)

ssh.exec_command(f"rm -f {remote_sql}")
ssh.close()
print("Done seeding June 2026 comparative data into MySQL!")
