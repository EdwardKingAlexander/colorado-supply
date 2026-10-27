<?php

use App\Models\DibbsApprovedSource;
use App\Models\DibbsFile;
use App\Models\DibbsQuoteTemplate;
use App\Models\DibbsRfq;
use App\Services\Dibbs\BatchQuoteFileParser;
use App\Services\Dibbs\DibbsFileImporter;
use App\Services\Dibbs\DibbsLayoutException;
use App\Services\Dibbs\InFileParser;
use Illuminate\Support\Facades\Storage;

/*
| Fixtures: tests/Fixtures/dibbs/{in,bq,as}261002.txt are real DLA files from
| 2026-10-02, trimmed to 50 RFQ lines (ai/modules/dibbs-rfq-tracking/make_fixtures.py).
*/

beforeEach(function () {
    Storage::fake('local');
});

describe('index parser', function () {
    test('parses every fixture line with the documented 140-character layout', function () {
        ['rows' => $rows, 'errors' => $errors] = (new InFileParser)->parse(dibbsFixture('in261002.txt'));

        expect($rows)->toHaveCount(50)
            ->and($errors)->toBe([])
            ->and($rows[0])->toMatchArray([
                'solicitation_number' => 'SPE1C126T1775',
                'nsn' => '8465015148739',
                'fsc' => '8465',
                'niin' => '015148739',
                'purchase_request' => '7018426027',
                'return_by_date' => '2026-10-07',
                'quantity' => 25,
                'unit_of_issue' => 'EA',
                'nomenclature' => 'RESTRAINT,INVOLUNTA',
                'amsc' => 'Z',
                'item_type' => '1',
                'set_aside' => 'Y',
                'set_aside_percent' => 100,
                'automated_award' => true,
            ]);
    });

    test('reports a bad row without dropping the rest of the file', function () {
        $lines = explode("\r\n", trim(dibbsFixture('in261002.txt')));
        $lines[1] = substr_replace($lines[1], 'X465015148739', 13, 13); // letter in the FSC

        ['rows' => $rows, 'errors' => $errors] = (new InFileParser)->parse(implode("\r\n", $lines));

        expect($rows)->toHaveCount(49)
            ->and($errors)->toHaveCount(1)
            ->and($errors[0])->toContain('Line 2')->toContain('4-digit FSC');
    });

    test('accepts DLA local stock numbers with letters in the NIIN', function () {
        // Real examples from 2026-09-25..10-02 files: 5330LN0035636, 9535LLNCA9756.
        $line = substr_replace(explode('
', dibbsFixture('in261002.txt'))[0], '5330LN0035636', 13, 13);

        ['rows' => $rows, 'errors' => $errors] = (new InFileParser)->parse($line);

        expect($errors)->toBe([])
            ->and($rows[0])->toMatchArray(['nsn' => '5330LN0035636', 'fsc' => '5330', 'niin' => 'LN0035636']);
    });

    test('rejects a file whose record length no longer matches the layout', function () {
        $changed = implode("\r\n", array_map(fn ($l) => $l.'XXXX', explode("\r\n", trim(dibbsFixture('in261002.txt')))));

        (new InFileParser)->parse($changed);
    })->throws(DibbsLayoutException::class, 'layout mismatch');

    test('slices by byte before decoding, so a Latin-1 character does not shift fields', function () {
        $line = explode("\r\n", dibbsFixture('in261002.txt'))[0];
        $line = substr_replace($line, "\xC9", 108, 1); // É in the nomenclature, as DLA's ISO-8859-1 would encode it

        ['rows' => $rows] = (new InFileParser)->parse($line);

        expect($rows[0]['nomenclature'])->toStartWith('É')
            ->and($rows[0]['buyer_code'])->toBe('PCPQD')
            ->and($rows[0]['amsc'])->toBe('Z');
    });
});

describe('batch quote and approved source parser', function () {
    test('keeps DLA rows verbatim and numbers repeated keys', function () {
        ['rows' => $rows, 'errors' => $errors] = (new BatchQuoteFileParser)->parseBatchQuotes(dibbsFixture('bq261002.txt'));

        expect($rows)->toHaveCount(52)
            ->and($errors)->toBe([])
            ->and($rows[0])->toMatchArray(['solicitation_number' => 'SPE1C126T1775', 'purchase_request' => '7018426027', 'line_number' => '0001', 'quantity' => 25, 'delivery_days' => 20, 'row_index' => 0])
            ->and(BatchQuoteFileParser::splitRow($rows[0]['raw_row']))->toHaveCount(121)
            ->and(collect($rows)->max('row_index'))->toBeGreaterThan(0);
    });

    test('parses approved sources', function () {
        ['rows' => $rows] = (new BatchQuoteFileParser)->parseApprovedSources(dibbsFixture('as261002.txt'));

        expect($rows)->toHaveCount(70)
            ->and(array_keys($rows[0]))->toBe(['nsn', 'cage_code', 'part_number', 'company_name'])
            ->and(strlen($rows[0]['cage_code']))->toBe(5);
    });

    test('handles unescaped quotes inside values, as DLA writes them', function () {
        // Real as261001.txt line: an inch mark in the part number is not escaped.
        $line = '"4820016591587","79227","RK 800M4-T 1-1/2"",""';

        ['rows' => $rows, 'errors' => $errors] = (new BatchQuoteFileParser)->parseApprovedSources($line);

        expect($errors)->toBe([])
            ->and($rows[0])->toBe(['nsn' => '4820016591587', 'cage_code' => '79227', 'part_number' => 'RK 800M4-T 1-1/2"', 'company_name' => null]);
    });
});

describe('importer', function () {
    test('imports the index and batch quote zip, linking quote rows to RFQs', function () {
        $importer = new DibbsFileImporter;

        $in = $importer->import($importer->register(dibbsFixture('in261002.txt'), 'in261002.txt', 'upload'));
        $bq = $importer->import($importer->register(dibbsBqZip(), 'bq261002.zip', 'upload'));

        expect($in->status)->toBe(DibbsFile::STATUS_IMPORTED)
            ->and($in->row_count)->toBe(50)
            ->and($in->posting_date->toDateString())->toBe('2026-10-02')
            ->and($bq->status)->toBe(DibbsFile::STATUS_IMPORTED)
            ->and(DibbsRfq::count())->toBe(50)
            ->and(DibbsQuoteTemplate::count())->toBe(52)
            ->and(DibbsQuoteTemplate::whereNull('dibbs_rfq_id')->count())->toBe(0)
            ->and(DibbsApprovedSource::count())->toBeGreaterThan(0)
            ->and(DibbsRfq::first()->approvedSources)->not->toBeEmpty();

        Storage::disk('local')->assertExists($in->stored_path);
    });

    test('links quote rows even when the zip arrives before the index', function () {
        $importer = new DibbsFileImporter;

        $importer->import($importer->register(dibbsBqZip(), 'bq261002.zip', 'automated'));
        expect(DibbsQuoteTemplate::whereNotNull('dibbs_rfq_id')->count())->toBe(0);

        $importer->import($importer->register(dibbsFixture('in261002.txt'), 'in261002.txt', 'automated'));
        expect(DibbsQuoteTemplate::whereNull('dibbs_rfq_id')->count())->toBe(0);
    });

    test('is idempotent: the same content registers once and imports once', function () {
        $importer = new DibbsFileImporter;

        $first = $importer->import($importer->register(dibbsFixture('in261002.txt'), 'in261002.txt', 'automated'));
        $again = $importer->import($importer->register(dibbsFixture('in261002.txt'), 'in261002.txt', 'upload'));

        expect($again->id)->toBe($first->id)
            ->and(DibbsFile::count())->toBe(1)
            ->and(DibbsRfq::count())->toBe(50);
    });

    test('a reissued RFQ updates in place and keeps its first-seen file', function () {
        $importer = new DibbsFileImporter;
        $day1 = $importer->import($importer->register(dibbsFixture('in261002.txt'), 'in261002.txt', 'automated'));

        // Next day's file repeats the first line with a new quantity (as DLA reissues do).
        $line = explode("\r\n", dibbsFixture('in261002.txt'))[0];
        $reissue = substr_replace($line, '0000040', 99, 7);
        $day2 = $importer->import($importer->register($reissue."\r\n", 'in261003.txt', 'automated'));

        $rfq = DibbsRfq::where('solicitation_number', 'SPE1C126T1775')->where('purchase_request', '7018426027')->sole();

        expect(DibbsRfq::count())->toBe(50)
            ->and($rfq->quantity)->toBe(40)
            ->and($rfq->first_seen_file_id)->toBe($day1->id)
            ->and($rfq->last_seen_file_id)->toBe($day2->id);
    });

    test('records a layout failure on the file instead of throwing', function () {
        $importer = new DibbsFileImporter;
        $bad = implode("\r\n", array_map(fn ($l) => $l.'XXXX', explode("\r\n", trim(dibbsFixture('in261002.txt')))));

        $file = $importer->import($importer->register($bad, 'in261002.txt', 'upload'));

        expect($file->status)->toBe(DibbsFile::STATUS_FAILED)
            ->and($file->error)->toContain('layout mismatch')
            ->and(DibbsRfq::count())->toBe(0);
    });

    test('rejects names that are not DIBBS daily files', function (string $name) {
        (new DibbsFileImporter)->register('x', $name, 'upload');
    })->with(['rfqs.txt', 'in261002.zip', 'bq261002.txt', 'ca261002.zip'])->throws(InvalidArgumentException::class);
});
