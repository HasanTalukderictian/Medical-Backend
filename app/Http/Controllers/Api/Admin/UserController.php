<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * GET /api/admin/users?search=&role_id=&active=1&per_page=50
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role_id' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->with('adminRole:id,name,slug')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.addcslashes($request->input('search'), '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->filled('role_id'), fn ($q) => $q->where('role_id', $request->integer('role_id')))
            ->when($request->filled('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->latest('id')
            ->paginate($request->integer('per_page', 100));

        return UserResource::collection($users)->additional(['status' => true]);
    }

    /**
     * POST /api/admin/users
     * Body: name, email, password, password_confirmation, role_id, active
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $role = Role::findOrFail($request->integer('role_id'));

        // Full access na thakle keu Super Admin / Admin banate parbe na
        if ($role->isFullAccess() && ! $request->user()->isFullAccess()) {
            return $this->fail('Only a super admin can assign this role.', 403);
        }

        $user = DB::transaction(fn () => User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'), // model e 'hashed' cast ache
            'role_id' => $role->id,
            'active' => $request->boolean('active', true),
        ]));

        return response()->json([
            'status' => true,
            'message' => 'User created successfully',
            'data' => new UserResource($user->load('adminRole:id,name,slug')),
        ], 201);
    }

    /**
     * GET /api/admin/users/{id}
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => new UserResource($user->load('adminRole:id,name,slug')),
        ]);
    }

    /**
     * PUT /api/admin/users/{id}   (form-data hole POST + _method=PUT)
     * Shudhu je field pathabe sheta bodlabe.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $isSelf = $actor->id === $user->id;
        $user->loadMissing('adminRole');

        // Full access user ke shudhu full access user-i bodlate pare
        if ($user->isFullAccess() && ! $actor->isFullAccess()) {
            return $this->fail('Only a super admin can change this user.', 403);
        }

        $newRole = $request->has('role_id') ? Role::findOrFail($request->integer('role_id')) : $user->adminRole;
        $roleChanged = $request->has('role_id') && (int) $request->input('role_id') !== (int) $user->role_id;
        $newActive = $request->has('active') ? $request->boolean('active') : (bool) $user->active;

        if ($isSelf && $roleChanged) {
            return $this->fail('You cannot change your own role.');
        }
        if ($isSelf && ! $newActive) {
            return $this->fail('You cannot deactivate your own account.');
        }
        if ($roleChanged && $newRole->isFullAccess() && ! $actor->isFullAccess()) {
            return $this->fail('Only a super admin can assign this role.', 403);
        }

        // Shesh active Super Admin ke shorano jabe na
        $losesFullAccess = $user->isFullAccess() && (! $newActive || ! $newRole->isFullAccess());
        if ($losesFullAccess && $this->activeFullAccessCount() <= 1) {
            return $this->fail('At least one active super admin must remain.');
        }

        $passwordChanged = $request->filled('password');

        DB::transaction(function () use ($request, $user, $newActive, $passwordChanged, $isSelf) {
            $data = $request->only(['name', 'email', 'role_id']);
            $data['active'] = $newActive;
            if ($passwordChanged) {
                $data['password'] = $request->input('password');
            }
            $user->update($data);

            // Inactive hole ba onno keu password bodlale, tar shob login (token) bati
            if (! $newActive || ($passwordChanged && ! $isSelf)) {
                $user->tokens()->delete();
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'User updated successfully',
            'data' => new UserResource($user->fresh()->load('adminRole:id,name,slug')),
        ]);
    }

    /**
     * DELETE /api/admin/users/{id}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $user->loadMissing('adminRole');

        if ($actor->id === $user->id) {
            return $this->fail('You cannot delete your own account.');
        }
        if ($user->isFullAccess() && ! $actor->isFullAccess()) {
            return $this->fail('Only a super admin can delete this user.', 403);
        }
        if ($user->isFullAccess() && $user->active && $this->activeFullAccessCount() <= 1) {
            return $this->fail('At least one active super admin must remain.');
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /* ---------------------------- helpers ---------------------------- */

    private function activeFullAccessCount(): int
    {
        return User::where('active', true)
            ->whereHas('adminRole', fn ($q) => $q->whereIn('slug', config('permissions.full_access_roles', [])))
            ->count();
    }

    private function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message], $status);
    }
}
