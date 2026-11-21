<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Exception;

class AuthController extends Controller
{
    //

     public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)
                ->where('role', 'admin')
                ->where('active', 1)
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email or password',
                ], 401);
            }

            // Create Sanctum Token
            $token = $user->createToken('admin-token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Admin login successful',
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'active' => $user->active,
                ],
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Login failed',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Admin Profile
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user || $user->role !== 'admin' || !$user->active) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            return response()->json([
                'status' => true,
                'message' => 'Admin profile retrieved successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'active' => $user->active,
                ],
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve profile',
            ], 500);
        }
    }

    /**
     * Admin Dashboard
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user || $user->role !== 'admin' || !$user->active) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            return response()->json([
                'status' => true,
                'message' => 'Welcome to Admin Dashboard',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Dashboard data loading failed',
            ], 500);
        }
    }

    /**
     * Admin Logout
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            // Revoke current token
            $token = $user->currentAccessToken();

            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }

            return response()->json([
                'status' => true,
                'message' => 'Logout successful',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Logout failed',
            ], 500);
        }
    }
}
