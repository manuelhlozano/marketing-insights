"""
Fix all 404 errors caused by relative paths breaking when URL has sub-segments.
Solution: Add <base href="/"> to all HTML pages so paths resolve from root.
Also fix invalid Lucide icon names (instagram, facebook don't exist in Lucide).
"""

import re

# --- 1. index.html ---
with open('public/index.html', 'r', encoding='utf-8') as f:
    content = f.read()

if '<base href="/">' not in content:
    content = content.replace(
        '  <meta charset="UTF-8">',
        '  <meta charset="UTF-8">\n  <base href="/">'
    )

# Fix invalid Lucide icon names
content = content.replace('data-lucide="instagram"', 'data-lucide="camera"')
content = content.replace('data-lucide="facebook"', 'data-lucide="users"')
content = content.replace('data-lucide="tiktok"', 'data-lucide="music"')

with open('public/index.html', 'w', encoding='utf-8') as f:
    f.write(content)
print("[OK] index.html: base href y iconos corregidos")

# --- 2. login.html ---
with open('public/login.html', 'r', encoding='utf-8') as f:
    content = f.read()

if '<base href="/">' not in content:
    content = content.replace(
        '  <meta charset="UTF-8">',
        '  <meta charset="UTF-8">\n  <base href="/">'
    )

with open('public/login.html', 'w', encoding='utf-8') as f:
    f.write(content)
print("[OK] login.html: base href anadido")

# --- 3. admin/index.html ---
with open('public/admin/index.html', 'r', encoding='utf-8') as f:
    content = f.read()

if '<base href="/">' not in content:
    content = content.replace(
        '  <meta charset="UTF-8">',
        '  <meta charset="UTF-8">\n  <base href="/">'
    )

# Fix relative paths to absolute
content = content.replace('href="../css/executive-dashboard.css"', 'href="/css/executive-dashboard.css"')
content = content.replace('src="../css/', 'src="/css/')
# Fix auth guard redirect to absolute path
content = content.replace("window.location.replace('../login.html')", "window.location.replace('/login')")
content = content.replace('src="../assets/', 'src="/assets/')

with open('public/admin/index.html', 'w', encoding='utf-8') as f:
    f.write(content)
print("[OK] admin/index.html: rutas absolutas corregidas")

# --- 4. Verify ---
print("\n--- Verificacion de base href ---")
for path in ['public/index.html', 'public/login.html', 'public/admin/index.html']:
    with open(path, 'r', encoding='utf-8') as f:
        c = f.read()
    has_base = '<base href="/">' in c
    lucide_bad = any(x in c for x in ['data-lucide="instagram"', 'data-lucide="facebook"', 'data-lucide="tiktok"'])
    print("  %s: base=%s, bad_icons=%s" % (path, has_base, lucide_bad))
