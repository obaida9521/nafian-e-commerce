<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    /**
     * Role-based write protection for the admin panel.
     *
     * Reads (GET/HEAD) are allowed for every authenticated admin; mutating
     * requests require the role to manage the area derived from the route name.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $admin = Auth::guard('admin')->user();
        $area = $this->areaFromRoute($request->route()?->getName());

        if ($admin && $area !== null && ! $admin->role->canManage($area)) {
            abort(403, 'Your role does not permit this action.');
        }

        return $next($request);
    }

    /**
     * Derive the admin area from a route name like "admin.products.store".
     */
    private function areaFromRoute(?string $name): ?string
    {
        if ($name === null || ! str_starts_with($name, 'admin.')) {
            return null;
        }

        $segments = explode('.', $name);

        // admin.<area>.<action> — logout has no area and is never area-gated.
        return $segments[1] ?? null;
    }
}
