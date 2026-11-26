<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        abort_unless($user?->is_admin === true, 403, 'Admin access required.');

        $role = $user->role;
        if ($role === null) {
            return $next($request);
        }

        abort_unless($role->allows($permission), 403, 'You do not have permission to perform this action.');

        return $next($request);
    }
}
