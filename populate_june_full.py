"""
Full standalone seeder for June 2026 comparative metrics and time series in MySQL.
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

# Exact datasets from user inputs (30 days of June 2026)
alcance_meta = [3739, 2437, 2301, 2363, 1428, 1159, 1069, 1673, 2511, 1305, 1038, 1794, 1606, 3450, 2659, 2768, 3954, 3428, 3150, 3907, 3117, 2405, 2950, 744, 517, 783, 612, 689, 823, 488]
vis_meta = [7763, 7965, 6593, 5397, 4844, 2860, 3326, 3477, 4683, 2367, 3732, 4395, 3535, 6717, 4791, 5726, 16560, 10280, 6944, 8229, 7084, 5173, 17376, 5442, 3262, 4778, 3481, 2218, 1796, 1446]
vis_fb = [2339, 6158, 2613, 2086, 2775, 1703, 1679, 1300, 1192, 1167, 2615, 3542, 1634, 2113, 1206, 1285, 9065, 4155, 1795, 5497, 4364, 1618, 15545, 6167, 2173, 2587, 2293, 2264, 2063, 1931]
interacciones = [13, 21, 11, 16, 14, 5, 5, 3, 3, 0, 22, 32, 7, 3, 2, 13, 186, 14, 8, 36, 40, 19, 258, 24, 12, 12, 4, 3, 20, 24]
espectadores = [1064, 2473, 1068, 887, 1152, 792, 959, 624, 579, 473, 1028, 1474, 863, 875, 624, 703, 4711, 2324, 964, 1828, 1818, 733, 6700, 2056, 943, 903, 1036, 1086, 1024, 723]
clics_fb = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 1, 0, 2, 0, 4]
clics_ig = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 1]
segs_ig = [7, 8, 8, 5, 10, 4, 11, 11, 12, 12, 6, 10, 8, 17, 4, 8, 106, 13, 4, 7, 10, 3, 99, 11, 7, 14, 19, 4, 1, 3]
segs_fb = [1, 2, 2, 2, 1, 1, 5, 0, 0, 0, 2, 1, 1, 4, 1, 0, 25, 10, 2, 1, 0, 0, 36, 3, 0, 1, 3, 4, 2, 6]
visitas_fb = [98, 153, 172, 104, 122, 119, 173, 177, 110, 98, 70, 83, 102, 150, 112, 56, 286, 149, 102, 153, 134, 99, 585, 351, 120, 126, 105, 140, 113, 154]
visitas_ig = [26, 29, 41, 32, 30, 31, 42, 20, 34, 31, 34, 28, 27, 34, 39, 50, 228, 128, 56, 59, 51, 52, 242, 88, 44, 85, 82, 57, 56, 42]

sql = []

# 1. Asegurar dashboard de Junio en la BD
sql.append("""
INSERT INTO dashboards (id, empresa_id, titulo, slug, periodo, fecha_inicio, fecha_fin, lema, descripcion_ejecutiva, public_token, es_publico)
VALUES (2, 1, 'Informe Ejecutivo de Gestión Mensual | Junio 2026', 'junio-2026', 'Junio 2026', '2026-06-01', '2026-06-30', 'Periodo Previo y Transición Operativa', 'Línea base previa a la gestión integral de Cibergenios.', 'mkt_live_cmv_jun2026', 1)
ON DUPLICATE KEY UPDATE lema = VALUES(lema), es_publico = 1;

DELETE FROM metricas_canal WHERE dashboard_id = 2;
DELETE FROM series_tiempo WHERE dashboard_id = 2;
""")

# 2. Métricas de canal
metricas = [
    (2, 'meta', 'visualizaciones_totales', 'Visualizaciones Meta', 172200, '172.2K', 'Mes Previo', 'NULL', 'num', 1),
    (2, 'meta', 'alcance_total', 'Alcance Total Meta', 56867, '56.8K', 'Mes Previo', 'NULL', 'num', 2),
    (2, 'meta', 'interacciones_totales', 'Interacciones Totales', 5700, '5.7K', 'Mes Previo', 'NULL', 'num', 3),
    (2, 'meta', 'clics_enlace_total', 'Clics en Enlace a Cartelera', 15, '15 clics', 'Mes Previo', 'NULL', 'num', 4),
    (2, 'meta', 'espectadores_unicos', 'Espectadores Únicos', 44020, '44.0K', 'Mes Previo', 'NULL', 'num', 5),
    (2, 'meta', 'seguidores_totales', 'Seguidores al Cierre', 50129, '50.129', 'Mes Previo', 'NULL', 'num', 6),
    (2, 'meta', 'crecimiento_neto', 'Crecimiento Neto', 51, '+51 seguidores', 'Mes Previo', 'NULL', 'num', 7),
    # Facebook
    (2, 'facebook', 'visualizaciones', 'Visualizaciones Facebook', 96924, '96.9K', '100% Orgánico', 'NULL', 'num', 1),
    (2, 'facebook', 'reproducciones_3s', 'Reproducciones 3s', 11500, '11.5K', 'Videos FB', 'NULL', 'num', 2),
    (2, 'facebook', 'interacciones', 'Interacciones FB', 830, '830', 'Mes Previo', 'NULL', 'num', 3),
    (2, 'facebook', 'visitas_pagina', 'Visitas a la Página', 4316, '4.3K', 'Mes Previo', 'NULL', 'num', 4),
    (2, 'facebook', 'clics_enlace', 'Clics en Enlace FB', 9, '9 clics', 'Mes Previo', 'NULL', 'num', 5),
    (2, 'facebook', 'seguidores_ganados', 'Nuevos Seguidores FB', 116, '+116', 'Mes Previo', 'NULL', 'num', 6),
    # Instagram
    (2, 'instagram', 'visualizaciones', 'Visualizaciones Instagram', 117367, '117.4K', '100% Orgánico', 'NULL', 'num', 1),
    (2, 'instagram', 'alcance', 'Alcance Instagram', 48900, '48.9K', 'Mes Previo', 'NULL', 'num', 2),
    (2, 'instagram', 'visitas_perfil', 'Visitas al Perfil', 1933, '1.9K', 'Mes Previo', 'NULL', 'num', 3),
    (2, 'instagram', 'clics_enlace', 'Clics en Enlace IG', 6, '6 clics', 'Mes Previo', 'NULL', 'num', 4),
    (2, 'instagram', 'seguidores_ganados', 'Nuevos Seguidores IG', 442, '+442', 'Mes Previo', 'NULL', 'num', 5),
    # Atención al cliente
    (2, 'atencion', 'tiempo_respuesta_fb', 'Tiempo de Respuesta Facebook', 925, '15 h 25 min', 'Atención FB', 'NULL', 'min', 1),
    (2, 'atencion', 'tiempo_respuesta_ig', 'Tiempo de Respuesta Instagram', 0.6, '36 segundos', 'Atención IG', 'NULL', 'seg', 2),
    (2, 'atencion', 'indice_respuesta_fb', 'Índice de Respuesta FB', 50.0, '50.0%', 'Atención FB', 'NULL', '%', 3),
    (2, 'atencion', 'indice_respuesta_ig', 'Índice de Respuesta IG', 90.6, '90.6%', 'Atención IG', 'NULL', '%', 4),
]


metricas_rows = []
for m in metricas:
    dash, canal, clave, etiqueta, vnum, vtxt, clbl, cval, unidad, orden = m
    metricas_rows.append(f"({dash}, '{canal}', '{clave}', '{etiqueta}', {vnum}, '{vtxt}', '{clbl}', {cval}, '{unidad}', {orden})")

sql.append("INSERT INTO metricas_canal (dashboard_id, canal, clave, etiqueta, valor_numerico, valor_texto, comparativo_label, comparativo_valor, unidad, orden) VALUES\n" + ",\n".join(metricas_rows) + ";")

# 3. Series de tiempo (30 días de Junio)
series_rows = []
for i in range(30):
    day = i + 1
    d_lbl = f"{day} jun"

    series_rows.append(f"(2, 'meta', 'alcance', '{d_lbl}', {alcance_meta[i]}, {day})")
    series_rows.append(f"(2, 'meta', 'visualizaciones', '{d_lbl}', {vis_meta[i]}, {day})")
    series_rows.append(f"(2, 'facebook', 'visualizaciones', '{d_lbl}', {vis_fb[i]}, {day})")
    series_rows.append(f"(2, 'meta', 'interacciones', '{d_lbl}', {interacciones[i]}, {day})")
    series_rows.append(f"(2, 'meta', 'espectadores', '{d_lbl}', {espectadores[i]}, {day})")
    series_rows.append(f"(2, 'facebook', 'clics', '{d_lbl}', {clics_fb[i]}, {day})")
    series_rows.append(f"(2, 'instagram', 'clics', '{d_lbl}', {clics_ig[i]}, {day})")
    series_rows.append(f"(2, 'instagram', 'seguidores', '{d_lbl}', {segs_ig[i]}, {day})")
    series_rows.append(f"(2, 'facebook', 'seguidores', '{d_lbl}', {segs_fb[i]}, {day})")
    series_rows.append(f"(2, 'facebook', 'visitas', '{d_lbl}', {visitas_fb[i]}, {day})")
    series_rows.append(f"(2, 'instagram', 'visitas', '{d_lbl}', {visitas_ig[i]}, {day})")

sql.append("INSERT INTO series_tiempo (dashboard_id, canal, serie, periodo_label, valor, orden) VALUES\n" + ",\n".join(series_rows) + ";")


FULL_SQL = "\n".join(sql)

with open('seed_junio_full.sql', 'w', encoding='utf-8') as f:
    f.write(FULL_SQL)

print(f"Generated seed_junio_full.sql with {len(FULL_SQL)} characters and {len(series_rows)} series records.")

# Execute via SSH
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

if os.path.exists(KEY_FILE):
    ssh.connect(HOST, port=PORT, username=USER, key_filename=KEY_FILE, timeout=15)
else:
    ssh.connect(HOST, port=PORT, username=USER, password='Password99$', timeout=15)
print("SSH Connected!")

sftp = ssh.open_sftp()
remote_sql = '/home/wwcibe/seed_junio_full.sql'
sftp.put('seed_junio_full.sql', remote_sql)
sftp.close()

cmd = f"mysql -u '{DB_USER}' -p'{DB_PASS}' '{DB_NAME}' < '{remote_sql}' 2>&1 && echo 'JUNIO_FULL_SUCCESS' || echo 'JUNIO_FULL_FAILED'"
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8', errors='ignore')
print("Output:", out)

ssh.exec_command(f"rm -f {remote_sql}")
ssh.close()
print("Done seeding June 2026 into MySQL!")
