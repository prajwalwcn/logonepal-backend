<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Deterministic, fully functional local gateway.
 *
 * It mirrors the real provider flow (create checkout -> redirect -> signed
 * webhook -> fulfilment) so a live provider can be dropped in by registering
 * another PaymentGateway implementation. It never reports an order as paid
 * without a validly signed webhook.
 */
class SandboxGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'sandbox';
    }

    public function createCheckout(Order $order): array
    {
        $reference = 'sbx_'.strtolower(bin2hex(random_bytes(8)));

        return [
            'reference' => $reference,
            'checkout_url' => URL::temporarySignedRoute(
                'payments.sandbox.checkout',
                now()->addMinutes(30),
                ['order' => $order->uuid, 'reference' => $reference]
            ),
            'provider' => $this->name(),
        ];
    }

    public function verifyWebhook(array $payload, array $headers): array
    {
        $reference = (string) ($payload['reference'] ?? '');
        $status = (string) ($payload['status'] ?? '');
        $signature = (string) ($headers['x-logoforge-signature'] ?? '');
        $secret = (string) config('app.key');

        if ($reference === '' || $status === '') {
            throw new RuntimeException('Webhook payload is incomplete.');
        }

        $expected = hash_hmac('sha256', $reference.'|'.$status, $secret);

        if ($secret === '' || ! hash_equals($expected, $signature)) {
            throw new RuntimeException('Webhook signature verification failed.');
        }

        return [
            'reference' => $reference,
            'status' => $status === 'paid' ? 'paid' : 'failed',
            'amount_cents' => isset($payload['amount_cents']) ? (int) $payload['amount_cents'] : null,
        ];
    }

    public function signatureFor(string $reference, string $status): string
    {
        return hash_hmac('sha256', $reference.'|'.$status, (string) config('app.key'));
    }
}
