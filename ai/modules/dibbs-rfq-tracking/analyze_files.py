"""Phase 1: parse a DIBBS in/bq/as file set with the documented layouts and report stats.

Usage: python analyze_files.py <in-file> <bq-file> <as-file> [our FSC list, comma-separated]
"""
import collections
import csv
import sys

IN_LAYOUT = [  # (name, width) per /Rfq/RfqFileDefs.aspx — 140 chars total
    ('solicitation', 13), ('nsn_part', 46), ('purchase_request', 13), ('return_by', 8),
    ('file_name', 19), ('qty', 7), ('unit_issue', 2), ('nomenclature', 21), ('buyer_code', 5),
    ('amsc', 1), ('item_type', 1), ('set_aside', 1), ('set_aside_pct', 3),
]
assert sum(w for _, w in IN_LAYOUT) == 140

in_path, bq_path, as_path = sys.argv[1:4]
ours = set(sys.argv[4].split(',')) if len(sys.argv) > 4 else set()

rows = []
for raw in open(in_path, encoding='latin-1'):
    line = raw.rstrip('\r\n')
    rec, pos = {}, 0
    for name, width in IN_LAYOUT:
        rec[name] = line[pos:pos + width].strip()
        pos += width
    rows.append(rec)

nsn_rows = [r for r in rows if r['item_type'] == '1']
print(f"IN: {len(rows)} lines, {len({r['solicitation'] for r in rows})} solicitations, "
      f"{len(nsn_rows)} NSN lines / {len(rows) - len(nsn_rows)} part-number lines")
print('  NSN shapes:', collections.Counter(len(r['nsn_part']) for r in nsn_rows).most_common(3))
print('  set-aside:', collections.Counter(r['set_aside'] for r in rows).most_common())
print('  AMSC:', collections.Counter(r['amsc'] for r in rows).most_common())
print('  return-by dates:', sorted({r['return_by'] for r in rows})[:3], '...', sorted({r['return_by'] for r in rows})[-2:])
print('  dup (solicitation, nsn, PR):', len(rows) - len({(r['solicitation'], r['nsn_part'], r['purchase_request']) for r in rows}))
print('  dup (solicitation, PR):', len(rows) - len({(r['solicitation'], r['purchase_request']) for r in rows}))
print('  solicitation 9th char:', collections.Counter(r['solicitation'][8:9] for r in rows).most_common())
fsc = collections.Counter(r['nsn_part'][:4] for r in nsn_rows)
print('  top FSCs:', fsc.most_common(12))
if ours:
    hit = [r for r in nsn_rows if r['nsn_part'][:4] in ours or r['nsn_part'][:2] in ours]
    print(f'  in our FSCs/groups: {len(hit)} lines ({len(hit) * 100 // max(len(nsn_rows), 1)}%)')
print('  sample:', {k: rows[0][k] for k in rows[0]})

bq = list(csv.reader(open(bq_path, encoding='latin-1', newline='')))
widths = collections.Counter(len(r) for r in bq)
print(f"\nBQ: {len(bq)} rows, columns per row: {widths.most_common(3)}")
filled = [sum(1 for v in r if v.strip()) for r in bq]
print('  non-empty columns per row: min', min(filled), 'max', max(filled))
print('  first row, non-empty (col#: value):', [(i + 1, v) for i, v in enumerate(bq[0]) if v.strip()][:30])

ap = list(csv.reader(open(as_path, encoding='latin-1', newline='')))
print(f"\nAS: {len(ap)} rows, columns: {collections.Counter(len(r) for r in ap).most_common(2)}")
print('  distinct NSNs:', len({r[0] for r in ap}), ' distinct CAGEs:', len({r[1] for r in ap}))
print('  sample:', ap[0])
in_nsns = {r['nsn_part'] for r in nsn_rows}
print('  RFQ NSNs with >=1 approved source:', len(in_nsns & {r[0] for r in ap}), 'of', len(in_nsns))
