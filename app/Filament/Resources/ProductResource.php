<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProductResource\RelationManagers\AttributesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\RelatedProductsRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Vendor;
use App\Services\Products\SampleProductCsvGenerator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')->columns(3)->schema([
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, ?int $state) {
                        static::synchronizeProductSlug($set, $get);
                    }),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255),

                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(100)
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                        static::synchronizeProductSlug($set, $get);
                    }),

                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->nullable(),
            ]),

            Section::make('Commerce')->columns(4)->schema([
                TextInput::make('price')->numeric()->prefix('$'),
                TextInput::make('list_price')->numeric()->prefix('$'),
                TextInput::make('cost')->numeric()->prefix('$'),
                TextInput::make('stock')->numeric()->minValue(0),
                Toggle::make('is_active')->default(true),
            ]),

            Section::make('Details')->columns(2)->schema([
                FileUpload::make('image')->image()->directory('products')->imageEditor(),
                Textarea::make('description')->rows(6),
            ]),

            // Everything below is optional: the storefront hides any section
            // whose data is missing (ai/modules/product-detail-page-complete).
            Section::make('Ordering')->columns(4)->collapsed()->schema([
                TextInput::make('unit_of_measure')
                    ->label('Unit of measure')
                    ->placeholder('EA')
                    ->maxLength(20)
                    ->helperText('EA, BX, PK, FT… Blank = EA.')
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? strtoupper(trim($state)) : 'EA'),
                TextInput::make('minimum_order_quantity')
                    ->label('Minimum order qty')
                    ->integer()
                    ->minValue(1)
                    ->placeholder('1')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) $state : 1),
                TextInput::make('reorder_point')->integer()->minValue(0),
                TextInput::make('lead_time_days')->label('Lead time (days)')->integer()->minValue(0),
            ]),

            Section::make('Quantity pricing')->collapsed()
                ->description('Optional price breaks. Buying the minimum quantity or more uses that unit price.')
                ->schema([
                    Repeater::make('priceTiers')
                        ->hiddenLabel()
                        ->relationship()
                        ->schema([
                            TextInput::make('min_quantity')
                                ->label('Minimum quantity')
                                ->integer()
                                ->minValue(1)
                                ->required()
                                ->distinct(),
                            TextInput::make('unit_price')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Add price break'),
                ]),

            Section::make('Gallery images')->collapsed()
                ->description('Optional extra images. The Details image stays the primary image.')
                ->schema([
                    Repeater::make('images')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->reorderable()
                        ->schema([
                            FileUpload::make('path')
                                ->label('Image')
                                ->image()
                                ->directory('products/gallery')
                                ->required(),
                            TextInput::make('alt')
                                ->label('Alt text')
                                ->maxLength(255)
                                ->helperText('Describes the image for screen readers. Blank = product name.'),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Add image'),
                ]),

            Section::make('Identifiers')->columns(3)->collapsed()->schema([
                TextInput::make('mpn')->label('MPN')->maxLength(255),
                TextInput::make('part_number')->maxLength(255),
                TextInput::make('gtin')->label('GTIN')->maxLength(255),
                TextInput::make('upc')->label('UPC')->maxLength(255),
                TextInput::make('ean')->label('EAN')->maxLength(255),
            ]),

            Section::make('Government')->columns(3)->collapsed()->schema([
                Select::make('standard_type')
                    ->options(array_combine(static::STANDARD_TYPES, static::STANDARD_TYPES))
                    ->default('Commercial')
                    ->selectablePlaceholder(false),
                TextInput::make('mil_spec_number')->label('Mil-spec number')->maxLength(255),
                TextInput::make('nsn')
                    ->label('NSN')
                    ->placeholder('5305-00-269-3238')
                    ->regex('/^\d{4}-?\d{2}-?\d{3}-?\d{4}$/')
                    ->validationMessages(['regex' => 'An NSN has 13 digits, e.g. 5305-00-269-3238.'])
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? preg_replace('/\D/', '', $state) : null),
                TextInput::make('cage_code')->label('CAGE code')->maxLength(5),
                TextInput::make('naics_code')->label('NAICS code')->maxLength(10),
                TextInput::make('unspsc')->label('UNSPSC')->maxLength(255),
                TextInput::make('psc_fsc')->label('PSC / FSC')->maxLength(255),
                TextInput::make('gsa_price')->label('GSA price')->numeric()->minValue(0)->prefix('$')
                    ->helperText('Shown only to government-buyer companies.'),
                TextInput::make('contract_price')->numeric()->minValue(0)->prefix('$')
                    ->helperText('Shown only to government-buyer companies.'),
            ]),

            Section::make('Compliance')->columns(4)->collapsed()->schema([
                Toggle::make('berry_compliant')->label('Berry compliant'),
                Toggle::make('taa_compliant')->label('TAA compliant'),
                Toggle::make('made_in_usa')->label('Made in USA'),
                Toggle::make('gsa_approved')->label('GSA approved'),
                Toggle::make('qpl_listed')->label('QPL listed'),
                Toggle::make('is_hazmat')->label('Hazmat'),
                Toggle::make('export_controlled')->label('Export controlled'),
                TextInput::make('country_of_origin')->maxLength(255),
            ]),

            Section::make('Specifications & documents')->columns(2)->collapsed()->schema([
                TextInput::make('material')->maxLength(255),
                TextInput::make('finish')->maxLength(255),
                Textarea::make('specifications')->rows(4)->columnSpanFull(),
                Textarea::make('search_keywords')->rows(2)->columnSpanFull()
                    ->helperText('Extra terms buyers might search for.'),
                TextInput::make('datasheet_url')->label('Datasheet URL')->url()->maxLength(255),
                TextInput::make('msds_url')->label('SDS / MSDS URL')->url()->maxLength(255),
                TextInput::make('weight_g')->label('Weight (g)')->integer()->minValue(0),
                TextInput::make('length_mm')->label('Length (mm)')->integer()->minValue(0),
                TextInput::make('width_mm')->label('Width (mm)')->integer()->minValue(0),
                TextInput::make('height_mm')->label('Height (mm)')->integer()->minValue(0),
            ]),
        ]);
    }

    protected static function synchronizeProductSlug(Set $set, Get $get): void
    {
        $vendorId = $get('vendor_id');
        $sku = $get('sku');

        if (blank($vendorId) || blank($sku)) {
            $set('slug', null);

            return;
        }

        $vendorName = Vendor::query()->find($vendorId)?->name;

        if (blank($vendorName)) {
            $set('slug', null);

            return;
        }

        $set('slug', Str::slug($vendorName.'-'.$sku));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['vendor', 'category']))
            ->headerActions([
                CreateAction::make(),
                Action::make('downloadSampleProducts')
                    ->label('Sample CSV (1,000)')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->tooltip('1,000 sample products across existing vendors and categories, ready for Import Products. Re-importing updates them instead of duplicating.')
                    ->action(function () {
                        try {
                            $csv = app(SampleProductCsvGenerator::class)->generate(1000);
                        } catch (RuntimeException $exception) {
                            Notification::make()
                                ->title('Cannot build sample CSV')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            return null;
                        }

                        return response()->streamDownload(
                            fn () => print ($csv),
                            'sample-products-1000.csv',
                            ['Content-Type' => 'text/csv'],
                        );
                    }),
                Action::make('importProducts')
                    ->label('Import Products')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->color('primary')
                    ->modalHeading('Import Products from CSV')
                    ->modalDescription('Upload a CSV containing product columns (e.g. vendor, sku, name, price) and optional attribute columns prefixed with attribute:.')
                    ->schema([
                        FileUpload::make('file')
                            ->label('CSV File')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->disk('local')
                            ->directory('imports/products')
                            ->visibility('private')
                            ->preserveFilenames()
                            ->enableDownload(false)
                            ->enableOpen(false)
                            ->maxSize(5120)
                            ->helperText('Single CSV file. Attribute columns should be prefixed with attribute:, e.g. attribute:Color.')
                            ->required(),
                    ])
                    ->action(fn (array $data) => static::handleImportAction($data['file'] ?? null))
                    ->modalWidth('lg'),
            ])
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sku')->label('SKU')->searchable()->toggleable(),
                TextColumn::make('vendor.name')->label('Vendor')->sortable()->toggleable(),
                TextColumn::make('category.name')->label('Category')->sortable()->toggleable(),
                TextColumn::make('price')->money('usd')->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active')->sortable(),
                TextColumn::make('updated_at')->dateTime('M j, Y')->label('Updated')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AttributesRelationManager::class,
            RelatedProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    protected static function handleImportAction(string|array|null $uploadedPath): void
    {
        if (is_array($uploadedPath)) {
            $uploadedPath = $uploadedPath[0] ?? null;
        }

        if (! $uploadedPath) {
            Notification::make()
                ->title('Products import failed')
                ->body('No file was uploaded.')
                ->danger()
                ->send();

            return;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($uploadedPath)) {
            Notification::make()
                ->title('Products import failed')
                ->body('Uploaded file could not be found.')
                ->danger()
                ->send();

            return;
        }

        try {
            $result = static::importProductsFromCsv($disk->path($uploadedPath));

            Notification::make()
                ->title('Products import complete')
                ->body(static::formatImportSummary($result))
                ->success()
                ->send();

            if (! empty($result['errors'])) {
                Notification::make()
                    ->title('Import completed with issues')
                    ->body(static::formatImportErrors($result['errors']))
                    ->warning()
                    ->send();
            }
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Products import failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            $disk->delete($uploadedPath);
        }
    }

    protected static function importProductsFromCsv(string $absolutePath): array
    {
        if (! is_readable($absolutePath)) {
            throw new RuntimeException('CSV file is not readable.');
        }

        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV file.');
        }

        try {
            $headerRow = fgetcsv($handle);

            if (! $headerRow) {
                throw new RuntimeException('CSV file does not contain a header row.');
            }

            $columns = static::buildColumnDefinitions($headerRow);
            $stats = [
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'attributes' => 0,
                'skipped' => 0,
                'errors' => [],
            ];

            $lineNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                if (static::rowIsEmpty($row)) {
                    continue;
                }

                $parsed = static::extractRowData($row, $columns);

                try {
                    $result = DB::transaction(function () use ($parsed) {
                        return static::importSingleProduct($parsed['fields'], $parsed['attributes']);
                    });

                    $stats[$result['status']]++;
                    $stats['attributes'] += $result['attributes'];
                } catch (Throwable $rowException) {
                    $stats['skipped']++;
                    $stats['errors'][] = 'Line '.$lineNumber.': '.$rowException->getMessage();
                }
            }

            return $stats;
        } finally {
            fclose($handle);
        }
    }

    protected static function buildColumnDefinitions(array $headers): array
    {
        $columns = [];

        foreach ($headers as $index => $header) {
            $trimmed = static::stripBom((string) $header);
            $trimmed = trim($trimmed);

            if ($trimmed === '') {
                throw new RuntimeException('CSV header contains an empty column name.');
            }

            $lower = strtolower($trimmed);
            $isAttribute = str_starts_with($lower, 'attribute:') || str_starts_with($lower, 'attr:');
            $attributeName = null;

            if ($isAttribute) {
                $attributeName = trim(substr($trimmed, strpos($trimmed, ':') + 1));

                if ($attributeName === '') {
                    throw new RuntimeException('Attribute column is missing a name.');
                }
            }

            $columns[] = [
                'index' => $index,
                'original' => $trimmed,
                'normalized' => static::normalizeHeaderKey($trimmed),
                'is_attribute' => $isAttribute,
                'attribute_name' => $attributeName,
            ];
        }

        return $columns;
    }

    protected static function extractRowData(array $row, array $columns): array
    {
        $fields = [];
        $attributes = [];

        foreach ($columns as $column) {
            $value = array_key_exists($column['index'], $row) ? $row[$column['index']] : null;
            $value = is_string($value) ? trim($value) : $value;

            if ($column['is_attribute']) {
                if ($value !== null && $value !== '') {
                    $attributes[$column['attribute_name']] = $value;
                }

                continue;
            }

            $fields[$column['normalized']] = $value;
        }

        return [
            'fields' => $fields,
            'attributes' => $attributes,
        ];
    }

    protected static function importSingleProduct(array $fields, array $attributes): array
    {
        $vendorId = static::resolveVendorId($fields);
        $sku = static::nullIfBlank($fields['sku'] ?? null);
        $name = static::nullIfBlank($fields['name'] ?? null);

        if (! $sku || ! $name) {
            throw new RuntimeException('Columns sku and name are required.');
        }

        $categoryId = static::resolveCategoryId($fields);
        $slug = static::nullIfBlank($fields['slug'] ?? null) ?: Str::slug($name);

        $productData = [
            'vendor_id' => $vendorId,
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'mpn' => static::nullIfBlank($fields['mpn'] ?? null),
            'gtin' => static::nullIfBlank($fields['gtin'] ?? null),
            'description' => static::nullIfBlank($fields['description'] ?? null),
            'image' => static::nullIfBlank($fields['image'] ?? null),
            'price' => static::parseDecimal($fields['price'] ?? null),
            'list_price' => static::parseDecimal($fields['list_price'] ?? null),
            'cost' => static::parseDecimal($fields['cost'] ?? null),
            'stock' => static::parseInteger($fields['stock'] ?? null),
            'reorder_point' => static::parseInteger($fields['reorder_point'] ?? null),
            'lead_time_days' => static::parseInteger($fields['lead_time_days'] ?? null),
            'is_active' => static::parseBoolean($fields['is_active'] ?? null, default: true),
            'weight_g' => static::parseInteger($fields['weight_g'] ?? null),
            'length_mm' => static::parseInteger($fields['length_mm'] ?? null),
            'width_mm' => static::parseInteger($fields['width_mm'] ?? null),
            'height_mm' => static::parseInteger($fields['height_mm'] ?? null),
            'unspsc' => static::nullIfBlank($fields['unspsc'] ?? null),
            'psc_fsc' => static::nullIfBlank($fields['psc_fsc'] ?? null),
            'country_of_origin' => static::nullIfBlank($fields['country_of_origin'] ?? null),
            'meta' => static::parseJson($fields['meta'] ?? null),
            'standard_type' => static::parseStandardType($fields['standard_type'] ?? null),
            'mil_spec_number' => static::nullIfBlank($fields['mil_spec_number'] ?? null),
            'nsn' => static::parseNsn($fields['nsn'] ?? null),
            'cage_code' => static::nullIfBlank($fields['cage_code'] ?? null),
            'part_number' => static::nullIfBlank($fields['part_number'] ?? null),
            'naics_code' => static::nullIfBlank($fields['naics_code'] ?? null),
            'upc' => static::nullIfBlank($fields['upc'] ?? null),
            'ean' => static::nullIfBlank($fields['ean'] ?? null),
            'unit_of_measure' => static::nullIfBlank($fields['unit_of_measure'] ?? null),
            'minimum_order_quantity' => static::parseInteger($fields['minimum_order_quantity'] ?? null),
            'is_hazmat' => static::parseBoolean($fields['is_hazmat'] ?? null),
            'export_controlled' => static::parseBoolean($fields['export_controlled'] ?? null),
            'berry_compliant' => static::parseBoolean($fields['berry_compliant'] ?? null),
            'taa_compliant' => static::parseBoolean($fields['taa_compliant'] ?? null),
            'made_in_usa' => static::parseBoolean($fields['made_in_usa'] ?? null),
            'gsa_approved' => static::parseBoolean($fields['gsa_approved'] ?? null),
            'qpl_listed' => static::parseBoolean($fields['qpl_listed'] ?? null),
            'material' => static::nullIfBlank($fields['material'] ?? null),
            'finish' => static::nullIfBlank($fields['finish'] ?? null),
            'specifications' => static::nullIfBlank($fields['specifications'] ?? null),
            'datasheet_url' => static::nullIfBlank($fields['datasheet_url'] ?? null),
            'msds_url' => static::nullIfBlank($fields['msds_url'] ?? null),
            'gsa_price' => static::parseDecimal($fields['gsa_price'] ?? null),
            'contract_price' => static::parseDecimal($fields['contract_price'] ?? null),
            'search_keywords' => static::nullIfBlank($fields['search_keywords'] ?? null),
        ];

        $payload = array_filter(
            $productData,
            fn ($value) => $value !== null
        );

        $product = Product::query()->updateOrCreate(
            [
                'vendor_id' => $vendorId,
                'sku' => $sku,
            ],
            $payload
        );

        $status = $product->wasRecentlyCreated ? 'created' : ($product->wasChanged() ? 'updated' : 'unchanged');
        $attributeCount = static::syncAttributes($product, $attributes);

        return [
            'status' => $status,
            'attributes' => $attributeCount,
        ];
    }

    protected static function resolveVendorId(array $fields): int
    {
        $vendorId = static::nullIfBlank($fields['vendor_id'] ?? null);

        if ($vendorId) {
            $vendor = Vendor::query()->find($vendorId);

            if (! $vendor) {
                throw new RuntimeException('Vendor with ID '.$vendorId.' was not found.');
            }

            return $vendor->id;
        }

        $vendorName = static::nullIfBlank($fields['vendor'] ?? $fields['vendor_name'] ?? null);

        if (! $vendorName) {
            throw new RuntimeException('Column vendor or vendor_id is required.');
        }

        $vendor = Vendor::query()->where('name', $vendorName)->first();

        if (! $vendor) {
            throw new RuntimeException('Vendor named '.$vendorName.' was not found.');
        }

        return $vendor->id;
    }

    protected static function resolveCategoryId(array $fields): ?int
    {
        $categoryId = static::nullIfBlank($fields['category_id'] ?? null);

        if ($categoryId) {
            $category = Category::query()->find($categoryId);

            if (! $category) {
                throw new RuntimeException('Category with ID '.$categoryId.' was not found.');
            }

            return $category->id;
        }

        $categoryName = static::nullIfBlank($fields['category'] ?? $fields['category_name'] ?? null);

        if (! $categoryName) {
            return null;
        }

        $category = Category::query()->where('name', $categoryName)->first();

        if (! $category) {
            throw new RuntimeException('Category named '.$categoryName.' was not found.');
        }

        return $category->id;
    }

    protected static function syncAttributes(Product $product, array $attributes): int
    {
        $count = 0;

        foreach ($attributes as $name => $value) {
            $cleanName = trim((string) $name);
            $cleanValue = trim((string) $value);

            if ($cleanName === '' || $cleanValue === '') {
                continue;
            }

            $type = static::inferAttributeType($cleanValue);
            $storedValue = static::coerceAttributeValue($cleanValue, $type);

            ProductAttribute::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'name' => $cleanName,
                ],
                [
                    'type' => $type,
                    'value' => $storedValue,
                ]
            );

            $count++;
        }

        return $count;
    }

    protected static function inferAttributeType(string $value): string
    {
        $lower = strtolower($value);

        if (in_array($lower, ['true', 'false', 'yes', 'no', '1', '0', 'y', 'n'], true)) {
            return 'boolean';
        }

        $normalized = str_replace([',', ' '], '', $value);

        if (is_numeric($normalized)) {
            return str_contains($normalized, '.') ? 'float' : 'integer';
        }

        return 'string';
    }

    protected static function coerceAttributeValue(string $value, string $type): string
    {
        try {
            return match ($type) {
                'boolean' => static::parseBoolean($value) ? '1' : '0',
                'integer' => (string) static::parseInteger($value),
                'float' => (string) static::parseDecimal($value),
                default => $value,
            };
        } catch (Throwable $exception) {
            return $value;
        }
    }

    protected static function normalizeHeaderKey(string $header): string
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $header));

        return trim($normalized, '_');
    }

    protected static function stripBom(string $value): string
    {
        return str_starts_with($value, "\u{FEFF}") ? substr($value, 3) : $value;
    }

    protected static function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected static function nullIfBlank(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }

    protected static function parseBoolean(mixed $value, ?bool $default = null): ?bool
    {
        $value = static::nullIfBlank($value);

        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $lower = strtolower((string) $value);

        return match (true) {
            in_array($lower, ['1', 'true', 't', 'yes', 'y', 'on'], true) => true,
            in_array($lower, ['0', 'false', 'f', 'no', 'n', 'off'], true) => false,
            default => throw new RuntimeException('Invalid boolean value ['.$value.'].'),
        };
    }

    /**
     * Allowed values mirror the products.standard_type enum column.
     */
    protected const STANDARD_TYPES = ['Commercial', 'AN', 'MS', 'NAS', 'MIL-DTL', 'MIL-PRF', 'MIL-C', 'MIL-R', 'MIL-T', 'MIL-W', 'Other'];

    protected static function parseStandardType(mixed $value): ?string
    {
        $value = static::nullIfBlank($value);

        if ($value === null) {
            return null;
        }

        foreach (static::STANDARD_TYPES as $type) {
            if (strcasecmp($type, (string) $value) === 0) {
                return $type;
            }
        }

        throw new RuntimeException('Invalid standard_type ['.$value.']. Allowed: '.implode(', ', static::STANDARD_TYPES).'.');
    }

    /**
     * Accepts dashed (5305-00-269-3238) or plain NSNs; stores the 13 digits.
     */
    protected static function parseNsn(mixed $value): ?string
    {
        $value = static::nullIfBlank($value);

        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) !== 13) {
            throw new RuntimeException('Invalid NSN ['.$value.']. An NSN must have 13 digits.');
        }

        return $digits;
    }

    protected static function parseInteger(mixed $value): ?int
    {
        $value = static::nullIfBlank($value);

        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            throw new RuntimeException('Invalid integer value ['.$value.'].');
        }

        return (int) $value;
    }

    protected static function parseDecimal(mixed $value): ?float
    {
        $value = static::nullIfBlank($value);

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(['$', ',', ' '], '', $value);
        }

        if (! is_numeric($value)) {
            throw new RuntimeException('Invalid decimal value ['.$value.'].');
        }

        return (float) $value;
    }

    protected static function parseJson(mixed $value): ?string
    {
        $value = static::nullIfBlank($value);

        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $encoded = json_encode($value);

            if ($encoded === false) {
                throw new RuntimeException('Meta column must contain valid JSON.');
            }

            return $encoded;
        }

        json_decode((string) $value);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Meta column must contain valid JSON.');
        }

        return (string) $value;
    }

    protected static function formatImportSummary(array $result): string
    {
        $parts = [
            'Created: '.$result['created'],
            'Updated: '.$result['updated'],
            'Unchanged: '.$result['unchanged'],
            'Skipped: '.$result['skipped'],
            'Attributes processed: '.$result['attributes'],
        ];

        return implode(' | ', $parts);
    }

    protected static function formatImportErrors(array $errors): string
    {
        $preview = array_slice($errors, 0, 5);
        $message = implode(PHP_EOL, $preview);

        if (count($errors) > 5) {
            $message .= PHP_EOL.'...';
        }

        return $message;
    }
}
