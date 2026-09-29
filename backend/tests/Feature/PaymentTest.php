<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\FeatureTestCase;

class PaymentTest extends FeatureTestCase
{
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        config(['bento.payment.gateway' => 'fake']);
        ['store' => $this->store] = $this->createTenant();
    }

    private function mobileMoneyOrder(): Order
    {
        $order = $this->placeOrder($this->store, 'AIRTEL_MONEY');
        Sanctum::actingAs($order->customer);

        return $order;
    }

    public function test_mobile_money_payment_is_pending_then_paid(): void
    {
        $order = $this->mobileMoneyOrder();

        $payment = $this->postJson('/api/payments', ['order_id' => $order->id, 'phone' => '0991234567'])
            ->assertOk()
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.amount', $order->total)
            ->assertJsonPath('data.phone', '+265991234567')
            ->json('data');

        // Customer approved the USSD prompt; the app polls.
        $this->getJson("/api/payments/{$payment['id']}")->assertJsonPath('data.status', 'PAID');

        $this->assertSame(PaymentStatus::PAID, $order->fresh()->payment_status);
    }

    public function test_paid_order_can_now_be_accepted_by_the_kitchen(): void
    {
        $order = $this->mobileMoneyOrder();
        $id = $this->postJson('/api/payments', ['order_id' => $order->id])->json('data.id');

        $this->actingAsRole(UserRole::KITCHEN_STAFF, $this->store);
        $this->postJson("/api/admin/orders/{$order->id}/accept")->assertJsonPath('error.code', 'PAYMENT_REQUIRED');

        Sanctum::actingAs($order->customer);
        $this->getJson("/api/payments/{$id}")->assertJsonPath('data.status', 'PAID');

        $this->actingAsRole(UserRole::KITCHEN_STAFF, $this->store);
        $this->postJson("/api/admin/orders/{$order->id}/accept")->assertOk();
    }

    public function test_declined_payment_can_be_retried(): void
    {
        $order = $this->mobileMoneyOrder();

        $this->postJson('/api/payments', ['order_id' => $order->id, 'phone' => '0991230000'])
            ->assertOk()
            ->assertJsonPath('data.status', 'FAILED')
            ->assertJsonPath('data.failure_code', 'INSUFFICIENT_FUNDS');
        $this->assertSame(PaymentStatus::FAILED, $order->fresh()->payment_status);

        $retry = $this->postJson('/api/payments', ['order_id' => $order->id, 'phone' => '0991234567'])->json('data');
        $this->getJson("/api/payments/{$retry['id']}")->assertJsonPath('data.status', 'PAID');
        $this->assertSame(2, $order->payments()->count());
    }

    public function test_starting_twice_returns_the_same_pending_charge(): void
    {
        $order = $this->mobileMoneyOrder();

        $first = $this->postJson('/api/payments', ['order_id' => $order->id, 'phone' => '0991239999'])->json('data.id');
        $second = $this->postJson('/api/payments', ['order_id' => $order->id, 'phone' => '0991239999'])->json('data.id');

        $this->assertSame($first, $second);
    }

    public function test_cash_orders_and_paid_orders_need_no_payment(): void
    {
        $cash = $this->placeOrder($this->store, 'CASH');
        Sanctum::actingAs($cash->customer);
        $this->postJson('/api/payments', ['order_id' => $cash->id])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PAYMENT_NOT_REQUIRED');

        $order = $this->mobileMoneyOrder();
        $id = $this->postJson('/api/payments', ['order_id' => $order->id])->json('data.id');
        $this->getJson("/api/payments/{$id}");
        $this->postJson('/api/payments', ['order_id' => $order->id])->assertStatus(409);
    }

    public function test_customers_cannot_pay_or_see_other_customers_payments(): void
    {
        $order = $this->mobileMoneyOrder();
        $id = $this->postJson('/api/payments', ['order_id' => $order->id])->json('data.id');

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/payments', ['order_id' => $order->id])->assertStatus(404);
        $this->getJson("/api/payments/{$id}")->assertStatus(404);
    }

    public function test_signed_webhook_marks_paid_after_reverifying(): void
    {
        $order = $this->mobileMoneyOrder();
        $reference = $this->postJson('/api/payments', ['order_id' => $order->id])->json('data.reference');
        $this->app['auth']->forgetGuards();

        $body = json_encode(['reference' => $reference, 'status' => 'success']);
        $this->call('POST', '/api/payments/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FAKE_SIGNATURE' => hash_hmac('sha256', $body, 'fake-secret'),
        ], $body)->assertOk()->assertJsonPath('data.status', 'PAID');

        $this->assertSame(PaymentStatus::PAID, $order->fresh()->payment_status);
    }

    public function test_forged_webhook_is_rejected(): void
    {
        $order = $this->mobileMoneyOrder();
        $reference = $this->postJson('/api/payments', ['order_id' => $order->id])->json('data.reference');

        $body = json_encode(['reference' => $reference, 'status' => 'success']);
        $this->call('POST', '/api/payments/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_FAKE_SIGNATURE' => 'not-a-valid-signature',
        ], $body)->assertStatus(403)->assertJsonPath('error.code', 'INVALID_SIGNATURE');

        $this->assertSame(PaymentStatus::PENDING, Payment::where('reference', $reference)->first()->status);
    }

    public function test_cancelling_a_paid_order_refunds_it(): void
    {
        $order = $this->mobileMoneyOrder();
        $id = $this->postJson('/api/payments', ['order_id' => $order->id])->json('data.id');
        $this->getJson("/api/payments/{$id}");

        $this->postJson("/api/orders/{$order->id}/cancel")->assertOk();

        $this->assertSame(PaymentStatus::REFUNDED, Payment::find($id)->status);
        $this->assertSame(PaymentStatus::REFUNDED, $order->fresh()->payment_status);
    }

    public function test_unpaid_mobile_money_orders_expire(): void
    {
        $unpaid = $this->mobileMoneyOrder();
        $cash = $this->placeOrder($this->store, 'CASH');

        $this->travel(16)->minutes();
        $this->artisan('orders:expire-unpaid')->assertSuccessful();

        $this->assertSame(OrderStatus::CANCELLED, $unpaid->fresh()->status);
        $this->assertSame('PAYMENT_TIMEOUT', $unpaid->fresh()->cancel_reason_code);
        $this->assertSame(OrderStatus::NEW, $cash->fresh()->status);
    }
}
