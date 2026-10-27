"""Build trimmed, consistent DIBBS fixtures (public data) for Phase 2 tests.

Picks ~50 index lines covering every set-aside and AMSC value seen, then keeps
the matching bq rows (by solicitation + PR) and as rows (by NSN). Files keep
DLA's exact bytes per line (CRLF, latin-1, 140-char records, quoted CSV).
"""
import os

REF = os.path.join(os.path.dirname(__file__), 'reference')
OUT = os.path.join(os.path.dirname(__file__), '..', '..', '..', 'tests', 'Fixtures', 'dibbs')
os.makedirs(OUT, exist_ok=True)

in_lines = open(os.path.join(REF, 'Downloads_RFQ_Archive_in261002.txt'), 'rb').read().split(b'\r\n')
in_lines = [l for l in in_lines if l]

def field(line, start, width):
    return line[start:start + width].decode('latin-1').strip()

picked, seen_sa, seen_amsc = [], set(), set()
for line in in_lines:  # first pass: one of every set-aside / AMSC value
    # 0-based offsets per the 140-char layout: AMSC at 134, item type 135, set-aside 136
    sa, amsc = field(line, 136, 1), field(line, 134, 1)
    if sa not in seen_sa or amsc not in seen_amsc:
        picked.append(line)
        seen_sa.add(sa)
        seen_amsc.add(amsc)
for line in in_lines:  # second pass: fill to 50 in file order
    if len(picked) >= 50:
        break
    if line not in picked:
        picked.append(line)
picked.sort(key=in_lines.index)

keys = {(field(l, 0, 13), field(l, 59, 13)) for l in picked}
nsns = {field(l, 13, 46) for l in picked}

bq_in = open(os.path.join(REF, 'bq', 'bq261002.txt'), 'rb').read().split(b'\r\n')
bq_keep = []
for raw in bq_in:
    if not raw:
        continue
    cols = raw.decode('latin-1').split('","')
    sol, pr = cols[0].strip('"'), cols[45].strip('"')
    if (sol, pr) in keys:
        bq_keep.append(raw)

as_in = open(os.path.join(REF, 'bq', 'as261002.txt'), 'rb').read().split(b'\r\n')
as_keep = [raw for raw in as_in if raw and raw.decode('latin-1').split('","')[0].strip('"') in nsns]

for name, lines in [('in261002.txt', picked), ('bq261002.txt', bq_keep), ('as261002.txt', as_keep)]:
    open(os.path.join(OUT, name), 'wb').write(b'\r\n'.join(lines) + b'\r\n')
    print(f'{name}: {len(lines)} lines')
print('set-asides:', sorted(seen_sa), 'AMSC:', sorted(seen_amsc))
