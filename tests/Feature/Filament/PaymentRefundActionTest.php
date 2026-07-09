<?php

namespace Tests\Feature\Filament;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\CRM\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\CRM\OrderResource\RelationManagers\PaymentsRelationManager;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Stripe\ApiRequestor;
use Stripe\StripeClient;
use Tests\Support\Stripe\FakeStripeHttpClient;
use Tests\TestCase;

class PaymentRefundActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $user = User::factory()->create();

        $role = Role::query()->firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['name' => 'super_admin', 'guard_name' => 'web']
        );

        $user->assignRole($role);

        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);

        parent::tearDown();
    }

    private function fakeStripeRefundClient(string $refundId = 're_test_123'): void
    {
        $fakeHttpClient = new FakeStripeHttpClient([
            'post /v1/refunds' => [
                'body' => [
                    'id' => $refundId,
                    'object' => 'refund',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        ApiRequestor::setHttpClient($fakeHttpClient);

        $this->app->instance(StripeClient::class, new StripeClient('sk_test_dummy'));
    }

    public function test_refund_action_only_visible_for_paid_stripe_payments(): void
    {
        $order = Order::factory()->create();

        $paidStripePayment = $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Paid,
            'amount' => 199.99,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_charge_id' => 'ch_test_123',
            'paid_at' => now(),
        ]);

        $pendingStripePayment = $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount' => 50,
            'currency' => 'USD',
            'gateway' => 'stripe',
        ]);

        $paidCashPayment = $order->payments()->create([
            'method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Paid,
            'amount' => 25,
            'currency' => 'USD',
            'gateway' => 'cash',
            'paid_at' => now(),
        ]);

        Livewire::test(PaymentsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertTableActionVisible('refund', $paidStripePayment)
            ->assertTableActionHidden('refund', $pendingStripePayment)
            ->assertTableActionHidden('refund', $paidCashPayment);
    }

    public function test_refund_action_issues_a_full_refund_and_updates_payment(): void
    {
        $this->fakeStripeRefundClient();

        $order = Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
            'grand_total' => 199.99,
        ]);

        $payment = $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Paid,
            'amount' => 199.99,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_charge_id' => 'ch_test_123',
            'paid_at' => now(),
        ]);

        Livewire::test(PaymentsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->callTableAction('refund', $payment, data: [
                'amount' => 199.99,
                'reason' => 'requested_by_customer',
            ])
            ->assertHasNoTableActionErrors();

        $payment->refresh();

        $this->assertSame(PaymentStatus::Refunded, $payment->status);
        $this->assertSame('re_test_123', $payment->gateway_refund_id);
        $this->assertNotNull($payment->refunded_at);
    }

    public function test_partial_refund_does_not_mark_payment_as_fully_refunded(): void
    {
        $this->fakeStripeRefundClient('re_test_partial');

        $order = Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
            'grand_total' => 199.99,
        ]);

        $payment = $order->payments()->create([
            'method' => PaymentMethod::Card,
            'status' => PaymentStatus::Paid,
            'amount' => 199.99,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'gateway_charge_id' => 'ch_test_123',
            'paid_at' => now(),
        ]);

        Livewire::test(PaymentsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->callTableAction('refund', $payment, data: [
                'amount' => 50.00,
                'reason' => 'requested_by_customer',
            ])
            ->assertHasNoTableActionErrors();

        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('re_test_partial', $payment->gateway_refund_id);
        $this->assertSame(5000, $payment->meta['refunds'][0]['amount']);
    }
}
