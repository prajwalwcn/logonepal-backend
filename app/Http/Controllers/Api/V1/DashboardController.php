<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DesignResource;
use App\Models\Favorite;
use App\Models\Order;
use App\Services\Logo\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly LicenseService $licenses)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $designs = $user->designs()->latest('updated_at')->take(8)->get();
        $subscription = $user->activeSubscription();

        return response()->json([
            'data' => [
                'stats' => [
                    'designs' => $user->designs()->count(),
                    'favorites' => $user->favorites()->count(),
                    'orders' => $user->orders()->count(),
                    'downloads' => $user->downloads()->count(),
                ],
                'entitlements' => [
                    'premium' => $this->licenses->isPremium($user),
                    'subscription' => $subscription ? [
                        'plan' => $subscription->plan?->name,
                        'ends_at' => $subscription->ends_at?->toIso8601String(),
                    ] : null,
                ],
                'recent_designs' => DesignResource::collection($designs)->resolve(),
                'recent_orders' => Order::query()
                    ->where('user_id', $user->id)
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(fn (Order $order) => [
                        'number' => $order->number,
                        'status' => $order->status,
                        'total' => number_format($order->total_cents / 100, 2, '.', ''),
                        'currency' => $order->currency,
                        'paid_at' => $order->paid_at?->toIso8601String(),
                    ]),
                'licenses' => $user->licenses()->latest()->take(5)->get()->map(fn ($license) => [
                    'key' => $license->license_key,
                    'type' => $license->type,
                    'issued_at' => $license->issued_at?->toIso8601String(),
                ]),
                'favorites' => Favorite::query()
                    ->where('user_id', $user->id)
                    ->with(['design', 'generatedLogo.generation'])
                    ->latest()
                    ->take(12)
                    ->get()
                    ->map(function (Favorite $favorite) {
                        if ($favorite->design !== null) {
                            return [
                                'key' => 'design-'.$favorite->design->uuid,
                                'type' => 'design',
                                'uuid' => $favorite->design->uuid,
                                'business_name' => $favorite->design->business_name,
                                'svg' => $favorite->design->svg,
                                'edit_href' => '/editor?design='.$favorite->design->uuid,
                                'updated_at' => $favorite->design->updated_at?->toIso8601String(),
                            ];
                        }

                        if ($favorite->generatedLogo !== null) {
                            return [
                                'key' => 'logo-'.$favorite->generatedLogo->id,
                                'type' => 'logo',
                                'logo_id' => $favorite->generatedLogo->id,
                                'business_name' => $favorite->generatedLogo->generation?->business_name ?? 'Logo concept',
                                'svg' => $favorite->generatedLogo->svg,
                                'edit_href' => '/editor?logo='.$favorite->generatedLogo->id,
                                'updated_at' => $favorite->created_at?->toIso8601String(),
                            ];
                        }

                        return null;
                    })
                    ->filter()
                    ->values(),
            ],
        ]);
    }
}
