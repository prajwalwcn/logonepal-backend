<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\SeederTestCase;

class PaymentWebhookTest extends SeederTestCase
{
    use RefreshDatabase;

    public function test_starting_a_checkout_creates_a_pending_payment(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $plan = Plan::query()->where('slug', 'logo-pack')->firstOrFail();

        $response = $this->postJson('/api/v1/orders', ['plan' => $plan->slug])->assertCreated();

        $this->assertNotEmpty($response->json('data.checkout_url'));
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'status' => 'pending']);
        $this->assertSame(1, Payment::query()->where('status', 'pending')->count());
    }

    public function test_webhooks_without_a_valid_signature_are_rejected(): void
    {
        $payment = $this->pendingPayment();

        $this->postJson('/api/v1/webhooks/payments/sandbox', [
            'reference' => $payment->provider_reference,
            'status' => 'paid',
        ], ['X-Logoforge-Signature' => 'forged'])->assertStatus(400);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $payment->order->fresh()->status);
    }

    public function test_a_valid_webhook_marks_the_order_paid_and_issues_a_licence(): void
    {
        $payment = $this->pendingPayment();
        $signature = app(PaymentService::class)->sandboxSignature($payment->provider_reference, 'paid');

        $this->postJson('/api/v1/webhooks/payments/sandbox', [
            'reference' => $payment->provider_reference,
            'status' => 'paid',
            'amount_cents' => $payment->amount_cents,
        ], ['X-Logoforge-Signature' => $signature])->assertOk();

        $this->assertSame('paid', $payment->order->fresh()->status);
        $this->assertNotNull($payment->fresh()->processed_at);
        $this->assertDatabaseCount('licenses', 1);
    }

    public function test_webhooks_are_idempotent(): void
    {
        $payment = $this->pendingPayment();
        $signature = app(PaymentService::class)->sandboxSignature($payment->provider_reference, 'paid');

        $payload = [
            'reference' => $payment->provider_reference,
            'status' => 'paid',
            'amount_cents' => $payment->amount_cents,
        ];

        $this->postJson('/api/v1/webhooks/payments/sandbox', $payload, ['X-Logoforge-Signature' => $signature])->assertOk();
        $this->postJson('/api/v1/webhooks/payments/sandbox', $payload, ['X-Logoforge-Signature' => $signature])->assertOk();

        $this->assertDatabaseCount('licenses', 1);
    }

    private function pendingPayment(): Payment
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $plan = Plan::query()->where('slug', 'logo-pack')->firstOrFail();
        $this->postJson('/api/v1/orders', ['plan' => $plan->slug])->assertCreated();

        $order = Order::query()->where('user_id', $user->id)->firstOrFail();

        return Payment::query()->where('order_id', $order->id)->firstOrFail();
    }
}
