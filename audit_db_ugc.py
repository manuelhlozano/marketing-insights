"""
Audit MySQL database contents and populate June UGC/popular posts if missing
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

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

if os.path.exists(KEY_FILE):
    ssh.connect(HOST, port=PORT, username=USER, key_filename=KEY_FILE, timeout=15)
else:
    ssh.connect(HOST, port=PORT, username=USER, password='Password99$', timeout=15)

def run_query(query):
    cmd = f"mysql -u '{DB_USER}' -p'{DB_PASS}' '{DB_NAME}' -e \"{query}\""
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return stdout.read().decode('utf-8', errors='ignore')

print("=== DASHBOARDS ===")
print(run_query("SELECT id, titulo, slug, periodo FROM dashboards;"))

print("=== UGC POSTS IN DB ===")
print(run_query("SELECT id, dashboard_id, titulo, canal, vistas, compartidos, likes FROM ugc_posts;"))

print("=== METRICAS CANAL COUNT ===")
print(run_query("SELECT dashboard_id, canal, COUNT(*) as count FROM metricas_canal GROUP BY dashboard_id, canal;"))

ssh.close()
