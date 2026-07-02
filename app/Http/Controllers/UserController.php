<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Home;
use App\Models\LoginHistory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::with(['home', 'roles', 'permissions', 'latestLogin', 'loginHistories'])->orderBy('name')->get(),
            'newUser' => new User(['is_active' => true]),
            'homes' => Home::where('status', 'active')->orderBy('name')->get(),
            'roles' => $this->assignableRoles()->get(),
            'permissions' => Permission::where('is_active', true)->orderBy('name')->get(),
            'selectedRoles' => [],
            'selectedPermissions' => [],
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'user' => new User(),
            'homes' => Home::where('status', 'active')->orderBy('name')->get(),
            'roles' => $this->assignableRoles()->get(),
            'permissions' => Permission::where('is_active', true)->orderBy('name')->get(),
            'selectedRoles' => [],
            'selectedPermissions' => [],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $attributes = Arr::only($validated, ['name', 'email', 'password', 'home_id', 'job_title', 'phone']);
        $attributes['is_active'] = $request->boolean('is_active', true);

        $user = User::create($attributes);
        $user->roles()->sync($validated['roles'] ?? []);
        $user->permissions()->sync($validated['permissions'] ?? []);
        app(AuditLogger::class)->log('user_access_synced', [
            'auditable' => $user,
            'event' => 'User',
            'new_values' => [
                'roles' => $user->roles()->pluck('roles.id')->all(),
                'permissions' => $user->permissions()->pluck('permissions.id')->all(),
            ],
        ]);

        return redirect()->route('users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        $user->load(['roles', 'permissions']);

        return view('users.edit', [
            'user' => $user,
            'homes' => Home::where('status', 'active')->orWhere('id', $user->home_id)->orderBy('name')->get(),
            'roles' => $this->assignableRoles()
                ->orWhere(fn ($query) => $query->whereIn('id', $user->roles->where('name', '!=', 'Carer')->pluck('id')))
                ->orderBy('name')
                ->get(),
            'permissions' => Permission::where('is_active', true)->orWhereIn('id', $user->permissions->pluck('id'))->orderBy('name')->get(),
            'selectedRoles' => $user->roles->pluck('id')->all(),
            'selectedPermissions' => $user->permissions->pluck('id')->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $previousRoles = $user->roles()->pluck('roles.id')->all();
        $previousPermissions = $user->permissions()->pluck('permissions.id')->all();
        $validated = $request->validated();
        $attributes = Arr::only($validated, ['name', 'email', 'home_id', 'job_title', 'phone']);
        $attributes['is_active'] = $request->boolean('is_active');

        if (! empty($validated['password'])) {
            $attributes['password'] = $validated['password'];
        }

        $user->update($attributes);
        $user->roles()->sync($this->rolesPreservingExistingCarerRole($validated['roles'] ?? [], $previousRoles));
        $user->permissions()->sync($validated['permissions'] ?? []);
        app(AuditLogger::class)->log('user_access_synced', [
            'auditable' => $user,
            'event' => 'User',
            'old_values' => [
                'roles' => $previousRoles,
                'permissions' => $previousPermissions,
            ],
            'new_values' => [
                'roles' => $user->roles()->pluck('roles.id')->all(),
                'permissions' => $user->permissions()->pluck('permissions.id')->all(),
            ],
        ]);

        return redirect()->route('users.index')->with('status', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        return redirect()->route('users.index')->with('status', $user->is_active ? 'User activated.' : 'User disabled.');
    }

    public function resetLoginHistory(): RedirectResponse
    {
        $deletedCount = LoginHistory::query()->delete();

        app(AuditLogger::class)->log('login_history_reset', [
            'event' => 'Login history',
            'metadata' => [
                'deleted_count' => $deletedCount,
            ],
        ]);

        return redirect()->route('users.index')->with('status', 'Login history reset.');
    }

    private function assignableRoles()
    {
        return Role::query()
            ->where('is_active', true)
            ->where('name', '!=', 'Carer')
            ->orderBy('name');
    }

    /**
     * @param array<int|string> $roleIds
     * @param array<int> $previousRoleIds
     * @return array<int>
     */
    private function rolesPreservingExistingCarerRole(array $roleIds, array $previousRoleIds): array
    {
        $carerRoleId = Role::where('name', 'Carer')->value('id');

        if ($carerRoleId && in_array((int) $carerRoleId, $previousRoleIds, true)) {
            $roleIds[] = (int) $carerRoleId;
        }

        return collect($roleIds)->map(fn ($roleId): int => (int) $roleId)->unique()->values()->all();
    }
}
