<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentGateway
{
    public function name(): string;

    /**
     * Create a payment session for an order.
     *
     * @return array{reference: string, checkout_url: string, provider: string}
     */
    public function createCheckout(Order $order): array;

    /**
     * Verify an incoming webhook request and normalise the result.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array{reference: string, status: string, amount_cents: ?int}
     */
    public function verifyWebhook(array $payload, array $headers): array;
}
