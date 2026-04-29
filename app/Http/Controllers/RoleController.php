<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::with('permissions')->withCount(['permissions', 'users'])->orderBy('name')->get(),
            'permissions' => Permission::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('roles.create', [
            'role' => new Role(['is_active' => true]),
            'permissions' => Permission::where('is_active', true)->orderBy('name')->get(),
            'selectedPermissions' => [],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(array_merge($request->safe()->only(['name', 'description']), [
            'is_active' => $request->boolean('is_active', true),
        ]));
        $role->permissions()->sync($request->validated('permissions', []));
        app(AuditLogger::class)->log('role_permissions_synced', [
            'auditable' => $role,
            'event' => 'Role',
            'new_values' => [
                'permissions' => $role->permissions()->pluck('permissions.id')->all(),
            ],
        ]);

        return redirect()->route('roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('roles.edit', [
            'role' => $role,
            'permissions' => Permission::where('is_active', true)->orderBy('name')->get(),
            'selectedPermissions' => $role->permissions->pluck('id')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $previousPermissions = $role->permissions()->pluck('permissions.id')->all();
        $role->update(array_merge($request->safe()->only(['name', 'description']), [
            'is_active' => $request->boolean('is_active'),
        ]));
        $role->permissions()->sync($request->validated('permissions', []));
        app(AuditLogger::class)->log('role_permissions_synced', [
            'auditable' => $role,
            'event' => 'Role',
            'old_values' => [
                'permissions' => $previousPermissions,
            ],
            'new_values' => [
                'permissions' => $role->permissions()->pluck('permissions.id')->all(),
            ],
        ]);

        return redirect()->route('roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->update(['is_active' => ! $role->is_active]);

        return redirect()->route('roles.index')->with('status', $role->is_active ? 'Role activated.' : 'Role disabled.');
    }
}
