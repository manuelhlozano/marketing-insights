"""
Script para insertar los 117 entregables exactos del CSV en la tabla entregables de MySQL.
"""

import os
import csv
import paramiko
from datetime import datetime

HOST = 'sonia.ai.cibergenios.com'
PORT = 22022
USER = 'wwcibe'
KEY_FILE = r'C:\Users\ADMINISTRATIVO\.ssh\id_sonia'

DB_USER = 'wwcibe_mktinsightsR00t'
DB_PASS = "jnLvx.I^AaMf59L%"
DB_NAME = 'wwcibe_mktinsights'

csv_path = r'C:\Users\ADMINISTRATIVO\.gemini\antigravity-ide\scratch\MktInsights\public\data\raw\entregables_cibergenios_117.csv'

sql_rows = []
with open(csv_path, 'r', encoding='utf-8') as f:
    reader = csv.DictReader(f)
    for row in reader:
        item_id = int(row['ID'])
        nombre = row['Nombre'].replace("'", "''")
        formato = row['Formato']
        categoria = row['Categoria'].replace("'", "''")
        fecha_str = row['Fecha']
        try:
            fecha_dt = datetime.strptime(fecha_str, "%d/%m/%Y")
            fecha_mysql = fecha_dt.strftime("%Y-%m-%d")
        except Exception:
            fecha_mysql = "2026-07-15"
        
        sql_rows.append(f"(1, {item_id}, '{nombre}', '{formato}', '{categoria}', '{fecha_mysql}')")

values_sql = ",\n".join(sql_rows)

SQL = f"""
DELETE FROM entregables WHERE dashboard_id = 1;
INSERT INTO entregables (dashboard_id, numero_item, nombre, formato, categoria, fecha_creacion)
VALUES
{values_sql};
"""

print(f"Total rows to insert: {len(sql_rows)}")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

if os.path.exists(KEY_FILE):
    ssh.connect(HOST, port=PORT, username=USER, key_filename=KEY_FILE, timeout=15)
else:
    ssh.connect(HOST, port=PORT, username=USER, password='Password99$', timeout=15)
print("SSH Connected!")

sftp = ssh.open_sftp()
remote_sql = '/home/wwcibe/entregables_117_seed.sql'
with sftp.open(remote_sql, 'w') as f:
    f.write(SQL.encode('utf-8'))
sftp.close()

cmd = f"mysql -u '{DB_USER}' -p'{DB_PASS}' '{DB_NAME}' < '{remote_sql}' 2>&1 && echo 'ENTREGABLES_SUCCESS' || echo 'ENTREGABLES_FAILED'"
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8', errors='ignore')
print("Output:", out)

# Verify count
cmd_verify = f"mysql -u '{DB_USER}' -p'{DB_PASS}' '{DB_NAME}' -e 'SELECT COUNT(*) as total_entregables FROM entregables;' 2>&1"
stdin, stdout, stderr = ssh.exec_command(cmd_verify)
print("Verify:", stdout.read().decode('utf-8', errors='ignore'))

ssh.exec_command(f"rm -f {remote_sql}")
ssh.close()
print("Seeding completed successfully!")
