<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Services\Admin\AdminActivityLogger;
use App\Support\AdminPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminRoleController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => AdminRole::query()->withCount('users')->orderBy('name')->get(),
            'permissions' => AdminPermissions::groups(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $role = AdminRole::create($data);
        $this->activity->log('role.created', $role, "Created role {$role->name}.", ['permissions' => $role->permissions]);

        return response()->json(['data' => $role->loadCount('users')], 201);
    }

    public function update(Request $request, AdminRole $role): JsonResponse
    {
        $data = $this->validated($request, $role);
        $role->update($data);
        $this->activity->log('role.updated', $role, "Updated role {$role->name}.", ['permissions' => $role->permissions]);

        return response()->json(['data' => $role->fresh()->loadCount('users')]);
    }

    public function destroy(AdminRole $role): JsonResponse
    {
        abort_if($role->is_system, 422, 'System roles cannot be deleted.');
        abort_if($role->users()->exists(), 422, 'Move users to another role before deleting this role.');

        $name = $role->name;
        $role->delete();
        $this->activity->log('role.deleted', null, "Deleted role {$name}.");

        return response()->json(['message' => 'Role deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?AdminRole $role = null): array
    {
        $data = $request->validate([
            'name' => [$role ? 'sometimes' : 'required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', 'unique:admin_roles,slug'.($role ? ','.$role->id : '')],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', array_merge(['*'], AdminPermissions::all()))],
            'is_system' => ['sometimes', 'boolean'],
        ]);

        return $data;
    }
}
