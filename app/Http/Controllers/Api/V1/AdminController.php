<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\LogoAsset;
use App\Models\LogoStyle;
use App\Models\User;
use App\Models\UserDesign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    // ─── Guard ───────────────────────────────────────────────────────────────

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->is_admin) {
            abort(403, 'Admin access required.');
        }
    }

    // ─── Overview Stats ───────────────────────────────────────────────────────

    public function stats(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $weekAgo = now()->subDays(7)->toDateTimeString();
        $counts = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM users) AS total_users,
                (SELECT COUNT(*) FROM users WHERE is_admin = 1) AS admin_users,
                (SELECT COUNT(*) FROM users WHERE created_at >= ?) AS users_this_week,
                (SELECT COUNT(*) FROM user_designs) AS total_designs,
                (SELECT COUNT(*) FROM user_designs WHERE created_at >= ?) AS designs_this_week,
                (SELECT COUNT(*) FROM logo_assets) AS total_assets
        ", [$weekAgo, $weekAgo]);

        $recentUsers = User::latest()->take(5)->get(['id', 'name', 'email', 'is_admin', 'created_at']);

        return response()->json([
            'data' => [
                'kpis' => [
                    'total_users'       => (int) $counts->total_users,
                    'admin_users'       => (int) $counts->admin_users,
                    'total_designs'     => (int) $counts->total_designs,
                    'total_assets'      => (int) $counts->total_assets,
                    'designs_this_week' => (int) $counts->designs_this_week,
                    'users_this_week'   => (int) $counts->users_this_week,
                ],
                'recent_users' => $recentUsers,
            ],
        ]);
    }

    // ─── Users ───────────────────────────────────────────────────────────────

    public function users(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $q = $request->get('q', '');
        $users = User::when($q, fn ($query) => $query->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%"))
            ->withCount(['designs'])
            ->latest()
            ->paginate(25);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    public function toggleAdmin(Request $request, User $user): JsonResponse
    {
        $this->ensureAdmin($request);

        if ($user->id === $request->user()->id) {
            abort(422, 'You cannot revoke your own admin privileges.');
        }

        $user->update(['is_admin' => ! $user->is_admin]);

        return response()->json([
            'data'    => $user->fresh(['designs']),
            'message' => $user->is_admin ? 'User promoted to admin.' : 'Admin privileges revoked.',
        ]);
    }

    public function deleteUser(Request $request, User $user): JsonResponse
    {
        $this->ensureAdmin($request);

        if ($user->id === $request->user()->id) {
            abort(422, 'You cannot delete your own account from admin panel.');
        }

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    // ─── Platform Designs ────────────────────────────────────────────────────

    public function designs(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $q      = $request->get('q', '');
        $format = $request->get('format', '');

        $designs = UserDesign::with('user:id,name,email')
            ->select([
                'id',
                'uuid',
                'user_id',
                'format',
                'name',
                'business_name',
                'tagline',
                'svg',
                'is_draft',
                'is_archived',
                'last_edited_at',
                'created_at',
                'updated_at',
            ])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('business_name', 'like', "%$q%")
                        ->orWhere('name', 'like', "%$q%")
                        ->orWhere('uuid', 'like', "%$q%")
                        ->orWhereHas('user', function ($uq) use ($q) {
                            $uq->where('email', 'like', "%$q%")
                               ->orWhere('name', 'like', "%$q%");
                        });
                });
            })
            ->when($format, fn ($query) => $query->where('format', $format))
            ->latest()
            ->paginate(24);

        return response()->json([
            'data' => $designs->items(),
            'meta' => [
                'total'        => $designs->total(),
                'per_page'     => $designs->perPage(),
                'current_page' => $designs->currentPage(),
                'last_page'    => $designs->lastPage(),
            ],
        ]);
    }

    public function deleteDesign(Request $request, UserDesign $design): JsonResponse
    {
        $this->ensureAdmin($request);
        $design->delete();

        return response()->json(['message' => 'Design deleted successfully.']);
    }

    // ─── Assets ──────────────────────────────────────────────────────────────

    public function assets(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $q         = $request->get('q', '');
        $industry  = $request->get('industry', '');
        $assetType = $request->get('asset_type', '');
        $isPremium = $request->get('is_premium', '');

        $assets = LogoAsset::when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%$q%")
                    ->orWhereJsonContains('keywords', $q)
                    ->orWhereJsonContains('industry_slugs', $q);
            }))
            ->when($industry, fn ($query) => $query->whereJsonContains('industry_slugs', $industry))
            ->when($assetType, fn ($query) => $query->where('asset_type', $assetType))
            ->when($isPremium !== '' && $isPremium !== null, fn ($query) => $query->where('is_premium', filter_var($isPremium, FILTER_VALIDATE_BOOLEAN)))
            ->orderByDesc('id')
            ->paginate(30);

        return response()->json([
            'data' => $assets->items(),
            'meta' => [
                'total'        => $assets->total(),
                'current_page' => $assets->currentPage(),
                'last_page'    => $assets->lastPage(),
            ],
        ]);
    }

    /**
     * Intellectual Asset Upload:
     * Accepts PNG/SVG/WebP, stores it, creates LogoAsset record with
     * industry slugs, keyword tags, style slugs, and metadata.
     */
    public function uploadAsset(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $request->validate([
            'file'            => 'required|file|mimes:png,svg,webp,jpg,jpeg|max:10240',
            'name'            => 'required|string|max:120',
            'industries'      => 'required|array|min:1',
            'industries.*'    => 'string',
            'keywords'        => 'required|array|min:1',
            'keywords.*'      => 'string|max:60',
            'styles'          => 'nullable|array',
            'styles.*'        => 'string',
            'is_premium'      => 'nullable|boolean',
        ]);

        $file      = $request->file('file');
        $ext       = strtolower($file->getClientOriginalExtension());
        $assetType = $ext === 'svg' ? 'svg' : 'png';
        $slug      = Str::slug($request->name) . '-' . Str::random(6);
        $filename  = $slug . '.' . $ext;

        // Store in public storage so SVG renderer can serve it
        $path     = $file->storeAs('assets/symbols', $filename, 'public');
        $imageUrl = '/storage/' . $path;

        $asset = LogoAsset::create([
            'name'           => $request->name,
            'slug'           => $slug,
            'asset_type'     => $assetType,
            'image_url'      => $imageUrl,
            'view_box'       => '0 0 100 100',
            'paths'          => null,
            'keywords'       => array_map('strtolower', array_map('trim', $request->keywords)),
            'industry_slugs' => array_map('strtolower', array_map('trim', $request->industries)),
            'style_slugs'    => array_map('strtolower', array_map('trim', $request->styles ?? [])),
            'is_premium'     => $request->boolean('is_premium', false),
            'is_active'      => true,
            'sort_order'     => 0,
        ]);

        return response()->json([
            'data'    => $asset,
            'message' => 'Asset uploaded and registered. It will now appear in matching design generators.',
        ], 201);
    }

    public function updateAsset(Request $request, LogoAsset $asset): JsonResponse
    {
        $this->ensureAdmin($request);

        $request->validate([
            'name'         => 'sometimes|string|max:120',
            'industries'   => 'sometimes|array',
            'industries.*' => 'string',
            'keywords'     => 'sometimes|array',
            'keywords.*'   => 'string|max:60',
            'styles'       => 'sometimes|array',
            'styles.*'     => 'string',
            'is_active'    => 'sometimes|boolean',
            'is_premium'   => 'sometimes|boolean',
        ]);

        $asset->update([
            'name'           => $request->input('name', $asset->name),
            'keywords'       => array_map('strtolower', array_map('trim', $request->input('keywords', $asset->keywords))),
            'industry_slugs' => array_map('strtolower', array_map('trim', $request->input('industries', $asset->industry_slugs))),
            'style_slugs'    => array_map('strtolower', array_map('trim', $request->input('styles', $asset->style_slugs))),
            'is_active'      => $request->input('is_active', $asset->is_active),
            'is_premium'     => $request->input('is_premium', $asset->is_premium),
        ]);

        return response()->json([
            'data'    => $asset->fresh(),
            'message' => 'Asset metadata updated.',
        ]);
    }

    public function deleteAsset(Request $request, LogoAsset $asset): JsonResponse
    {
        $this->ensureAdmin($request);

        // Remove physical file if stored
        if ($asset->image_url && str_starts_with($asset->image_url, '/storage/')) {
            $storagePath = str_replace('/storage/', '', $asset->image_url);
            Storage::disk('public')->delete($storagePath);
        }

        $asset->delete();

        return response()->json(['message' => 'Asset deleted.']);
    }

    // ─── Industries & Styles for the Upload form ──────────────────────────────

    public function industriesIndex(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $industries = Industry::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'keywords']);

        return response()->json(['data' => $industries]);
    }

    public function stylesIndex(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $styles = LogoStyle::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        return response()->json(['data' => $styles]);
    }
}
