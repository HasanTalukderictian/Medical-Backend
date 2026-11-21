<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\Admin\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * GET /api/admin/roles
     */
    public function index(): AnonymousResourceCollection
    {
        $roles = Role::withCount('users')->orderByDesc('is_system')->orderBy('id')->get();

        return RoleResource::collection($roles)->additional([
            'status' => true,
            // Frontend chaile ekhan thekei permission list nite pare
            'available_permissions' => config('permissions.keys', []),
        ]);
    }

    /**
     * POST /api/admin/roles
     * Body: name, permissions[]
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = DB::transaction(fn () => Role::create([
            'name' => $request->input('name'),
            'slug' => Role::uniqueSlug($request->input('name')),
            'permissions' => array_values(array_unique($request->input('permissions'))),
            'is_system' => false,
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Role created successfully',
            'data' => new RoleResource($role->loadCount('users')),
        ], 201);
    }

    /**
     * GET /api/admin/roles/{id}
     */
    public function show(Role $role): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => new RoleResource($role->loadCount('users')),
        ]);
    }

    /**
     * PUT /api/admin/roles/{id}   (form-data hole POST + _method=PUT)
     * Body: name, permissions[]
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        if ($role->is_system || $role->isFullAccess()) {
            return $this->fail('This role always has full access and cannot be edited.');
        }

        DB::transaction(function () use ($request, $role) {
            $role->update([
                'name' => $request->input('name'),
                'slug' => $role->name === $request->input('name')
                    ? $role->slug
                    : Role::uniqueSlug($request->input('name'), $role->id),
                'permissions' => array_values(array_unique($request->input('permissions'))),
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Role updated successfully',
            'data' => new RoleResource($role->fresh()->loadCount('users')),
        ]);
    }

    /**
     * DELETE /api/admin/roles/{id}
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system || $role->isFullAccess()) {
            return $this->fail('This role cannot be deleted.');
        }

        $count = $role->users()->count();
        if ($count > 0) {
            return $this->fail("{$count} user(s) have this role. Move them to another role first.");
        }

        $role->delete();

        return response()->json([
            'status' => true,
            'message' => 'Role deleted successfully',
        ]);
    }

    private function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message], $status);
    }
}
