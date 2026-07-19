<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * auth:sanctum on its own only proves "this is a valid token for SOME
 * Sanctum-tokenable model" — it does NOT care whether that model is
 * Admin, Driver, or User. Without this check, a driver's or customer's
 * token would pass auth:sanctum on an /api/admin/* route just fine, and
 * then crash (or worse, silently misbehave) the moment a controller
 * tries to read ->role or ->isSuperAdmin() on a model that doesn't have
 * them. This middleware closes that gap explicitly.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Admin) {
            abort(403, 'This action is unauthorized.');
        }

        if ($request->user()->status !== 'active') {
            abort(403, 'This admin account has been deactivated.');
        }

        return $next($request);
    }
}