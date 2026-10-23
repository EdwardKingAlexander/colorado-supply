<?php

namespace App\Notifications;

/**
 * Every notification the store sends, declared once: who it is for, how it
 * is delivered by default, and how it is labelled
 * (ai/modules/will-call-notifications-complete, phase 2).
 *
 * Buyer types go to the buyer of an order or quote. Admin types go only to
 * admins (App\Models\Admin), each filtered by their own preferences.
 */
enum NotificationType: string
{
    // Buyer
    case OrderReceived = 'order_received';
    case ReadyForPickup = 'ready_for_pickup';
    case PickupReminder = 'pickup_reminder';
    case PickedUp = 'picked_up';
    case PaymentReceived = 'payment_received';
    case PaymentFailed = 'payment_failed';
    case OrderShipped = 'order_shipped';
    case OrderDelivered = 'order_delivered';
    case OrderFulfilled = 'order_fulfilled';
    case OrderCancelled = 'order_cancelled';
    case OrderRefunded = 'order_refunded';
    case BackorderAvailable = 'backorder_available';
    case QuoteReady = 'quote_ready';

    // Admin
    case AdminNewWillCallOrder = 'admin_new_will_call_order';
    case AdminNewOrder = 'admin_new_order';
    case AdminQuoteRequested = 'admin_quote_requested';
    case AdminPaymentReceived = 'admin_payment_received';
    case AdminPaymentFailed = 'admin_payment_failed';
    case AdminBackorderCreated = 'admin_backorder_created';
    case AdminPickupUncollected = 'admin_pickup_uncollected';

    public function isForAdmins(): bool
    {
        return str_starts_with($this->value, 'admin_');
    }

    /**
     * @return list<self>
     */
    public static function forAdmins(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type->isForAdmins()));
    }

    /**
     * Channels used unless the recipient turns one off (admins) or it is
     * unavailable for them (no push device, no texting consent, ...).
     *
     * @return list<NotificationChannel>
     */
    public function defaultChannels(): array
    {
        $email = NotificationChannel::Email;
        $bell = NotificationChannel::Bell;
        $push = NotificationChannel::Push;
        $sms = NotificationChannel::Sms;

        return match ($this) {
            // The order confirmation email is sent at checkout already.
            self::OrderReceived => [$bell],
            self::ReadyForPickup, self::PickupReminder => [$email, $bell, $push, $sms],
            self::PickedUp, self::PaymentReceived, self::PaymentFailed => [$email, $bell],
            self::OrderShipped, self::OrderDelivered, self::OrderFulfilled,
            self::OrderCancelled, self::OrderRefunded,
            self::BackorderAvailable, self::QuoteReady => [$email, $bell, $push],

            self::AdminNewWillCallOrder => [$email, $bell, $push, $sms],
            self::AdminNewOrder, self::AdminQuoteRequested => [$email, $bell, $push],
            self::AdminPaymentReceived, self::AdminPaymentFailed => [$bell, $push],
            self::AdminBackorderCreated, self::AdminPickupUncollected => [$email, $bell],
        };
    }

    public function allows(NotificationChannel $channel): bool
    {
        return in_array($channel, $this->defaultChannels(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::OrderReceived => 'Order received',
            self::ReadyForPickup => 'Ready for pickup',
            self::PickupReminder => 'Pickup reminder',
            self::PickedUp => 'Order picked up',
            self::PaymentReceived => 'Payment received',
            self::PaymentFailed => 'Payment failed',
            self::OrderShipped => 'Order shipped',
            self::OrderDelivered => 'Order delivered',
            self::OrderFulfilled => 'Order complete',
            self::OrderCancelled => 'Order cancelled',
            self::OrderRefunded => 'Order refunded',
            self::BackorderAvailable => 'Backordered item available',
            self::QuoteReady => 'Quote ready',
            self::AdminNewWillCallOrder => 'New will call order',
            self::AdminNewOrder => 'New order',
            self::AdminQuoteRequested => 'Quote requested',
            self::AdminPaymentReceived => 'Payment received',
            self::AdminPaymentFailed => 'Payment failed',
            self::AdminBackorderCreated => 'Backorder created',
            self::AdminPickupUncollected => 'Will call order not picked up',
        };
    }

    /**
     * Shown on the admin preferences page.
     */
    public function description(): string
    {
        return match ($this) {
            self::AdminNewWillCallOrder => 'A buyer placed a will call order that needs pulling.',
            self::AdminNewOrder => 'A buyer placed an order to be shipped.',
            self::AdminQuoteRequested => 'A buyer asked for a quote from a product page.',
            self::AdminPaymentReceived => 'A card or PayPal payment went through.',
            self::AdminPaymentFailed => 'A card or PayPal payment was declined or failed.',
            self::AdminBackorderCreated => 'An order asked for more than is in stock.',
            self::AdminPickupUncollected => 'A will call order has been waiting at the counter for days.',
            default => $this->label(),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::OrderReceived, self::AdminNewOrder => 'heroicon-o-shopping-bag',
            self::ReadyForPickup, self::AdminNewWillCallOrder => 'heroicon-o-building-storefront',
            self::PickupReminder, self::AdminPickupUncollected => 'heroicon-o-clock',
            self::PickedUp, self::OrderFulfilled => 'heroicon-o-check-circle',
            self::PaymentReceived, self::AdminPaymentReceived => 'heroicon-o-credit-card',
            self::PaymentFailed, self::AdminPaymentFailed => 'heroicon-o-exclamation-triangle',
            self::OrderShipped => 'heroicon-o-truck',
            self::OrderDelivered => 'heroicon-o-home',
            self::OrderCancelled => 'heroicon-o-x-circle',
            self::OrderRefunded => 'heroicon-o-arrow-uturn-left',
            self::BackorderAvailable, self::AdminBackorderCreated => 'heroicon-o-archive-box',
            self::QuoteReady, self::AdminQuoteRequested => 'heroicon-o-document-text',
        };
    }
}
