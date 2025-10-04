<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\UserDetailResource\Pages\ListUserDetails;
use App\Filament\Resources\UserDetailResource\Pages\CreateUserDetail;
use App\Filament\Resources\UserDetailResource\Pages\EditUserDetail;
use Dom\Text;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\UserDetail;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserDetailResource\Pages;
use App\Filament\Resources\UserDetailResource\RelationManagers;

class UserDetailResource extends Resource
{
    protected static ?string $model = UserDetail::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->required()
                    ->getSearchResultsUsing(fn (string $search): array => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id', 'email')->toArray())
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name),
                TextInput::make('ship_to_address')
                    ->label('Ship To Address'),
                TextInput::make('ship_to_city')
                    ->label('Ship To City'),
                TextInput::make('ship_to_state')
                    ->label('Ship To State'),
                TextInput::make('ship_to_zip')
                    ->label('Ship To Zip'),
                TextInput::make('billing_address')
                    ->label('Billing Address'),
                TextInput::make('billing_city')
                    ->label('Billing City'),
                TextInput::make('billing_state')
                    ->label('Billing State'),
                TextInput::make('billing_zip')
                    ->label('Billing Zip'),
                TextInput::make('email')
                    ->label('Email'),
                TextInput::make('first_name')
                    ->label('First Name'),
                TextInput::make('last_name')
                    ->label('Last Name'),
                TextInput::make('company_name')
                    ->label('Company Name'),
                TextInput::make('country')
                    ->label('Country'),
                TextInput::make('state')
                    ->label('State'),
                TextInput::make('city')
                    ->label('City'),
                TextInput::make('zip_code')
                    ->label('Zip Code'),
                TextInput::make('fax_number')
                    ->label('Fax Number'),
                TextInput::make('address_line_1')
                    ->label('Address Line 1'),
                TextInput::make('address_line_2')
                    ->label('Address Line 2'),
                TextInput::make('phone_number')
                    ->label('Phone Number'),
                TextInput::make('mobile_number')
                    ->label('Mobile Number'),
                TextInput::make('website')
                    ->label('Website'),
                TextInput::make('tax_id')
                    ->label('Tax ID'),
                Textarea::make('notes')
                    ->label('Notes'),
                DateTimePicker::make('last_interaction')
                    ->label('Last Interaction'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable(),
                    TextColumn::make('user.email')
                    ->label('Email')
                    ->url(fn ($record) => "mailto:{$record->user->email} ")
                    ->searchable()
                    ->sortable(),
                    TextColumn::make('phone_number')
                    ->label('Phone Number')
                    ->url(fn ($record) => "tel:{$record->phone_number}")
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserDetails::route('/'),
            'create' => CreateUserDetail::route('/create'),
            'edit' => EditUserDetail::route('/{record}/edit'),
        ];
    }
}
