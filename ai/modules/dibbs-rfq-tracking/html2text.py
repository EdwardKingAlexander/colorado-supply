"""Turn a saved DIBBS HTML page into readable text (tables become tab-separated rows)."""
import html
import re
import sys

src = open(sys.argv[1], encoding='utf-8', errors='replace').read()
src = re.sub(r'(?is)<(script|style|head).*?</\1>', '', src)
src = re.sub(r'(?i)</t[dh]>', '\t', src)
src = re.sub(r'(?i)<(br|/tr|/p|/div|/li|/h\d)[^>]*>', '\n', src)
text = html.unescape(re.sub(r'<[^>]+>', ' ', src))
lines = [re.sub(r'[  ]+', ' ', l).strip(' \t') for l in text.splitlines()]
out = '\n'.join(l for l in lines if l.strip())
if len(sys.argv) > 2:
    open(sys.argv[2], 'w', encoding='utf-8').write(out)
print(out)
