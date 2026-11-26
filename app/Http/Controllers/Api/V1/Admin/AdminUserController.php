<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\Favorite;
use App\Models\User;
use App\Services\Admin\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount(['designs', 'favorites', 'generations', 'aiGenerations', 'supportTickets']);

        return response()->json([
            'data' => [
                'user' => $user->load('role'),
                'roles' => AdminRole::query()->orderBy('name')->get(['id', 'name', 'slug']),
                'recent_designs' => $user->designs()->latest()->limit(10)->get(['id', 'uuid', 'name', 'business_name', 'format', 'moderation_status', 'created_at']),
                'recent_ai' => $user->aiGenerations()->latest()->limit(10)->get(['id', 'provider', 'model', 'purpose', 'status', 'tokens_in', 'tokens_out', 'estimated_cost', 'created_at']),
                'recent_activity' => $user->activityLogs()->latest()->limit(10)->get(),
            ],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'status' => ['sometimes', 'in:active,suspended'],
            'role_id' => ['nullable', 'integer', 'exists:admin_roles,id'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        if ($user->id === $request->user()->id && array_key_exists('is_admin', $data) && ! $data['is_admin']) {
            abort(422, 'You cannot revoke your own administrator access.');
        }

        $user->update($data);
        $this->activity->log('user.updated', $user, "Updated user {$user->email}.", $data);

        return response()->json(['data' => $user->fresh()->load('role')]);
    }

    public function suspend(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot suspend your own account.');
        $user->update(['status' => 'suspended']);
        $this->activity->log('user.suspended', $user, "Suspended user {$user->email}.");

        return response()->json(['data' => $user->fresh(), 'message' => 'User suspended.']);
    }

    public function restore(User $user): JsonResponse
    {
        $user->update(['status' => 'active']);
        $this->activity->log('user.restored', $user, "Restored user {$user->email}.");

        return response()->json(['data' => $user->fresh(), 'message' => 'User restored.']);
    }

    public function favorites(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $favorites = Favorite::query()
            ->with([
                'user:id,name,email',
                'design:id,uuid,name,business_name,svg,format',
                'generatedLogo:id,generation_id,svg',
                'generatedLogo.generation:id,business_name',
            ])
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            })
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 30));

        return response()->json([
            'data' => $favorites->items(),
            'meta' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ],
        ]);
    }

    public function destroyFavorite(Favorite $favorite): JsonResponse
    {
        $favorite->delete();

        return response()->json(['message' => 'Favorite removed.']);
    }
}
