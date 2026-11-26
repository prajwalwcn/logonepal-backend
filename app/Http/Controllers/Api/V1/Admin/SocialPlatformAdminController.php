<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSocialDesignTypeRequest;
use App\Http\Requests\Api\StoreSocialPlatformRequest;
use App\Http\Resources\SocialDesignTypeResource;
use App\Http\Resources\SocialPlatformResource;
use App\Models\SocialDesignType;
use App\Models\SocialPlatform;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SocialPlatformAdminController extends Controller
{
    public function index(): JsonResponse
    {
        $platforms = SocialPlatform::query()
            ->with(['designTypes' => fn ($query) => $query->withCount('templates')->orderBy('sort_order')])
            ->withCount('templates')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => SocialPlatformResource::collection($platforms)->resolve()]);
    }

    public function store(StoreSocialPlatformRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $platform = SocialPlatform::create($data);

        return response()->json(['data' => (new SocialPlatformResource($platform->load('designTypes')))->resolve()], 201);
    }

    public function update(StoreSocialPlatformRequest $request, SocialPlatform $platform): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $platform->update($data);

        return response()->json(['data' => (new SocialPlatformResource($platform->fresh('designTypes')))->resolve()]);
    }

    public function destroy(SocialPlatform $platform): JsonResponse
    {
        $platform->delete();

        return response()->json(['message' => 'Platform deleted.']);
    }

    public function storeDesignType(StoreSocialDesignTypeRequest $request, SocialPlatform $platform): JsonResponse
    {
        $data = $request->validated();
        $data['platform_id'] = $platform->id;
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['aspect_ratio'] = $data['aspect_ratio'] ?? $this->ratio((int) $data['width'], (int) $data['height']);

        $designType = SocialDesignType::create($data);

        return response()->json(['data' => (new SocialDesignTypeResource($designType->load('platform')))->resolve()], 201);
    }

    public function updateDesignType(StoreSocialDesignTypeRequest $request, SocialDesignType $designType): JsonResponse
    {
        $data = $request->validated();
        $data['platform_id'] = $designType->platform_id;
        $data['slug'] = $data['slug'] ?? $designType->slug;
        $data['aspect_ratio'] = $data['aspect_ratio'] ?? $this->ratio(
            (int) ($data['width'] ?? $designType->width),
            (int) ($data['height'] ?? $designType->height)
        );

        $designType->update($data);

        return response()->json(['data' => (new SocialDesignTypeResource($designType->fresh('platform')))->resolve()]);
    }

    public function destroyDesignType(SocialDesignType $designType): JsonResponse
    {
        $designType->delete();

        return response()->json(['message' => 'Design type deleted.']);
    }

    private function ratio(int $width, int $height): string
    {
        $divisor = $this->gcd($width, $height) ?: 1;

        return intdiv($width, $divisor).':'.intdiv($height, $divisor);
    }

    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return abs($a);
    }
}
