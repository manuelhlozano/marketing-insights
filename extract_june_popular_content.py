"""
Extract top/popular posts of June 2026 from CSVs
"""

import os
import csv
import json

UPLOAD_DIR = r"C:\Users\ADMINISTRATIVO\.gemini\antigravity-ide\brain\340f08d2-463e-4913-a5b6-c6f10f7bbad0\.user_uploaded"

# 1. Instagram Posts June 2026: media_1787178971003.csv
ig_posts_file = os.path.join(UPLOAD_DIR, "media_1787178971003.csv")
ig_posts = []

if os.path.exists(ig_posts_file):
    with open(ig_posts_file, 'r', encoding='utf-8', errors='ignore') as f:
        reader = csv.reader(f)
        header = next(reader)
        for r in reader:
            if len(r) >= 15:
                try:
                    desc = r[4]
                    tipo = r[8]
                    fecha = r[6]
                    vistas = int(r[11]) if r[11].isdigit() else 0
                    alcance = int(r[12]) if r[12].isdigit() else 0
                    likes = int(r[13]) if r[13].isdigit() else 0
                    shares = int(r[14]) if r[14].isdigit() else 0
                    comments = int(r[16]) if len(r) > 16 and r[16].isdigit() else 0
                    link = r[7]
                    ig_posts.append({
                        'canal': 'Instagram',
                        'tipo': tipo,
                        'descripcion': desc[:120],
                        'fecha': fecha,
                        'vistas': vistas,
                        'alcance': alcance,
                        'likes': likes,
                        'shares': shares,
                        'comments': comments,
                        'link': link
                    })
                except Exception as e:
                    pass

ig_posts.sort(key=lambda x: x['vistas'], reverse=True)

# 2. Facebook Videos June 2026: media_1787178954547.csv
fb_videos_file = os.path.join(UPLOAD_DIR, "media_1787178954547.csv")
fb_videos = []

if os.path.exists(fb_videos_file):
    with open(fb_videos_file, 'r', encoding='utf-8', errors='ignore') as f:
        reader = csv.reader(f)
        header = next(reader)
        for r in reader:
            if len(r) >= 13:
                try:
                    hora = r[6]
                    duracion = r[5]
                    vistas_3s = int(r[10]) if r[10].isdigit() else 0
                    vistas_1min = int(r[11]) if r[11].isdigit() else 0
                    likes = int(r[15]) if len(r) > 15 and r[15].isdigit() else 0
                    shares = int(r[17]) if len(r) > 17 and r[17].isdigit() else 0
                    fb_videos.append({
                        'canal': 'Facebook',
                        'fecha': hora,
                        'duracion': duracion,
                        'vistas_3s': vistas_3s,
                        'vistas_1min': vistas_1min,
                        'likes': likes,
                        'shares': shares
                    })
                except Exception as e:
                    pass

fb_videos.sort(key=lambda x: x['vistas_3s'], reverse=True)

# 3. TikTok Top Posts (desde capturas de pantalla de estadísticas de TikTok en Junio)
tiktok_top = [
    {
        'canal': 'TikTok',
        'titulo': '⚡ El hombre más poderoso del universo ya está aquí',
        'subtitulo': 'Estreno y contenido temático',
        'vistas': 3943,
        'fecha': '23 abr / Viral Jun',
        'badge': 'Top 1 Vistas'
    },
    {
        'canal': 'TikTok',
        'titulo': '🐾 ¿Alguna vez te has preguntado qué pasa por la mente...',
        'subtitulo': 'Humor / Situacional en salas',
        'vistas': 2276,
        'fecha': '24 feb / Viral Jun',
        'badge': 'Top 2 Vistas'
    },
    {
        'canal': 'TikTok',
        'titulo': '👤 Cliente: "¿Qué tiene de dos mil pesos?" 🍿 Confitería',
        'subtitulo': 'Humor en taquilla & snacks',
        'vistas': 1112,
        'fecha': '25 jun 2026',
        'badge': 'Top 3 Orgánico'
    },
    {
        'canal': 'TikTok',
        'titulo': '🎭 ¡De la crisis existencial a los prohibidos de Spider-Man...',
        'subtitulo': 'Tendencias de cine y baile',
        'vistas': 1029,
        'fecha': '25 jun 2026',
        'badge': 'Top 4 Tendencia'
    },
    {
        'canal': 'TikTok',
        'titulo': '👰 El juego no ha terminado y ahora es más mortal 😳',
        'subtitulo': 'Suspenso & Próximos estrenos',
        'vistas': 642,
        'fecha': '20 mar / Jun',
        'badge': 'Top 5 Catálogo'
    }
]

out = {
    'top_instagram_junio': ig_posts[:10],
    'top_facebook_junio': fb_videos[:10],
    'top_tiktok_junio': tiktok_top
}

with open('contenidos_populares_junio.json', 'w', encoding='utf-8') as f:
    json.dump(out, f, ensure_ascii=False, indent=2)

print(f"Extracted {len(ig_posts)} Instagram posts, {len(fb_videos)} Facebook videos, and {len(tiktok_top)} TikTok posts.")
