<?php

namespace App\Filament\Resources\CRM\OrderResource\RelationManagers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Stripe\StripeRefundService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use RuntimeException;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Payments')
            ->columns([
                TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge(),
                TextColumn::make('method')
                    ->label('Method'),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => PaymentStatus::Unpaid->value,
                        'warning' => PaymentStatus::Pending->value,
                        'success' => PaymentStatus::Paid->value,
                        'info' => PaymentStatus::Refunded->value,
                        'danger' => PaymentStatus::Failed->value,
                    ]),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD'),
                TextColumn::make('gateway_charge_id')
                    ->label('Charge ID')
                    ->toggleable()
                    ->copyable(),
                TextColumn::make('gateway_refund_id')
                    ->label('Refund ID')
                    ->toggleable()
                    ->copyable(),
                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([
                static::refundAction(),
            ])
            ->bulkActions([]);
    }

    public static function refundAction(): Action
    {
        return Action::make('refund')
            ->label('Refund')
            ->icon('heroicon-o-receipt-refund')
            ->color('danger')
            ->visible(fn (Payment $record) => $record->gateway === 'stripe' && $record->status === PaymentStatus::Paid)
            ->requiresConfirmation()
            ->form([
                TextInput::make('amount')
                    ->label('Amount to Refund (USD)')
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->default(fn (Payment $record) => (float) $record->amount)
                    ->helperText('Leave as the full amount for a full refund.')
                    ->required(),
                Select::make('reason')
                    ->label('Reason')
                    ->options([
                        'requested_by_customer' => 'Requested by customer',
                        'duplicate' => 'Duplicate charge',
                        'fraudulent' => 'Fraudulent',
                    ])
                    ->native(false),
            ])
            ->action(function (Payment $record, array $data) {
                try {
                    $amountCents = (int) round(((float) $data['amount']) * 100);

                    app(StripeRefundService::class)->refund(
                        $record,
                        $amountCents,
                        $data['reason'] ?? null,
                    );

                    Notification::make()
                        ->success()
                        ->title('Refund issued')
                        ->body("Refund processed for payment #{$record->id}.")
                        ->send();
                } catch (RuntimeException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Refund failed')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }
}
