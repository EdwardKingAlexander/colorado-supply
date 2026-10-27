"""Assemble and verify attributable acceptance examples without app writes."""
import json
from pathlib import Path

root = Path(__file__).resolve().parent
folder = root / 'fixtures'
def read(name):
    return json.loads((folder / (name + '.CSV.json')).read_text(encoding='utf-8'))

items = {r['NIIN']: r for r in read('P_FLIS_NSN')}
cages = {r['CAGE_CODE']: r for r in read('P_CAGE')}
addresses = {r['CAGE_CODE']: r for r in read('V_CAGE_ADDRESS')}
references = read('V_FLIS_PART')
statuses = read('V_FLIS_STANDARDIZATION')
codes = {r['CODE']: r for r in read('P_HELP')}
chosen = {'000000012', '000000042', '000000045', '000000057', '000000058'}
chosen_refs = [r for r in references if r['NIIN'] in chosen]
chosen_cages = {r['CAGE_CODE'] for r in chosen_refs}
fixture = {
    'source': 'Official DLA PUB LOG release listed 2026-10-01; hashes in ../source-measurements.json',
    'source_record_convention': 'One-based data record, excluding header; not physical line number.',
    'items': [items[n] for n in sorted(chosen)],
    'references': chosen_refs,
    'companies': [cages[c] for c in sorted(chosen_cages)],
    'addresses': [addresses[c] for c in sorted(chosen_cages)],
    'standardization': [r for r in statuses if r['NIIN'] in chosen],
    'definitions': [codes[c] for c in ['RNCC', 'RNCC:3', 'RNCC:5', 'RNVC', 'RNVC:1', 'RNVC:2', 'RNVC:9', 'CAGE_STATUS:A', 'CAGE_STATUS:Y', 'CAGE_STATUS:H', 'NIIN_STAT_CD:4', 'NIIN_STAT_CD:5']],
    'synthetic_scenarios': [
        {'name': 'missing_company', 'recipe': 'Use real reference 000000042 / UK 60A890216 / 24039, but omit company and address 24039 from the test snapshot.', 'expected': 'Reference preserved, unresolved company shown; not an assertion that DLA lacks this company.'},
        {'name': 'no_match', 'input_type': 'nsn', 'input': '9999999999999', 'expected': 'Absent from this deliberately small fixture; not claimed absent from the complete public dataset.'},
        {'name': 'invalid_input', 'input_type': 'nsn', 'input': 'abc5940000000045', 'expected': 'Invalid format, not accepted by stripping letters.'},
    ],
}
assert items['000000045']['FSC'] + '000000045' == '5940000000045'
assert len({r['CAGE_CODE'] for r in chosen_refs if r['NIIN'] == '000000045'}) == 2
assert {'000000057', '000000058'} <= {r['NIIN'] for r in chosen_refs if r['PART_NUMBER'] == 'BHMA-A156.4'}
assert any(r['NIIN'] == '000000045' and r['RNVC'] == '9' for r in chosen_refs)
assert any(r['NIIN'] == '000000012' and r['NIIN_STAT_CD'] == '4' for r in fixture['standardization'])
assert all(r['NIIN'] in items and r['CAGE_CODE'] in cages for r in chosen_refs)
assert all(len(r['NIIN']) == 9 for r in fixture['items'])
assert all(len(r['CAGE_CODE']) == 5 for r in fixture['companies'])
(folder / 'acceptance-cases.json').write_text(json.dumps(fixture, indent=2), encoding='utf-8')
manifest = json.loads((root / 'source-measurements.json').read_text())
assert len(manifest['files']) == 9
assert all(r['malformed_rows'] == 0 for r in manifest['files'].values())
benchmark = json.loads((root / 'sample-benchmark.json').read_text())
projection = sum(r['bytes_per_row_with_raw_json_and_three_indexes'] * manifest['files'][r['file']]['rows'] for r in benchmark)
summary = {
    'files_fully_parsed': len(manifest['files']),
    'parsed_data_records': sum(r['rows'] for r in manifest['files'].values()),
    'archive_bytes': sum(r['bytes'] for r in manifest['archives'].values()),
    'uncompressed_bytes': sum(r['bytes'] for r in manifest['files'].values()),
    'core_sqlite_projection_bytes': round(projection),
    'projection_limitations': 'First 100k records per file; four core tables only. Excludes status rows, MySQL overhead, staging, retained versions, WAL/temp, backups and app data. Not a capacity guarantee.',
    'fixture_assertions_passed': 10,
}
(root / 'verification-summary.json').write_text(json.dumps(summary, indent=2), encoding='utf-8')
print(json.dumps(summary, indent=2))
