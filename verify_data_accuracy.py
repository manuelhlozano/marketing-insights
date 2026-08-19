"""
Comprehensive verification of all data points across MySQL wwcibe_mktinsights
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

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

if os.path.exists(KEY_FILE):
    ssh.connect(HOST, port=PORT, username=USER, key_filename=KEY_FILE, timeout=15)
else:
    ssh.connect(HOST, port=PORT, username=USER, password='Password99$', timeout=15)

def run_query(q):
    cmd = f"mysql -u '{DB_USER}' -p'{DB_PASS}' '{DB_NAME}' -e \"{q}\""
    stdin, stdout, stderr = ssh.exec_command(cmd)
    return stdout.read().decode('utf-8', errors='ignore')

print("=== ALL METRICS FOR JULY 2026 (DASHBOARD 1) ===")
print(run_query("SELECT canal, clave, etiqueta, valor_numerico, valor_texto, comparativo_label, comparativo_valor FROM metricas_canal WHERE dashboard_id = 1 ORDER BY canal, orden;"))

print("\n=== ALL METRICS FOR JUNE 2026 (DASHBOARD 2) ===")
print(run_query("SELECT canal, clave, etiqueta, valor_numerico, valor_texto, comparativo_label, comparativo_valor FROM metricas_canal WHERE dashboard_id = 2 ORDER BY canal, orden;"))

print("\n=== POPULAR UGC POSTS (BOTH DASHBOARDS) ===")
print(run_query("SELECT dashboard_id, titulo, canal, vistas, compartidos, likes, badge_label FROM ugc_posts ORDER BY dashboard_id, orden;"))

ssh.close()
