<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/admin/login
     * Body: email, password
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::with('adminRole')->where('email', $request->input('email'))->first();

        if ($user) {
            $passwordOk = Hash::check($request->input('password'), $user->password);
        } else {
            // User na thakleo shomoy nishi, jate response time dekhe email ache kina bojha na jay
            Hash::make($request->input('password'));
            $passwordOk = false;
        }

        if (! $user || ! $passwordOk) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email or password',
            ], 401);
        }

        if (! $user->active) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact the super admin.',
            ], 403);
        }

        if (! $user->adminRole) {
            return response()->json([
                'status' => false,
                'message' => 'No role is assigned to your account. Please contact the super admin.',
            ], 403);
        }

        $token = $user->createToken('admin-panel')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Admin login successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->toAuthPayload(),
        ]);
    }

    /**
     * GET /api/admin/me  (role / permission notun kore ante)
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'user' => $request->user()->toAuthPayload(),
        ]);
    }

    /**
     * POST /api/admin/logout  (shudhu ei device er token muche)
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Logout successful',
        ]);
    }
}
