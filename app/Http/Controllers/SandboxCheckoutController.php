<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Local checkout screen for the sandbox gateway.
 *
 * Fulfilment still happens through the signed webhook path, exactly like a
 * live provider. Nothing is marked paid from the browser.
 */
class SandboxCheckoutController extends Controller
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function show(Request $request, Order $order): Response
    {
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->latest()
            ->first();

        return response($this->page($order, $payment?->provider_reference, $request->fullUrl()));
    }

    public function confirm(Request $request, Order $order): Response
    {
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->latest()
            ->firstOrFail();

        $signature = $this->payments->sandboxSignature($payment->provider_reference, 'paid');

        $this->payments->handleWebhook('sandbox', [
            'reference' => $payment->provider_reference,
            'status' => 'paid',
            'amount_cents' => $order->total_cents,
        ], ['x-logoforge-signature' => $signature]);

        $url = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/')
            .'/dashboard?payment=success&order='.$order->number;

        return response('<html><head><meta http-equiv="refresh" content="0;url='.$url.'"></head>'
            .'<body>Payment confirmed. <a href="'.e($url).'">Continue</a></body></html>');
    }

    private function page(Order $order, ?string $reference, string $action): string
    {
        $total = number_format($order->total_cents / 100, 2, '.', '');
        $orderNumber = e($order->number);
        $actionUrl = e($action);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en"><head><meta charset="utf-8"><title>Sandbox checkout</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body{margin:0;min-height:100vh;display:grid;place-items:center;background:#FFF8F2;
            font-family:system-ui,-apple-system,'Segoe UI',sans-serif;color:#050748}
            .card{background:#fff;border-radius:16px;padding:40px;max-width:420px;width:90%;
            box-shadow:0 25px 40px -30px rgba(0,0,0,.3);border:1px solid #F1E6DD}
            h1{font-size:22px;margin:0 0 8px}
            p{color:#6A6A8E;line-height:1.6;font-size:14px}
            .row{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #F4F4F8;font-size:14px}
            .total{font-size:26px;font-weight:700;margin:20px 0}
            button{width:100%;border:0;border-radius:100px;padding:16px;font-size:16px;font-weight:600;
            color:#fff;background:linear-gradient(to right,#FFBD84,#FF1F8E);cursor:pointer}
            .note{margin-top:18px;font-size:12px;color:#959595}
        </style></head>
        <body><div class="card">
            <h1>Sandbox checkout</h1>
            <p>Order <strong>{$orderNumber}</strong> is awaiting payment. This local gateway simulates a
            provider callback — no card is charged.</p>
            <div class="row"><span>Status</span><span>{$order->status}</span></div>
            <div class="row"><span>Reference</span><span>{$reference}</span></div>
            <div class="total">\${$total} {$order->currency}</div>
            <form method="POST" action="{$actionUrl}">
                {$this->csrfField()}
                <button type="submit">Confirm sandbox payment</button>
            </form>
            <p class="note">Fulfilment runs through the same signed webhook path a production provider uses.</p>
        </div></body></html>
        HTML;
    }

    private function csrfField(): string
    {
        return '<input type="hidden" name="_token" value="'.e(csrf_token()).'">';
    }
}
