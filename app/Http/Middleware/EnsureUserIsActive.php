<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login thaka obosthay user ke inactive kora hole, porer request e-i atke dey
 * ar token muche dey.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->active || ! $user->role_id)) {
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact the super admin.',
            ], 403);
        }

        return $next($request);
    }
}
