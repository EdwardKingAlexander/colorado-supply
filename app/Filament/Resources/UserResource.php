<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255)
                            ->revealable()
                            ->helperText(fn ($context) => $context === 'edit' ? 'Leave blank to keep current password' : null),
                    ])
                    ->columns(2),

                Section::make('Roles & Permissions')
                    ->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->visible(fn () => auth()->user()?->can('users.assignRoles'))
                            ->helperText('Assign one or more roles to this user'),

                        Select::make('permissions')
                            ->label('Direct Permissions')
                            ->relationship('permissions', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->visible(fn () => auth()->user()?->can('users.assignPermissions'))
                            ->helperText('Assign direct permissions (in addition to role permissions)'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'sales_manager' => 'success',
                        'sales_rep' => 'primary',
                        'viewer' => 'gray',
                        default => 'primary',
                    })
                    ->searchable(),

                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->tooltip(fn (User $record) => $record->email_verified_at
                        ? 'Verified '.$record->email_verified_at->diffForHumans()
                        : 'Email not verified')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->since(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('email_verified_at')
                    ->label('Email verified')
                    ->nullable(),
            ])
            ->recordActions([
                Action::make('markVerified')
                    ->label('Mark verified')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark email as verified?')
                    ->modalDescription(fn (User $record) => "Manually verify {$record->email}? Use this when a customer cannot receive the verification email.")
                    ->visible(fn (User $record) => $record->email_verified_at === null
                        && auth()->user()?->can('users.update'))
                    ->action(function (User $record) {
                        $record->forceFill(['email_verified_at' => now()])->save();

                        Notification::make()
                            ->title('Email verified')
                            ->body("{$record->email} has been marked as verified.")
                            ->success()
                            ->send();
                    }),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record) => auth()->user()?->can('delete', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('users.delete')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['roles', 'permissions']);
    }
}
