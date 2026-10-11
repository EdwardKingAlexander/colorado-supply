<?php

namespace App\Filament\Resources\CRM\OrderResource\Pages;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PickupStatus;
use App\Filament\Resources\CRM\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All orders'),
            'will_call' => Tab::make('Will call queue')
                ->modifyQueryUsing(fn (Builder $query) => self::openWillCall($query))
                ->badge(fn () => self::openWillCall(Order::query())->count() ?: null)
                ->badgeColor('warning'),
        ];
    }

    /**
     * Will-call orders still to be pulled or collected.
     */
    private static function openWillCall(Builder $query): Builder
    {
        return $query
            ->where('fulfillment_method', FulfillmentMethod::WillCall)
            ->whereIn('pickup_status', [PickupStatus::AwaitingPick, PickupStatus::ReadyForPickup])
            ->where('status', '!=', OrderStatus::Cancelled);
    }
}
