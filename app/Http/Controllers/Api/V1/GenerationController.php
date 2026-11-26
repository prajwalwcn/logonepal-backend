<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GenerateLogosRequest;
use App\Http\Resources\GeneratedLogoResource;
use App\Models\LogoGeneration;
use App\Services\Logo\BrandAnalysisService;
use App\Services\Logo\LogoGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GenerationController extends Controller
{
    public function __construct(
        private readonly LogoGenerationService $generations,
        private readonly BrandAnalysisService $analysis,
    ) {
    }

    public function intent(GenerateLogosRequest $request): JsonResponse
    {
        $intent = $this->analysis->analyze($request->validated()['business_name']);

        return response()->json(['data' => $intent]);
    }

    public function generate(GenerateLogosRequest $request): JsonResponse
    {
        $guestToken = $this->guestToken($request);

        $generation = $this->generations->generate(
            brandName: $request->validated()['business_name'],
            filters: $request->filters(),
            user: $request->user(),
            guestToken: $guestToken,
            ip: $request->ip(),
        );

        $perPage = min(48, max(8, (int) $request->input('per_page', config('logoforge.page_size', 24))));

        $page = $this->generations->page(
            $generation,
            (int) $request->input('page', 1),
            $perPage,
            $request->input('sort'),
        );

        return response()
            ->json($this->payload($generation, $page->items(), [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ]))
            ->header('X-Guest-Token', $guestToken);
    }

    public function show(Request $request, LogoGeneration $generation): JsonResponse
    {
        $perPage = min(48, max(8, (int) $request->query('per_page', config('logoforge.page_size', 24))));

        $page = $this->generations->page(
            $generation,
            (int) $request->query('page', 1),
            $perPage,
            $request->query('sort'),
        );

        return response()->json($this->payload($generation, $page->items(), [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ]));
    }

    private function payload(LogoGeneration $generation, array $logos, array $meta): array
    {
        return [
            'data' => [
                'generation' => [
                    'uuid' => $generation->uuid,
                    'business_name' => $generation->business_name,
                    'engine' => $generation->engine,
                    'intent' => $generation->intent,
                    'total' => $generation->total_count,
                ],
                'logos' => GeneratedLogoResource::collection($logos)->resolve(),
                'meta' => $meta,
            ],
        ];
    }

    private function guestToken(Request $request): string
    {
        $token = $request->header('X-Guest-Token') ?: $request->input('guest_token');

        if (! is_string($token) || ! Str::isUuid($token)) {
            return (string) Str::uuid();
        }

        return $token;
    }
}
