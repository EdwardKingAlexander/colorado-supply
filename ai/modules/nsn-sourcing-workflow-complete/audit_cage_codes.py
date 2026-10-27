import csv, io, json, re, zipfile
from pathlib import Path

base = Path(__file__).parent
report = {}
for archive, member in [('CAGE', 'P_CAGE.CSV'), ('CAGE', 'V_CAGE_ADDRESS.CSV'), ('REFERENCE', 'V_FLIS_PART.CSV')]:
    bad_count = 0
    examples = []
    lengths = {}
    with zipfile.ZipFile(base / 'artifacts/2026-10' / (archive + '.zip')) as z:
        reader = csv.reader(io.TextIOWrapper(z.open(member), encoding='utf-8-sig', newline=''))
        header = next(reader)
        col = header.index('CAGE_CODE')
        for n, row in enumerate(reader, 1):
            code = row[col]
            lengths[len(code)] = lengths.get(len(code), 0) + 1
            if not re.fullmatch('[A-Z0-9]{5}', code):
                bad_count += 1
                if len(examples) < 20:
                    examples.append({'source_row': n, 'cage_code': code})
    report[member] = {'rows': n, 'lengths': lengths, 'invalid_count': bad_count, 'examples': examples}
    print(json.dumps({member: report[member]}), flush=True)
    (base / 'cage-code-audit.json').write_text(json.dumps(report, indent=2), encoding='utf-8')
