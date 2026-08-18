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

# Probar SHOW TABLES
cmd = f"mysql -u {db_user} -p'{db_pass}' {db_name} -e 'SHOW TABLES;'"
stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8')
err = stderr.read().decode('utf-8')

print("RESULTADO DE CONSULTA MYSQL EN wwcibe_mktinsights:")
print("STDOUT:", out)
print("STDERR:", err)

ssh.close()
