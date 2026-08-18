import os
import paramiko

hostname = 'sonia.ai.cibergenios.com'
port = 22022
username = 'wwcibe'
key_file = r'C:\Users\ADMINISTRATIVO\.ssh\id_sonia'
remote_base = '/home/wwcibe/insights.cibergenios.com'
local_base = r'C:\Users\ADMINISTRATIVO\.gemini\antigravity-ide\scratch\MktInsights\public'

print(f"Iniciando despliegue hacia {username}@{hostname}:{port}...")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

try:
    if os.path.exists(key_file):
        ssh.connect(hostname, port=port, username=username, key_filename=key_file, timeout=10)
    else:
        ssh.connect(hostname, port=port, username=username, password='Password99$', timeout=10)
    
    print("SSH Conectado exitosamente!")
    sftp = ssh.open_sftp()

    def upload_dir(local_dir, remote_dir):
        try:
            sftp.stat(remote_dir)
        except IOError:
            print(f"Creando directorio remoto: {remote_dir}")
            ssh.exec_command(f'mkdir -p "{remote_dir}"')

        for item in os.listdir(local_dir):
            local_path = os.path.join(local_dir, item)
            remote_path = f"{remote_dir}/{item}"

            if os.path.isdir(local_path):
                upload_dir(local_path, remote_path)
            else:
                print(f"Subiendo: {local_path} -> {remote_path}")
                sftp.put(local_path, remote_path)

    upload_dir(local_base, remote_base)

    sftp.close()
    
    # Ajustar permisos estándar de cPanel
    ssh.exec_command(f'chmod -R 755 {remote_base}')
    ssh.exec_command(f'find {remote_base} -type f -exec chmod 644 {{}} +')
    
    stdin, stdout, stderr = ssh.exec_command(f'ls -la {remote_base}')
    print("\nContenido desplegado en el servidor:\n", stdout.read().decode('utf-8'))
    
    ssh.close()
    print("\nDespliegue completado con exito en insights.cibergenios.com!")

except Exception as e:
    import traceback
    print(f"Error en despliegue: {e}")
    traceback.print_exc()
