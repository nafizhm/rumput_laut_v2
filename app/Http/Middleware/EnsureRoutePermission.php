<?php

namespace App\Http\Middleware;

use App\Services\Auth\RoutePermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->route()->getName(), ['logout', 'access.home', 'access.empty'], true)) {
            abort_unless(RoutePermissionService::canAccess($request->user(), $request->route()->getName()), 403);
        }

        return $next($request);
    }
}
