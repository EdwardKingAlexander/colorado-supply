<?php

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\Vendor;

function importProductCsv(string $csv): array
{
    $path = tempnam(sys_get_temp_dir(), 'products').'.csv';
    file_put_contents($path, $csv);

    try {
        $method = new ReflectionMethod(ProductResource::class, 'importProductsFromCsv');

        return $method->invoke(null, $path);
    } finally {
        @unlink($path);
    }
}

beforeEach(function () {
    $this->vendor = Vendor::factory()->create(['name' => 'Brighton-Best']);
});

it('imports government and mil-spec columns', function () {
    $result = importProductCsv(<<<'CSV'
vendor,sku,name,standard_type,mil_spec_number,nsn,cage_code,part_number,naics_code,upc,ean,unit_of_measure,minimum_order_quantity,is_hazmat,export_controlled,berry_compliant,taa_compliant,made_in_usa,gsa_approved,qpl_listed,material,finish,specifications,datasheet_url,msds_url,gsa_price,contract_price,search_keywords
Brighton-Best,BB-1,Hex Bolt,ms,MS90725-60,5305-00-269-3238,1ABC2,PN-77,332722,012345678905,4006381333931,BX,25,no,yes,yes,y,1,true,on,Steel,Zinc,Grade 5,https://example.test/ds.pdf,https://example.test/sds.pdf,"$1,200.50",9.75,"bolt, hex"
CSV);

    expect($result['errors'])->toBe([])
        ->and($result['created'])->toBe(1);

    $product = Product::query()->where('sku', 'BB-1')->sole();

    expect($product->standard_type)->toBe('MS')
        ->and($product->mil_spec_number)->toBe('MS90725-60')
        ->and($product->nsn)->toBe('5305002693238')
        ->and($product->cage_code)->toBe('1ABC2')
        ->and($product->part_number)->toBe('PN-77')
        ->and($product->naics_code)->toBe('332722')
        ->and($product->upc)->toBe('012345678905')
        ->and($product->ean)->toBe('4006381333931')
        ->and($product->unit_of_measure)->toBe('BX')
        ->and((int) $product->minimum_order_quantity)->toBe(25)
        ->and((bool) $product->is_hazmat)->toBeFalse()
        ->and((bool) $product->export_controlled)->toBeTrue()
        ->and((bool) $product->berry_compliant)->toBeTrue()
        ->and((bool) $product->taa_compliant)->toBeTrue()
        ->and((bool) $product->made_in_usa)->toBeTrue()
        ->and((bool) $product->gsa_approved)->toBeTrue()
        ->and((bool) $product->qpl_listed)->toBeTrue()
        ->and($product->material)->toBe('Steel')
        ->and($product->finish)->toBe('Zinc')
        ->and($product->specifications)->toBe('Grade 5')
        ->and($product->datasheet_url)->toBe('https://example.test/ds.pdf')
        ->and($product->msds_url)->toBe('https://example.test/sds.pdf')
        ->and((float) $product->gsa_price)->toBe(1200.5)
        ->and((float) $product->contract_price)->toBe(9.75)
        ->and($product->search_keywords)->toBe('bolt, hex');
});

it('does not require the new columns and applies database defaults', function () {
    $result = importProductCsv("vendor,sku,name\nBrighton-Best,BB-2,Washer\n");

    expect($result['errors'])->toBe([]);

    $product = Product::query()->where('sku', 'BB-2')->sole();

    expect($product->standard_type)->toBe('Commercial')
        ->and($product->unit_of_measure)->toBe('EA')
        ->and((int) $product->minimum_order_quantity)->toBe(1)
        ->and((bool) $product->berry_compliant)->toBeFalse()
        ->and($product->nsn)->toBeNull();
});

it('keeps existing values when the cells are blank on update', function () {
    importProductCsv("vendor,sku,name,nsn,berry_compliant,unit_of_measure\nBrighton-Best,BB-3,Nut,5310-00-123-4567,yes,PK\n");
    importProductCsv("vendor,sku,name,nsn,berry_compliant,unit_of_measure\nBrighton-Best,BB-3,Nut,,,\n");

    $product = Product::query()->where('sku', 'BB-3')->sole();

    expect($product->nsn)->toBe('5310001234567')
        ->and((bool) $product->berry_compliant)->toBeTrue()
        ->and($product->unit_of_measure)->toBe('PK');
});

it('rejects an unknown standard type or malformed NSN without stopping other rows', function () {
    $result = importProductCsv(<<<'CSV'
vendor,sku,name,standard_type,nsn
Brighton-Best,BB-4,Good,MIL-DTL,
Brighton-Best,BB-5,Bad Type,ISO,
Brighton-Best,BB-6,Bad NSN,,5305-00-12
CSV);

    expect($result['created'])->toBe(1)
        ->and($result['skipped'])->toBe(2)
        ->and($result['errors'][0])->toContain('Line 3')->toContain('standard_type')
        ->and($result['errors'][1])->toContain('Line 4')->toContain('NSN');
});
