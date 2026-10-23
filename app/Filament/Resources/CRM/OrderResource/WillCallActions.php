<?php

namespace App\Filament\Resources\CRM\OrderResource;

use App\Models\Order;
use App\Services\Orders\WillCallPickupService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * One-click will-call pickup actions for the order list and order view
 * (ai/modules/will-call-notifications-complete, phase 1). Visibility mirrors
 * WillCallPickupService, which enforces the transitions server-side.
 */
class WillCallActions
{
    /**
     * @return list<Action>
     */
    public static function make(): array
    {
        return [self::markReady(), self::markPickedUp(), self::revert()];
    }

    public static function markReady(): Action
    {
        return Action::make('markReadyForPickup')
            ->label('Ready for pickup')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('info')
            ->visible(fn (Order $record) => self::service()->canMarkReady($record))
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record) => "Mark {$record->order_number} ready for pickup?")
            ->modalDescription(function (Order $record) {
                $short = self::service()->shortLines($record);

                if ($short === []) {
                    return 'A pickup code is generated for the buyer to bring to the counter.';
                }

                $lines = collect($short)
                    ->map(fn (array $line) => "{$line['name']} (ordered {$line['ordered']}, on hand {$line['on_hand']})")
                    ->implode('; ');

                return "Stock looks short for: {$lines}. Only continue if everything has been pulled.";
            })
            ->action(fn (Order $record) => self::run(
                fn () => self::service()->markReady($record),
                fn () => "Ready for pickup. Code {$record->pickup_code}.",
            ));
    }

    public static function markPickedUp(): Action
    {
        return Action::make('markPickedUp')
            ->label('Picked up')
            ->icon(Heroicon::OutlinedHandRaised)
            ->color('success')
            ->visible(fn (Order $record) => self::service()->canMarkPickedUp($record))
            ->modalHeading(fn (Order $record) => "Hand over {$record->order_number}")
            ->modalSubmitActionLabel('Mark picked up')
            ->schema([
                TextInput::make('picked_up_by')
                    ->label('Collected by')
                    ->helperText('Name of the person picking up.')
                    ->required()
                    ->maxLength(255),
                TextInput::make('pickup_code')
                    ->label('Pickup code')
                    ->helperText('Ask for the code from the ready-for-pickup message.')
                    ->required(fn ($get) => ! $get('override_code'))
                    ->maxLength(20),
                Toggle::make('override_code')
                    ->label('Hand over without the code (ID checked)')
                    ->live(),
            ])
            ->action(fn (Order $record, array $data) => self::run(
                fn () => self::service()->markPickedUp(
                    $record,
                    $data['picked_up_by'],
                    $data['pickup_code'] ?? null,
                    (bool) ($data['override_code'] ?? false),
                ),
                fn () => "Picked up by {$record->picked_up_by}.",
            ));
    }

    public static function revert(): Action
    {
        return Action::make('revertToAwaitingPick')
            ->label('Not ready')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('gray')
            ->visible(fn (Order $record) => self::service()->canRevertToAwaitingPick($record))
            ->requiresConfirmation()
            ->modalHeading('Move back to awaiting pick?')
            ->modalDescription('Use this if the order was marked ready by mistake. The buyer keeps the same pickup code.')
            ->action(fn (Order $record) => self::run(
                fn () => self::service()->revertToAwaitingPick($record),
                fn () => 'Moved back to awaiting pick.',
            ));
    }

    private static function service(): WillCallPickupService
    {
        return app(WillCallPickupService::class);
    }

    /**
     * Run a transition and report the outcome as a Filament toast; a refused
     * transition (stale page, wrong code) is shown instead of thrown.
     */
    private static function run(callable $transition, callable $successMessage): void
    {
        try {
            $transition();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Not updated')
                ->body(collect($exception->errors())->flatten()->first())
                ->danger()
                ->send();

            return;
        }

        Notification::make()->title($successMessage())->success()->send();
    }
}
