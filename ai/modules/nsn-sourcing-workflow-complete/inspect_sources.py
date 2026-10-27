"""Read-only PUB LOG phase-1 probe; writes research evidence, never app data."""
import csv
import hashlib
import io
import json
from pathlib import Path
import shutil
import time
import zipfile

ROOT = Path(__file__).resolve().parent
OUT = ROOT / 'fixtures'
ARCHIVES = ROOT / 'artifacts' / '2026-10'
OUT.mkdir(exist_ok=True)
ARCHIVES.mkdir(parents=True, exist_ok=True)
SOURCES = {
    'IDENTIFICATION': Path('C:/Users/babyd/Downloads/1c0935b0-f439-403c-8fd4-bcb483fb0726.tmp'),
    'CAGE': Path('C:/Users/babyd/Downloads/3b5d18e7-7fb4-43b5-9114-e37b4c26afae.tmp'),
    'REFERENCE': Path('C:/Users/babyd/Downloads/78042e1d-5f39-4d09-9ad7-446bd5d8779c.tmp'),
}
BASE = 'https://www.dla.mil/Portals/104/Documents/InformationOperations/LogisticsInformationServices/FOIA/PUBLOG/'
manifest = {'observed_release_date': '2026-10-01', 'archives': {}, 'files': {}}
if (ROOT / 'source-measurements.json').exists():
    manifest = json.loads((ROOT / 'source-measurements.json').read_text(encoding='utf-8'))
samples = {}
selected_niins = set()
selected_cages = set()
reference_part_targets = set()
prior_reference = OUT / 'V_FLIS_PART.CSV.json'
if prior_reference.exists():
    for row in json.loads(prior_reference.read_text(encoding='utf-8')):
        selected_cages.add(row['CAGE_CODE'])
        if row['source_record'] <= 500:
            selected_niins.add(row['NIIN'])
            reference_part_targets.add(row['PART_NUMBER'])

for name, original in SOURCES.items():
    saved = ARCHIVES / (name + '.zip')
    if not saved.exists():
        shutil.copyfile(original, saved)
    with saved.open('rb') as stream:
        digest = hashlib.file_digest(stream, 'sha256').hexdigest()
    manifest['archives'][name] = {'url': BASE + name + '.zip', 'bytes': saved.stat().st_size, 'sha256': digest}

def inspect(archive, member, encoding='utf-8-sig'):
    if member in manifest['files']:
        print('Already verified: ' + member, flush=True)
        return
    started = time.monotonic()
    collected = []
    with zipfile.ZipFile(ARCHIVES / (archive + '.zip')) as zipped:
        info = zipped.getinfo(member)
        with zipped.open(member) as raw:
            prefix = raw.read(500)
        # Fail visibly rather than silently replacing undecodable characters.
        with zipped.open(member) as raw, io.TextIOWrapper(raw, encoding=encoding, newline='') as stream:
            reader = csv.DictReader(stream, strict=True)
            headers = reader.fieldnames
            count = 0
            malformed = 0
            for count, row in enumerate(reader, 1):
                if None in row or any(v is None for v in row.values()):
                    malformed += 1
                keep = count <= 4
                if member == 'V_FLIS_PART.CSV':
                    if count <= 500:
                        selected_niins.add(row['NIIN'])
                        reference_part_targets.add(row['PART_NUMBER'])
                    keep = row['NIIN'] in selected_niins or row['PART_NUMBER'] in reference_part_targets
                    if keep:
                        selected_cages.add(row['CAGE_CODE'])
                elif 'NIIN' in row:
                    keep |= row['NIIN'] in selected_niins
                elif 'CAGE_CODE' in row:
                    keep |= row['CAGE_CODE'] in selected_cages
                elif member == 'P_HELP.CSV':
                    keep = row['CODE'].startswith(('RNCC', 'RNVC', 'CAGE', 'NIIN_STAT', 'DAC', 'RNFC', 'RNSC', 'RNJC', 'RNAAC', 'SADC'))
                if keep:
                    collected.append({'source_record': count, **row})
        stats = {'rows': count, 'headers': headers, 'bytes': info.file_size,
                 'compressed_bytes': info.compress_size, 'zip_timestamp': list(info.date_time),
                 'decoding_used': encoding,
                 'encoding_note': 'Strict full-file decoding; ASCII-only bytes do not establish original encoding.',
                 'prefix_hex': prefix[:12].hex(), 'crlf_in_prefix': b'\r\n' in prefix,
                 'malformed_rows': malformed, 'seconds': round(time.monotonic()-started, 2),
                 'sample_rows': len(collected)}
        manifest['files'][member] = stats
        samples[member] = collected
        (OUT / (member + '.json')).write_text(json.dumps(collected, indent=2), encoding='utf-8')
        (ROOT / 'source-measurements.json').write_text(json.dumps(manifest, indent=2), encoding='utf-8')
        print(json.dumps({'file': member, **stats}), flush=True)

inspect('REFERENCE', 'V_FLIS_PART.CSV')
for source in ('IDENTIFICATION', 'CAGE'):
    with zipfile.ZipFile(ARCHIVES / (source + '.zip')) as zipped:
        members = zipped.namelist()
    for member in members:
        try:
            inspect(source, member)
        except UnicodeDecodeError as exc:
            print(member + ': UTF-8 failed; testing strict Windows-1252: ' + str(exc), flush=True)
            inspect(source, member, 'cp1252')

print('All archive streams read through EOF and CRC checked by zipfile.', flush=True)
