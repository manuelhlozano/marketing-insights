"""
Normaliza y sincroniza todos los archivos HTML (login.html, login/index.html, admin.html, admin/index.html, index.html)
con rutas absolutas, base href, favicon y redirecciones consistentes.
"""

import os

# 1. Favicon tag
FAVICON_TAG = '<link rel="icon" type="image/png" href="/assets/images/logo_cibergenios.png">'

# ─── 1. LOGIN HTML (login.html y login/index.html) ───────────────────────────
LOGIN_HTML = """<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <base href="/">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marketing Insights | Iniciar Sesión</title>
  <link rel="icon" type="image/png" href="/assets/images/logo_cibergenios.png">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
  
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/css/executive-dashboard.css">
</head>
<body class="light-mode" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; background-color: var(--bg-main);">

  <div style="width: 100%; max-width: 440px;">
    
    <!-- Card de Login -->
    <div class="content-card" style="padding: 40px 36px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08); border-radius: var(--radius-lg);">
      
      <div style="text-align: center; margin-bottom: 28px;">
        <img src="/assets/images/logo_cibergenios.png" alt="Cibergenios" style="max-height: 48px; margin: 0 auto 16px; display: block;">
        <h2 style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px;">
          Marketing Insights
        </h2>
        <p style="font-size: 13px; color: var(--text-muted);">
          Panel de Administración & Control Maestro
        </p>
      </div>

      <div id="errorMsg" style="display: none; padding: 12px 14px; background: var(--danger-red-bg); border: 1px solid rgba(239,68,68,0.3); border-radius: var(--radius-sm); color: var(--danger-red-text); font-size: 12.5px; margin-bottom: 18px; font-weight: 600;">
        Credenciales no válidas. Acceso exclusivo para superadministradores.
      </div>

      <form id="loginForm" onsubmit="handleLogin(event)">
        
        <div class="form-group">
          <label class="form-label">Correo Electrónico</label>
          <input type="email" id="loginEmail" class="form-input" required placeholder="correo@empresa.com" autocomplete="off">
        </div>

        <div class="form-group">
          <label class="form-label">Contraseña</label>
          <input type="password" id="loginPassword" class="form-input" required placeholder="••••••••••••" autocomplete="new-password">
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; font-size: 12.5px;">
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; color: var(--text-secondary);">
            <input type="checkbox" id="rememberMe" checked>
            <span>Recordar sesión</span>
          </label>
        </div>

        <button type="submit" class="btn-action btn-primary" style="width: 100%; justify-content: center; padding: 13px; font-size: 14px; border-radius: var(--radius-sm);">
          <i data-lucide="log-in" style="width: 16px; height: 16px;"></i>
          <span>Iniciar Sesión</span>
        </button>

      </form>

    </div>

    <div style="text-align: center; margin-top: 20px;">
      <a href="/cine-multiplex-villacentro/julio-2026?token=mkt_live_cmv_78a9c0f" style="font-size: 13px; color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
        <span>← Ver Dashboard de Ejemplo (Cine Múltiplex)</span>
      </a>
    </div>

  </div>

  <script>
    if (typeof lucide !== 'undefined') lucide.createIcons();

    function handleLogin(e) {
      e.preventDefault();
      const email = document.getElementById('loginEmail').value.trim();
      const pass = document.getElementById('loginPassword').value.trim();

      // Validación estricta del superadmin
      if (email === 'webmaster@cibergenios.com' && pass === 'Dieg0$m1') {
        localStorage.setItem('mkt_admin_logged', 'true');
        localStorage.setItem('mkt_admin_user', 'webmaster@cibergenios.com');
        window.location.href = '/admin';
      } else {
        document.getElementById('errorMsg').style.display = 'block';
      }
    }
  </script>
</body>
</html>
"""

with open('public/login.html', 'w', encoding='utf-8') as f:
    f.write(LOGIN_HTML)

os.makedirs('public/login', exist_ok=True)
with open('public/login/index.html', 'w', encoding='utf-8') as f:
    f.write(LOGIN_HTML)

print("[OK] login.html y login/index.html actualizados.")

# ─── 2. ADMIN HTML (admin/index.html y admin.html) ───────────────────────────
with open('public/admin/index.html', 'r', encoding='utf-8') as f:
    admin_content = f.read()

# Fix favicon in admin
if 'favicon' not in admin_content:
    admin_content = admin_content.replace('<title>', FAVICON_TAG + '\n  <title>')

# Fix relative links in admin
admin_content = admin_content.replace('href="../css/', 'href="/css/')
admin_content = admin_content.replace('src="../assets/', 'src="/assets/')
admin_content = admin_content.replace("window.location.href = '../login.html'", "window.location.href = '/login'")
admin_content = admin_content.replace("window.location.replace('../login.html')", "window.location.replace('/login')")
admin_content = admin_content.replace("const targetUrl = `../index.html?", "const targetUrl = `/index.html?")

with open('public/admin/index.html', 'w', encoding='utf-8') as f:
    f.write(admin_content)

with open('public/admin.html', 'w', encoding='utf-8') as f:
    f.write(admin_content)

print("[OK] admin/index.html y admin.html sincronizados.")

# ─── 3. INDEX HTML (favicon + token redirect) ─────────────────────────────────
with open('public/index.html', 'r', encoding='utf-8') as f:
    index_content = f.read()

if 'favicon' not in index_content:
    index_content = index_content.replace('<title>', FAVICON_TAG + '\n  <title>')

# Asegurar que redirección del token gate apunte a /login
index_content = index_content.replace("window.location.replace('login.html')", "window.location.replace('/login')")

with open('public/index.html', 'w', encoding='utf-8') as f:
    f.write(index_content)

print("[OK] index.html actualizado con favicon y ruta limpia de login.")
