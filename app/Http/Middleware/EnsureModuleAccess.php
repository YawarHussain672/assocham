<?php

namespace App\Http\Middleware;

use App\Support\Roles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side enforcement of App\Support\Roles::MODULE_ACCESS — the real
 * access-control layer (Phase 1). Applied per-route as middleware('module:x'),
 * where x is the module id (matches the route name / shell.js nav id).
 * A logged-in user hitting a module their role doesn't cover gets a 403,
 * regardless of what the sidebar shows (which is filtered client-side too,
 * but that's just UX — this is the actual gate).
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless(
            Roles::canAccessModule($request->user()?->role, $module),
            403,
            'Your role does not have access to this module.'
        );

        return $next($request);
    }
}
