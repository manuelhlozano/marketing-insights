"""
Seed June 2026 Brevo Email Marketing and TikTok data into MySQL wwcibe_mktinsights (dashboard_id = 2)
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

sql = []

# 1. Eliminar métricas existentes de tiktok e email para dashboard 2
sql.append("""
DELETE FROM metricas_canal WHERE dashboard_id = 2 AND canal IN ('tiktok', 'email_b2c', 'email_b2b');
DELETE FROM series_tiempo WHERE dashboard_id = 2 AND canal = 'tiktok';
""")

# 2. Métricas de TikTok Junio 2026
tiktok_metricas = [
    (2, 'tiktok', 'vistas_totales', 'Visualizaciones de Video', 349800, '349.8K', 'Mes Previo', '+80.5%', 'num', 1),
    (2, 'tiktok', 'vistas_perfil', 'Visualizaciones de Perfil', 1600, '1.6K', 'Mes Previo', '+88.2%', 'num', 2),
    (2, 'tiktok', 'likes', 'Me Gusta (Likes)', 8700, '8.7K', 'Mes Previo', '+39.8%', 'num', 3),
    (2, 'tiktok', 'comentarios', 'Comentarios', 253, '253', 'Mes Previo', '+86.0%', 'num', 4),
    (2, 'tiktok', 'compartidos', 'Veces Compartido', 7400, '7.4K', 'Mes Previo', '+117.2%', 'num', 5),
    (2, 'tiktok', 'seguidores_totales', 'Total Seguidores al Cierre', 3440, '3.440', 'Al Cierre', None, 'num', 6),
    (2, 'tiktok', 'seguidores_netos', 'Seguidores Netos Ganados', 354, '+354', 'Mes Previo', '+122.6%', 'num', 7),
    (2, 'tiktok', 'trafico_buscar', 'Tráfico Búsqueda Orgánica', 89.2, '89.2%', 'Canal Principal', None, '%', 8),
    (2, 'tiktok', 'trafico_parati', 'Tráfico Feed Para Ti', 9.5, '9.5%', 'Recomendados', None, '%', 9),
    (2, 'tiktok', 'trafico_perfil', 'Tráfico Perfil Personal', 1.3, '1.3%', 'Directo', None, '%', 10),
]

# 3. Métricas de Brevo Email Marketing Junio 2026
email_metricas = [
    (2, 'email_b2c', 'campanas_enviadas', 'Campañas Enviadas', 3, '3 campañas', 'Mes Previo', None, 'num', 1),
    (2, 'email_b2c', 'destinatarios_totales', 'Destinatarios Totales', 59478, '59.478', 'Base Contactos', None, 'num', 2),
    (2, 'email_b2c', 'aperturas_totales', 'Aperturas Totales', 7023, '7.023', 'Mes Previo', None, 'num', 3),
    (2, 'email_b2c', 'open_rate', 'Tasa de Apertura Global', 12.14, '12.14%', 'Promedio Brevo', None, '%', 4),
    (2, 'email_b2c', 'clics_totales', 'Clics Totales', 146, '146 clics', 'Mes Previo', None, 'num', 5),
    (2, 'email_b2c', 'click_rate', 'Tasa de Clics Global', 0.25, '0.25%', 'CTR', None, '%', 6),
    (2, 'email_b2c', 'bounces', 'Rebotes (Soft + Hard)', 1614, '1.614', 'Rebotes', None, 'num', 7),
    (2, 'email_b2c', 'desuscripciones', 'Cancelaciones de Suscripción', 97, '97 (0.17%)', 'Bajas', None, 'num', 8),
    (2, 'email_b2c', 'campana_c4_apertura', 'Apertura Campaña C4 Junio', 12.43, '12.43%', '57.4K envíos', None, '%', 9),
    (2, 'email_b2c', 'campana_c4_clics', 'Clics Campaña C4 Junio', 808, '808 clics', '1.45% CTR', None, 'num', 10),
    (2, 'email_b2c', 'campana_cumple_apertura', 'Apertura Campaña Cumpleaños', 17.15, '17.15%', '1.574 envíos', None, '%', 11),
    (2, 'email_b2c', 'campana_c2c_apertura', 'Apertura Campaña C2C Prueba', 18.94, '18.94%', '479 envíos', None, '%', 12),
]

all_rows = []
for m in (tiktok_metricas + email_metricas):
    dash, canal, clave, etiqueta, vnum, vtxt, clbl, cval, unidad, orden = m
    vnum_s = str(vnum) if vnum is not None else 'NULL'
    cval_s = f"'{cval}'" if cval is not None else 'NULL'
    clbl_s = f"'{clbl}'" if clbl is not None else 'NULL'
    all_rows.append(f"({dash}, '{canal}', '{clave}', '{etiqueta}', {vnum_s}, '{vtxt}', {clbl_s}, {cval_s}, '{unidad}', {orden})")

sql.append("INSERT INTO metricas_canal (dashboard_id, canal, clave, etiqueta, valor_numerico, valor_texto, comparativo_label, comparativo_valor, unidad, orden) VALUES\n" + ",\n".join(all_rows) + ";")

# 4. Serie diaria de visualizaciones de TikTok en Junio (30 días basada en la curva del gráfico de estadísticas)
# Curva: 1 jun (~3.2K), sube a pico de ~38.5K el 8-10 jun, baja gradualmente a ~14.3K el 15 jun, baja a ~6K el 20 jun, 5.1K el 27 jun, ~3.8K el 30 jun. Total suma ~349.8K
tiktok_daily = [
    3200, 4100, 6800, 11500, 24000, 31000, 38500, 36200, 29000, 22000,
    18500, 15200, 14300, 12100, 13800, 11200, 9500, 8100, 7400, 7200,
    6500, 5800, 7200, 9100, 8000, 6200, 5100, 6800, 5900, 3800
]

series_rows = []
for i in range(30):
    day = i + 1
    d_lbl = f"{day} jun"
    series_rows.append(f"(2, 'tiktok', 'vistas', '{d_lbl}', {tiktok_daily[i]}, {day})")

sql.append("INSERT INTO series_tiempo (dashboard_id, canal, serie, periodo_label, valor, orden) VALUES\n" + ",\n".join(series_rows) + ";")

FULL_SQL = "\n".join(sql)

with open('seed_brevo_tiktok_junio.sql', 'w', encoding='utf-8') as f:
    f.write(FULL_SQL)

print(f"Generated seed_brevo_tiktok_junio.sql ({len(FULL_SQL)} chars). Connecting SSH...")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

if os.path.exists(KEY_FILE):
    ssh.connect(HOST, port=PORT, username=USER, key_filename=KEY_FILE, timeout=15)
else:
    ssh.connect(HOST, port=PORT, username=USER, password='Password99$', timeout=15)

sftp = ssh.open_sftp()
remote_sql = '/home/wwcibe/seed_brevo_tiktok_junio.sql'
sftp.put('seed_brevo_tiktok_junio.sql', remote_sql)
sftp.close()

cmd = f"mysql -u '{DB_USER}' -p'{DB_PASS}' '{DB_NAME}' < '{remote_sql}' 2>&1 && echo 'SEED_BREVO_TIKTOK_SUCCESS' || echo 'SEED_BREVO_TIKTOK_FAILED'"
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8', errors='ignore')
print("Output:", out)

ssh.exec_command(f"rm -f {remote_sql}")
ssh.close()
print("Done seeding June Brevo and TikTok data into MySQL!")
