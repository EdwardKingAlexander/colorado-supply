<?php

namespace App\Filament\Resources\MilSpecParts;

use App\Filament\Resources\MilSpecParts\Pages\CreateMilSpecPart;
use App\Filament\Resources\MilSpecParts\Pages\EditMilSpecPart;
use App\Filament\Resources\MilSpecParts\Pages\ListMilSpecParts;
use App\Filament\Resources\MilSpecParts\RelationManagers\ProcurementHistoriesRelationManager;
use App\Filament\Resources\MilSpecParts\RelationManagers\SuppliersRelationManager;
use App\Filament\Resources\MilSpecParts\Schemas\MilSpecPartForm;
use App\Filament\Resources\MilSpecParts\Tables\MilSpecPartsTable;
use App\Models\MilSpecPart;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MilSpecPartResource extends Resource
{
    protected static ?string $model = MilSpecPart::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static UnitEnum|string|null $navigationGroup = 'NSN Procurement';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return MilSpecPartForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MilSpecPartsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SuppliersRelationManager::class,
            ProcurementHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMilSpecParts::route('/'),
            'create' => CreateMilSpecPart::route('/create'),
            'edit' => EditMilSpecPart::route('/{record}/edit'),
        ];
    }
}
