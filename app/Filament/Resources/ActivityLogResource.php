<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLog;
use App\Filament\Resources\ActivityLogResource\Pages\ViewActivityLog;
use App\Filament\Resources\CRM\CustomerResource;
use App\Filament\Resources\CRM\OpportunityResource;
use App\Filament\Resources\CRM\OrderResource;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Activity Log';

    protected static ?int $navigationSort = 51;

    /**
     * Where a subject links to: its own view page where one exists,
     * otherwise its edit page.
     *
     * @var array<class-string, array{0: class-string, 1: string}>
     */
    protected static array $subjectPages = [
        Order::class => [OrderResource::class, 'view'],
        User::class => [UserResource::class, 'edit'],
        Customer::class => [CustomerResource::class, 'edit'],
        Opportunity::class => [OpportunityResource::class, 'view'],
        Vendor::class => [VendorResource::class, 'edit'],
    ];

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('log_name')->badge(),
            TextEntry::make('event')
                ->badge()
                ->color(fn (?string $state): string => static::eventColor($state)),
            TextEntry::make('description')->label('Change'),
            TextEntry::make('subject')
                ->getStateUsing(fn (ActivityLog $record): string => $record->subject_type
                    ? class_basename($record->subject_type).' #'.$record->subject_id
                    : '—'),
            TextEntry::make('causer')
                ->getStateUsing(fn (ActivityLog $record): string => static::causerLabel($record)),
            TextEntry::make('company.name')->placeholder('—'),
            TextEntry::make('created_at')->dateTime('M j, Y H:i'),
            KeyValueEntry::make('new_values')
                ->label('New values')
                ->getStateUsing(fn (ActivityLog $record): array => $record->properties['attributes'] ?? [])
                ->visible(fn (ActivityLog $record): bool => filled($record->properties['attributes'] ?? [])),
            KeyValueEntry::make('old_values')
                ->label('Previous values')
                ->getStateUsing(fn (ActivityLog $record): array => $record->properties['old'] ?? [])
                ->visible(fn (ActivityLog $record): bool => filled($record->properties['old'] ?? [])),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')->badge()->sortable(),
                TextColumn::make('subject_type')
                    ->label('Record')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->sortable(),
                TextColumn::make('subject_id')
                    ->label('ID')
                    ->placeholder('—')
                    ->url(fn (ActivityLog $record): ?string => static::subjectUrl($record))
                    ->sortable(),
                TextColumn::make('event')
                    ->badge()
                    ->color(fn (?string $state): string => static::eventColor($state)),
                TextColumn::make('causer')
                    ->label('By')
                    ->getStateUsing(fn (ActivityLog $record): string => static::causerLabel($record)),
                TextColumn::make('description')
                    ->label('Change')
                    ->getStateUsing(fn (ActivityLog $record): string => static::changeSummary($record))
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('At')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('subject_type')
                    ->label('Record type')
                    ->options(fn (): array => ActivityLog::query()
                        ->whereNotNull('subject_type')
                        ->distinct()
                        ->pluck('subject_type')
                        ->mapWithKeys(fn (string $type) => [$type => class_basename($type)])
                        ->all()),

                Filter::make('causer')
                    ->schema([
                        Select::make('type')
                            ->label('Caused by')
                            ->options(['user' => 'User', 'admin' => 'Admin', 'system' => 'System']),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['type'] ?? null) {
                            'user' => $query->where('causer_type', User::class),
                            'admin' => $query->where('causer_type', Admin::class),
                            'system' => $query->whereNull('causer_type'),
                            default => $query,
                        };
                    }),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($qq, $d) => $qq->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($qq, $d) => $qq->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['subject', 'causer', 'company']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLog::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
            // no create/edit/delete pages: this is a trail, not editable data
        ];
    }

    /**
     * Link to the logged record's own page, or null when there is no
     * resolvable subject, no mapped page, or no permission — the ID
     * then renders as plain text.
     */
    public static function subjectUrl(ActivityLog $entry): ?string
    {
        [$resource, $page] = static::$subjectPages[$entry->subject_type] ?? [null, null];

        if ($resource === null || ! $resource::hasPage($page)) {
            return null;
        }

        $subject = $entry->subject;

        if (! $subject) {
            return null;
        }

        $allowed = $page === 'view'
            ? $resource::canView($subject)
            : $resource::canEdit($subject);

        if (! $allowed) {
            return null;
        }

        return $resource::getUrl($page, ['record' => $subject]);
    }

    public static function causerLabel(ActivityLog $entry): string
    {
        $causer = $entry->causer;

        if (! $causer) {
            return 'System';
        }

        return $causer->name ?? class_basename($causer).' #'.$causer->getKey();
    }

    public static function changeSummary(ActivityLog $entry): string
    {
        $keys = array_keys($entry->properties['attributes'] ?? []);

        if ($entry->event === 'updated' && $keys !== []) {
            return "{$entry->description} (".implode(', ', $keys).')';
        }

        return $entry->description;
    }

    protected static function eventColor(?string $event): string
    {
        return match ($event) {
            'created', 'role_assigned', 'permission_granted' => 'success',
            'updated' => 'info',
            'deleted', 'role_removed', 'permission_revoked' => 'danger',
            'restored' => 'warning',
            default => 'gray',
        };
    }
}
