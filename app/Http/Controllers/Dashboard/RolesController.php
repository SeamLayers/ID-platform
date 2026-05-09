<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ResponseHelper;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:role.view')->only(['index', 'show']);
        $this->middleware('permission:role.create')->only(['store']);
        $this->middleware('permission:role.update')->only(['update']);
        $this->middleware('permission:role.delete')->only(['destroy']);
    }

    /**
     * List roles
     */
    public function index()
    {
        $roles = Role::with('permissions')->latest()->get();

        return ResponseHelper::success(
            RoleResource::collection($roles),
            __('messages.data_retrieved')
        );
    }

    /**
     * Create role + permissions
     */
    public function store(Request $request)
    {
        // 1. Ensure permissions exist
        if ($request->permissions) {
            foreach ($request->permissions as $perm) {
               Permission::firstOrCreate([
                    'name' => $perm,
                    'guard_name' => 'api',
                ]);
            }
        }

        // 2. Create role
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'api',
        ]);

        // 3. Assign permissions
        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return ResponseHelper::success(
            new RoleResource($role->load('permissions')),
            __('messages.data_saved'),
            201
        );
    }
    /**
     * Show role
     */
    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        $employees = \App\Models\User::role($role->name)->get();

        return ResponseHelper::success(
            new RoleResource($role, $employees),
            __('messages.data_retrieved')
        );
    }
    /**
     * Update role
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // 1. Ensure permissions exist
        if ($request->permissions) {
            foreach ($request->permissions as $perm) {
               Permission::firstOrCreate([
                    'name' => $perm,
                    'guard_name' => 'api',
                ]);
            }
        }

        // 2. Update role
        $role->update([
            'name' => $request->name,
            'guard_name' => 'api',
        ]);

        // 3. Sync permissions
        $role->givePermissionTo($request->permissions);

        return ResponseHelper::success(
            new RoleResource($role->load('permissions')),
            __('messages.data_updated')
        );
    }
    /**
     * Delete role
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return ResponseHelper::success(
            null,
            __('messages.data_deleted')
        );
    }

    /**
     * Assign users to role
     */
    public function assignUsers(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $users = User::whereIn('id', $request->user_ids)->get();

        foreach ($users as $user) {

            $user->syncRoles([$role->name]);
        }

        return ResponseHelper::success(
            $role->load('permissions'),
            __('messages.data_updated')
        );
    }
}
