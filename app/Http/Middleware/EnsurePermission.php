<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route e:  ->middleware('permission:users')
 * Ekadhik: ->middleware('permission:queries,appointments')  (jekono ekta thaklei hobe)
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('adminRole');

        if (! $user->hasPermission(...$permissions)) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to do this.',
            ], 403);
        }

        return $next($request);
    }
}
