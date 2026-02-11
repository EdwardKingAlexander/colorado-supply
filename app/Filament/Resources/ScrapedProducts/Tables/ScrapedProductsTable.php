<?php

namespace App\Filament\Resources\ScrapedProducts\Tables;

use App\Models\Product;
use App\Models\ScrapedProduct;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Response;

class ScrapedProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->limit(50)
                    ->tooltip(fn (ScrapedProduct $record): string => $record->title ?? 'No title'),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('SKU copied')
                    ->placeholder('--'),

                TextColumn::make('nsn')
                    ->label('NSN')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('NSN copied')
                    ->badge()
                    ->color('success')
                    ->placeholder('--')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cage_code')
                    ->label('CAGE Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('CAGE code copied')
                    ->badge()
                    ->color('warning')
                    ->placeholder('--')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('milspec')
                    ->label('Mil-Spec')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Mil-spec copied')
                    ->badge()
                    ->color('primary')
                    ->placeholder('--')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('price_numeric')
                    ->label('Price')
                    ->money('USD')
                    ->sortable()
                    ->placeholder('--'),

                BadgeColumn::make('status')
                    ->searchable()
                    ->sortable()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'imported',
                        'danger' => 'failed',
                        'gray' => 'ignored',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'imported',
                        'heroicon-o-x-circle' => 'failed',
                        'heroicon-o-minus-circle' => 'ignored',
                    ]),

                TextColumn::make('vendor_domain')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('source_url')
                    ->label('Source URL')
                    ->searchable()
                    ->limit(40)
                    ->url(fn (ScrapedProduct $record): string => $record->source_url)
                    ->openUrlInNewTab()
                    ->tooltip(fn (ScrapedProduct $record): string => $record->source_url)
                    ->toggleable(),

                TextColumn::make('product.name')
                    ->label('Linked Product')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not imported')
                    ->toggleable(),

                TextColumn::make('importer.name')
                    ->label('Imported By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('imported_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Scraped At')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'imported' => 'Imported',
                        'failed' => 'Failed',
                        'ignored' => 'Ignored',
                    ])
                    ->default('pending'),

                SelectFilter::make('vendor_domain')
                    ->label('Vendor')
                    ->options(fn () => ScrapedProduct::query()
                        ->select('vendor_domain')
                        ->distinct()
                        ->pluck('vendor_domain', 'vendor_domain')
                        ->toArray()),

                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('import')
                    ->label('Import as Product')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (ScrapedProduct $record) => $record->isPending())
                    ->form([
                        Select::make('product_id')
                            ->label('Link to Existing Product (optional)')
                            ->searchable()
                            ->options(fn () => Product::query()
                                ->limit(100)
                                ->pluck('name', 'id')
                                ->toArray())
                            ->helperText('Leave empty to create a new product'),

                        TextInput::make('markup_percent')
                            ->label('Markup %')
                            ->numeric()
                            ->default(20)
                            ->suffix('%')
                            ->helperText('Your selling price will be vendor price + markup'),

                        Textarea::make('notes')
                            ->label('Import Notes')
                            ->rows(3),
                    ])
                    ->action(function (ScrapedProduct $record, array $data) {
                        try {
                            if ($data['product_id']) {
                                // Link to existing product
                                $product = Product::find($data['product_id']);
                                $record->markAsImported($product, auth()->user(), $data['notes'] ?? null);
                            } else {
                                // Create new product
                                $markupPercent = $data['markup_percent'] ?? 20;
                                $sellingPrice = $record->price_numeric * (1 + ($markupPercent / 100));

                                $product = Product::create([
                                    'name' => $record->title,
                                    'sku' => $record->sku,
                                    'price' => $sellingPrice,
                                    'description' => "Imported from {$record->vendor_domain}",
                                    'is_active' => true,
                                ]);

                                $record->markAsImported($product, auth()->user(), $data['notes'] ?? null);
                            }

                            Notification::make()
                                ->title('Product Imported')
                                ->body("Successfully imported '{$record->title}'")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('ignore')
                    ->label('Ignore')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (ScrapedProduct $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Ignore this product?')
                    ->modalDescription('This will mark the product as ignored and hide it from pending imports.')
                    ->action(fn (ScrapedProduct $record) => $record->markAsIgnored('Manually ignored')),

                Action::make('viewRawData')
                    ->label('View Raw Data')
                    ->icon('heroicon-o-code-bracket')
                    ->color('primary')
                    ->modalContent(fn (ScrapedProduct $record) => view('filament.pages.scraped-product-raw-data', [
                        'data' => $record->raw_data,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('viewHtml')
                    ->label('View Cached HTML')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->visible(fn (ScrapedProduct $record) => ! empty($record->html_cache_path))
                    ->url(fn (ScrapedProduct $record) => route('scraped-products.html-cache', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('exportCsv')
                        ->label('Export as CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            return self::exportToCsv($records);
                        }),

                    BulkAction::make('exportJson')
                        ->label('Export as JSON')
                        ->icon('heroicon-o-code-bracket')
                        ->color('primary')
                        ->action(function (Collection $records) {
                            return self::exportToJson($records);
                        }),

                    BulkAction::make('bulkImport')
                        ->label('Bulk Import')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Import Products')
                        ->modalDescription('This will create new products for all selected scraped items.')
                        ->form([
                            TextInput::make('markup_percent')
                                ->label('Markup %')
                                ->numeric()
                                ->default(20)
                                ->suffix('%')
                                ->required()
                                ->helperText('Apply this markup to all imported products'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $markupPercent = $data['markup_percent'] ?? 20;
                            $imported = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                if (! $record->isPending()) {
                                    continue;
                                }

                                try {
                                    $sellingPrice = $record->price_numeric * (1 + ($markupPercent / 100));

                                    $product = Product::create([
                                        'name' => $record->title,
                                        'sku' => $record->sku,
                                        'price' => $sellingPrice,
                                        'description' => "Imported from {$record->vendor_domain}",
                                        'is_active' => true,
                                    ]);

                                    $record->markAsImported($product, auth()->user(), 'Bulk imported');
                                    $imported++;
                                } catch (\Exception $e) {
                                    $record->markAsFailed($e->getMessage());
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('Bulk Import Complete')
                                ->body("Imported: {$imported}, Failed: {$failed}")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('bulkIgnore')
                        ->label('Bulk Ignore')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn (ScrapedProduct $record) => $record->markAsIgnored('Bulk ignored'));

                            Notification::make()
                                ->title('Products Ignored')
                                ->body("{$records->count()} products marked as ignored")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    protected static function exportToCsv(Collection $records)
    {
        $filename = 'scraped-products-'.now()->format('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');

            // Write header
            fputcsv($file, [
                'ID',
                'Title',
                'SKU',
                'NSN',
                'CAGE Code',
                'Mil-Spec',
                'Price',
                'Price Numeric',
                'Vendor Domain',
                'Source URL',
                'Status',
                'Linked Product',
                'Imported By',
                'Imported At',
                'Scraped At',
            ]);

            // Write data
            foreach ($records as $record) {
                fputcsv($file, [
                    $record->id,
                    $record->title,
                    $record->sku,
                    $record->nsn,
                    $record->cage_code,
                    $record->milspec,
                    $record->price,
                    $record->price_numeric,
                    $record->vendor_domain,
                    $record->source_url,
                    $record->status,
                    $record->product?->name,
                    $record->importer?->name,
                    $record->imported_at?->toDateTimeString(),
                    $record->created_at->toDateTimeString(),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    protected static function exportToJson(Collection $records)
    {
        $filename = 'scraped-products-'.now()->format('Y-m-d-His').'.json';

        $data = $records->map(function (ScrapedProduct $record) {
            return [
                'id' => $record->id,
                'title' => $record->title,
                'sku' => $record->sku,
                'nsn' => $record->nsn,
                'cage_code' => $record->cage_code,
                'milspec' => $record->milspec,
                'price' => $record->price,
                'price_numeric' => $record->price_numeric,
                'vendor_domain' => $record->vendor_domain,
                'source_url' => $record->source_url,
                'status' => $record->status,
                'linked_product' => $record->product?->name,
                'imported_by' => $record->importer?->name,
                'imported_at' => $record->imported_at?->toIso8601String(),
                'scraped_at' => $record->created_at->toIso8601String(),
                'raw_data' => $record->raw_data,
            ];
        });

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return Response::make($data->toJson(JSON_PRETTY_PRINT), 200, $headers);
    }
}
