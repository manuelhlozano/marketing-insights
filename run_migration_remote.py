import paramiko
import os

hostname = 'sonia.ai.cibergenios.com'
port = 22022
username = 'wwcibe'
key_file = r'C:\Users\ADMINISTRATIVO\.ssh\id_sonia'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(hostname, port=port, username=username, key_filename=key_file, timeout=10)

db_user = "wwcibe_mktinsightsR00t"
db_pass = "jnLvx.I^AaMf59L%"
db_name = "wwcibe_mktinsights"

# Subir schema_and_seed.sql
sftp = ssh.open_sftp()
local_sql = r'C:\Users\ADMINISTRATIVO\.gemini\antigravity-ide\scratch\MktInsights\database\schema_and_seed.sql'
remote_sql = '/home/wwcibe/insights.cibergenios.com/schema_and_seed.sql'
sftp.put(local_sql, remote_sql)
sftp.close()
print("SQL subido a", remote_sql)

# Ejecutar SQL estrictamente en wwcibe_mktinsights
cmd = f"mysql -u {db_user} -p'{db_pass}' {db_name} < {remote_sql}"
stdin, stdout, stderr = ssh.exec_command(cmd)
print("Ejecutando SQL en", db_name)
out = stdout.read().decode('utf-8')
err = stderr.read().decode('utf-8')
if out: print("OUT:", out)
if err: print("ERR:", err)

# Verificar tablas creadas
cmd_check = f"mysql -u {db_user} -p'{db_pass}' {db_name} -e 'SHOW TABLES; SELECT id, nombre, slug, token_acceso_maestro FROM empresas;'"
stdin, stdout, stderr = ssh.exec_command(cmd_check)
print("\nTABLAS EN", db_name, ":\n", stdout.read().decode('utf-8'))

# Borrar archivo temporal SQL del servidor
ssh.exec_command(f"rm -f {remote_sql}")

ssh.close()
print("Base de datos wwcibe_mktinsights inicializada con exito!")
