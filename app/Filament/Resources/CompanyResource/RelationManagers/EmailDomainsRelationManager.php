<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use App\Models\Admin;
use App\Models\CompanyEmailDomain;
use App\Services\Organizations\CompanyDomainService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailDomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'emailDomains';

    protected static ?string $title = 'Approved Email Domains';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('domain')
                ->required()
                ->maxLength(253)
                ->unique(ignoreRecord: true)
                ->placeholder('example.com')
                ->helperText('Enter only the domain, without @ or https://.'),

            Toggle::make('is_primary')
                ->label('Primary domain')
                ->helperText('Each company must retain one primary domain.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('domain')
            ->columns([
                TextColumn::make('domain')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean(),

                TextColumn::make('approvedBy.name')
                    ->label('Approved by')
                    ->placeholder('System'),

                TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('activateEnforcement')
                    ->label('Enable enforcement')
                    ->icon('heroicon-o-lock-closed')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn () => ! $this->getOwnerRecord()->enforcesEmailDomains())
                    ->action(function (CompanyDomainService $domains) {
                        $admin = auth('admin')->user();
                        $domains->activate($this->getOwnerRecord(), $admin instanceof Admin ? $admin : null);

                        Notification::make()
                            ->title('Email-domain enforcement enabled')
                            ->success()
                            ->send();
                    }),

                Action::make('deactivateEnforcement')
                    ->label('Disable enforcement')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn () => $this->getOwnerRecord()->enforcesEmailDomains())
                    ->action(function (CompanyDomainService $domains) {
                        $admin = auth('admin')->user();
                        $domains->deactivate($this->getOwnerRecord(), $admin instanceof Admin ? $admin : null);

                        Notification::make()
                            ->title('Email-domain enforcement disabled')
                            ->warning()
                            ->send();
                    }),

                CreateAction::make(),
            ])
            ->actions([
                Action::make('makePrimary')
                    ->label('Make primary')
                    ->icon('heroicon-o-star')
                    ->visible(fn (CompanyEmailDomain $record) => ! $record->is_primary)
                    ->action(fn (CompanyEmailDomain $record, CompanyDomainService $domains) => $domains->makePrimary($record)),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
