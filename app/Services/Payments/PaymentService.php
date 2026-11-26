<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\License;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Logo\LicenseService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function __construct(private readonly LicenseService $licenses)
    {
    }

    public function gateway(?string $driver = null): PaymentGateway
    {
        return match ($driver ?? config('services.payments.driver', 'sandbox')) {
            'sandbox' => new SandboxGateway(),
            default => throw new RuntimeException("Unsupported payment driver [{$driver}]."),
        };
    }

    /**
     * @return array{payment: Payment, checkout_url: string}
     */
    public function start(Order $order): array
    {
        $gateway = $this->gateway();
        $checkout = $gateway->createCheckout($order);

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => $checkout['provider'],
            'provider_reference' => $checkout['reference'],
            'status' => 'pending',
            'amount_cents' => $order->total_cents,
            'currency' => $order->currency,
        ]);

        return ['payment' => $payment, 'checkout_url' => $checkout['checkout_url']];
    }

    /**
     * Idempotent fulfilment driven only by a verified provider webhook.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function handleWebhook(string $provider, array $payload, array $headers): Payment
    {
        $gateway = $this->gateway($provider);
        $verified = $gateway->verifyWebhook($payload, $headers);

        $payment = Payment::query()
            ->where('provider_reference', $verified['reference'])
            ->firstOrFail();

        if ($payment->processed_at !== null) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $verified, $payload) {
            $payment->update([
                'status' => $verified['status'],
                'payload' => $payload,
                'processed_at' => now(),
            ]);

            $order = $payment->order()->with('items.design', 'plan')->firstOrFail();

            if ($verified['status'] !== 'paid') {
                $order->update(['status' => 'failed']);

                return $payment;
            }

            $order->update(['status' => 'paid', 'paid_at' => now()]);

            foreach ($order->items as $item) {
                if ($item->design !== null) {
                    $this->licenses->issue($order->user, $item->design, $order->id);
                }
            }

            if ($order->plan !== null && $order->plan->interval !== 'one_time') {
                Subscription::updateOrCreate(
                    ['user_id' => $order->user_id, 'plan_id' => $order->plan_id, 'status' => 'active'],
                    [
                        'provider' => $payment->provider,
                        'provider_reference' => $payment->provider_reference,
                        'starts_at' => now(),
                        'ends_at' => $order->plan->interval === 'year' ? now()->addYear() : now()->addMonth(),
                    ]
                );
            }

            // Plans bought without a specific design grant an account-wide licence.
            if (! $order->items->contains(fn ($item) => $item->user_design_id !== null)) {
                $this->licenses->issue($order->user, null, $order->id, 'account');
            }

            return $payment;
        });
    }

    /**
     * Sign a sandbox webhook so the local checkout screen can complete an order
     * exactly the way a real provider would.
     */
    public function sandboxSignature(string $reference, string $status): string
    {
        /** @var SandboxGateway $gateway */
        $gateway = $this->gateway('sandbox');

        return $gateway->signatureFor($reference, $status);
    }

    public function licenses(): LicenseService
    {
        return $this->licenses;
    }
}
