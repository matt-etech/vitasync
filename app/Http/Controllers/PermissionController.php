<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        return view('permissions.index', [
            'permissions' => Permission::withCount('roles')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('permissions.create', [
            'permission' => new Permission(['is_active' => true]),
        ]);
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        Permission::create(array_merge($request->safe()->only(['name', 'description']), [
            'is_active' => $request->boolean('is_active', true),
        ]));

        return redirect()->route('permissions.index')->with('status', 'Permission created.');
    }

    public function edit(Permission $permission): View
    {
        return view('permissions.edit', [
            'permission' => $permission,
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $permission->update(array_merge($request->safe()->only(['name', 'description']), [
            'is_active' => $request->boolean('is_active'),
        ]));

        return redirect()->route('permissions.index')->with('status', 'Permission updated.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->update(['is_active' => ! $permission->is_active]);

        return redirect()->route('permissions.index')->with('status', $permission->is_active ? 'Permission activated.' : 'Permission disabled.');
    }
}
