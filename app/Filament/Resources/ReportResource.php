<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages\ListReports;
use App\Models\Report;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only view of customers' saved report-builder reports, for support.
 * Admins can't edit or run them: reports belong to the customer.
 */
class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Customer Reports';

    protected static ?int $navigationSort = 60;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('owner.email')->label('Owner')->searchable(),
                TextColumn::make('company.name')->label('Company')->placeholder('No company'),
                TextColumn::make('dataset')->badge(),
                TextColumn::make('definition.mode')->label('Type')->formatStateUsing(fn (?string $state) => ucfirst($state ?? 'table')),
                TextColumn::make('visibility')->badge(),
                TextColumn::make('last_run_at')->label('Last run')->dateTime()->sortable()->placeholder('Never'),
                TextColumn::make('updated_at')->label('Updated')->dateTime()->sortable()->since(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('dataset')->options([
                    'orders' => 'Orders',
                    'order_lines' => 'Order lines',
                    'payments' => 'Payments',
                    'shipments' => 'Shipments',
                    'quotes' => 'Quotes',
                    'quote_lines' => 'Quote lines',
                ]),
                SelectFilter::make('visibility')->options([
                    Report::VISIBILITY_PRIVATE => 'Private',
                    Report::VISIBILITY_COMPANY => 'Company',
                ]),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
        ];
    }
}
