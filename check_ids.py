import re

with open('public/index.html', 'r', encoding='utf-8') as f:
    content = f.read()

ids_found = re.findall(r'id="(section[^"]+)"', content)
print('Section IDs found:', ids_found)
