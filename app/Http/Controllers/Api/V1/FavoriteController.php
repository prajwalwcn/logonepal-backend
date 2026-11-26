<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DesignResource;
use App\Http\Resources\GeneratedLogoResource;
use App\Models\Favorite;
use App\Models\GeneratedLogo;
use App\Models\UserDesign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()
            ->with(['design', 'generatedLogo.font', 'generatedLogo.symbolAsset'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $favorites->map(fn (Favorite $favorite) => [
                'id' => $favorite->id,
                'type' => $favorite->user_design_id ? 'design' : 'logo',
                'design' => $favorite->design ? (new DesignResource($favorite->design))->resolve() : null,
                'logo' => $favorite->generatedLogo
                    ? (new GeneratedLogoResource($favorite->generatedLogo))->resolve()
                    : null,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_design_id' => ['required_without:generated_logo_id', 'nullable', 'integer', 'exists:user_designs,id'],
            'generated_logo_id' => ['required_without:user_design_id', 'nullable', 'integer', 'exists:generated_logos,id'],
        ]);

        if (isset($validated['user_design_id'])) {
            $design = UserDesign::query()->findOrFail($validated['user_design_id']);
            abort_unless($design->user_id === $request->user()->id, 403);
        }

        $favorite = Favorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'user_design_id' => $validated['user_design_id'] ?? null,
            'generated_logo_id' => $validated['generated_logo_id'] ?? null,
        ]);

        return response()->json(['data' => ['id' => $favorite->id]], 201);
    }

    public function destroy(Request $request, Favorite $favorite): JsonResponse
    {
        abort_unless($favorite->user_id === $request->user()->id, 403);
        $favorite->delete();

        return response()->json(['message' => 'Removed from favorites.']);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'generated_logo_id' => ['required', 'integer', 'exists:generated_logos,id'],
        ]);

        $existing = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('generated_logo_id', $validated['generated_logo_id'])
            ->first();

        if ($existing !== null) {
            $existing->delete();

            return response()->json(['data' => ['favorited' => false]]);
        }

        Favorite::create([
            'user_id' => $request->user()->id,
            'generated_logo_id' => $validated['generated_logo_id'],
        ]);

        return response()->json(['data' => ['favorited' => true]]);
    }
}
