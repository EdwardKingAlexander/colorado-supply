<?php

namespace App\Filament\Resources;

use Dom\Text;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->required()
                    ->getSearchResultsUsing(fn (string $search): array => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id', 'email')->toArray())
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name),
                Forms\Components\TextInput::make('ship_to_address')
                    ->label('Ship To Address'),
                Forms\Components\TextInput::make('ship_to_city')
                    ->label('Ship To City'),
                Forms\Components\TextInput::make('ship_to_state')
                    ->label('Ship To State'),
                Forms\Components\TextInput::make('ship_to_zip')
                    ->label('Ship To Zip'),
                Forms\Components\TextInput::make('billing_address')
                    ->label('Billing Address'),
                Forms\Components\TextInput::make('billing_city')
                    ->label('Billing City'),
                Forms\Components\TextInput::make('billing_state')
                    ->label('Billing State'),
                Forms\Components\TextInput::make('billing_zip')
                    ->label('Billing Zip'),
                Forms\Components\TextInput::make('email')
                    ->label('Email'),
                Forms\Components\TextInput::make('first_name')
                    ->label('First Name'),
                Forms\Components\TextInput::make('last_name')
                    ->label('Last Name'),
                Forms\Components\TextInput::make('company_name')
                    ->label('Company Name'),
                Forms\Components\TextInput::make('country')
                    ->label('Country'),
                Forms\Components\TextInput::make('state')
                    ->label('State'),
                Forms\Components\TextInput::make('city')
                    ->label('City'),
                Forms\Components\TextInput::make('zip_code')
                    ->label('Zip Code'),
                Forms\Components\TextInput::make('fax_number')
                    ->label('Fax Number'),
                Forms\Components\TextInput::make('address_line_1')
                    ->label('Address Line 1'),
                Forms\Components\TextInput::make('address_line_2')
                    ->label('Address Line 2'),
                Forms\Components\TextInput::make('phone_number')
                    ->label('Phone Number'),
                Forms\Components\TextInput::make('mobile_number')
                    ->label('Mobile Number'),
                Forms\Components\TextInput::make('website')
                    ->label('Website'),
                Forms\Components\TextInput::make('tax_id')
                    ->label('Tax ID'),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes'),
                Forms\Components\DateTimePicker::make('last_interaction')
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
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUserDetails::route('/'),
            'create' => Pages\CreateUserDetail::route('/create'),
            'edit' => Pages\EditUserDetail::route('/{record}/edit'),
        ];
    }
}
