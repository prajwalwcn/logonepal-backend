<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use App\Models\UserDesign;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items', 'plan'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => collect($orders->items())->map(fn (Order $order) => [
                'uuid' => $order->uuid,
                'number' => $order->number,
                'status' => $order->status,
                'currency' => $order->currency,
                'total' => number_format($order->total_cents / 100, 2, '.', ''),
                'created_at' => $order->created_at?->toIso8601String(),
                'paid_at' => $order->paid_at?->toIso8601String(),
                'plan' => $order->plan?->name,
                'items' => $order->items->map(fn ($item) => [
                    'description' => $item->description,
                    'total' => number_format($item->total_cents / 100, 2, '.', ''),
                ]),
            ]),
            'meta' => ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', 'exists:plans,slug'],
            'design_uuid' => ['nullable', 'string', 'exists:user_designs,uuid'],
        ]);

        $plan = Plan::query()->where('slug', $validated['plan'])->where('is_active', true)->firstOrFail();
        $design = null;

        if (isset($validated['design_uuid'])) {
            $design = UserDesign::query()->where('uuid', $validated['design_uuid'])->firstOrFail();
            abort_unless($design->user_id === $request->user()->id, 403);
        }

        $order = DB::transaction(function () use ($plan, $design, $request) {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'subtotal_cents' => $plan->price_cents,
                'total_cents' => $plan->price_cents,
                'currency' => $plan->currency,
            ]);

            $order->items()->create([
                'plan_id' => $plan->id,
                'user_design_id' => $design?->id,
                'description' => $plan->name.($design ? ' — '.$design->business_name : ''),
                'unit_price_cents' => $plan->price_cents,
                'quantity' => 1,
                'total_cents' => $plan->price_cents,
            ]);

            return $order;
        });

        $checkout = $this->payments->start($order);

        return response()->json([
            'data' => [
                'order' => $order->number,
                'status' => $order->status,
                'checkout_url' => $checkout['checkout_url'],
            ],
        ], 201);
    }

    public function webhook(Request $request, string $provider): JsonResponse
    {
        try {
            $payment = $this->payments->handleWebhook(
                $provider,
                $request->all(),
                ['x-logoforge-signature' => (string) $request->header('X-Logoforge-Signature')]
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Webhook rejected.'], 400);
        }

        return response()->json(['data' => ['status' => $payment->status]]);
    }
}
