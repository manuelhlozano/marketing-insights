import re

with open('public/index.html', 'r', encoding='utf-8') as f:
    content = f.read()

# Add missing section IDs to key landmark sections
replacements = [
    # Meta section (Resumen Mensual y Rendimiento - consolidated table)
    (
        '<div class="content-card" style="margin-bottom: 32px;">\n      <div class="channel-header-pill pill-consolidated">',
        '<div class="content-card" id="sectionMeta" style="margin-bottom: 32px;">\n      <div class="channel-header-pill pill-consolidated">'
    ),
    # TikTok section (grid with Meta + TikTok charts)
    (
        '<!-- SECCIÓN: ANÁLISIS DE EVOLUCIÓN TEMPORAL (META & TIKTOK) -->',
        '<!-- SECCIÓN: ANÁLISIS DE EVOLUCIÓN TEMPORAL (META & TIKTOK) -->'
    ),
    # UGC showcase section
    (
        '<!-- SECCIÓN: SHOWCASE UGC & CREADORES (Slide 7) -->',
        '<!-- SECCIÓN: SHOWCASE UGC & CREADORES (Slide 7) -->'
    ),
]

# Add sectionTikTok to the evolution section div
content = content.replace(
    '<div class="section-title-wrapper">\n      <h3 class="section-title"><i data-lucide="trending-up"',
    '<div class="section-title-wrapper" id="sectionTikTok">\n      <h3 class="section-title"><i data-lucide="trending-up"',
    1  # only first occurrence
)

# Add sectionUgc to UGC showcase section
content = content.replace(
    '<div class="section-title-wrapper">\n      <h3 class="section-title"><i data-lucide="sparkles"',
    '<div class="section-title-wrapper" id="sectionUgc">\n      <h3 class="section-title"><i data-lucide="sparkles"',
    1
)

# Add sectionPauta + sectionEmail to the combined section
content = content.replace(
    '<div class="section-title-wrapper">\n      <h3 class="section-title"><i data-lucide="target"',
    '<div class="section-title-wrapper" id="sectionPauta" style="--email-anchor:1">\n      <h3 class="section-title"><i data-lucide="target"',
    1
)

# Add sectionEntregables to deliverables section
content = content.replace(
    '<div class="section-title-wrapper">\n      <h3 class="section-title"><i data-lucide="folder-check"',
    '<div class="section-title-wrapper" id="sectionEntregables">\n      <h3 class="section-title"><i data-lucide="folder-check"',
    1
)

with open('public/index.html', 'w', encoding='utf-8') as f:
    f.write(content)

# Verify
import re
ids_found = re.findall(r'id="(section[^"]+)"', content)
print('Section IDs now:', ids_found)
