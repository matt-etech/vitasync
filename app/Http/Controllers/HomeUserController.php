<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHomeUserRequest;
use App\Http\Requests\UpdateHomeUserRequest;
use App\Models\Home;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class HomeUserController extends Controller
{
    public function index(Home $home): View
    {
        return view('homes.users.index', [
            'home' => $home,
            'users' => $home->users()->with(['roles', 'permissions'])->orderBy('name')->get(),
            'newUser' => new User(['home_id' => $home->id, 'is_active' => true]),
            'roles' => Role::where('is_active', true)->with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::where('is_active', true)->orderBy('name')->get(),
            'selectedRoles' => [],
            'selectedPermissions' => [],
        ]);
    }

    public function create(Home $home): View
    {
        return view('homes.users.create', [
            'home' => $home,
            'user' => new User(['home_id' => $home->id, 'is_active' => true]),
            'roles' => Role::where('is_active', true)->with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::where('is_active', true)->orderBy('name')->get(),
            'selectedRoles' => [],
            'selectedPermissions' => [],
        ]);
    }

    public function store(StoreHomeUserRequest $request, Home $home): RedirectResponse
    {
        $validated = $request->validated();
        $attributes = Arr::only($validated, ['name', 'email', 'password', 'job_title', 'phone']);
        $attributes['home_id'] = $home->id;
        $attributes['is_active'] = $request->boolean('is_active', true);

        $user = User::create($attributes);
        $user->roles()->sync($validated['roles'] ?? []);
        $user->permissions()->sync($validated['permissions'] ?? []);
        $this->syncHomeManager($home, $user);

        return redirect()->route('homes.users.index', $home)->with('status', 'Home user created.');
    }

    public function edit(Home $home, User $user): View
    {
        $this->ensureUserBelongsToHome($home, $user);
        $user->load(['roles', 'permissions']);

        return view('homes.users.edit', [
            'home' => $home,
            'user' => $user,
            'roles' => Role::where('is_active', true)->orWhereIn('id', $user->roles->pluck('id'))->with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::where('is_active', true)->orWhereIn('id', $user->permissions->pluck('id'))->orderBy('name')->get(),
            'selectedRoles' => $user->roles->pluck('id')->all(),
            'selectedPermissions' => $user->permissions->pluck('id')->all(),
        ]);
    }

    public function update(UpdateHomeUserRequest $request, Home $home, User $user): RedirectResponse
    {
        $this->ensureUserBelongsToHome($home, $user);

        $validated = $request->validated();
        $attributes = Arr::only($validated, ['name', 'email', 'job_title', 'phone']);
        $attributes['is_active'] = $request->boolean('is_active');

        if (! empty($validated['password'])) {
            $attributes['password'] = $validated['password'];
        }

        $user->update($attributes);
        $user->roles()->sync($validated['roles'] ?? []);
        $user->permissions()->sync($validated['permissions'] ?? []);
        $this->syncHomeManager($home, $user);

        return redirect()->route('homes.users.index', $home)->with('status', 'Home user updated.');
    }

    public function destroy(Home $home, User $user): RedirectResponse
    {
        $this->ensureUserBelongsToHome($home, $user);

        if ((int) $home->manager_id === (int) $user->id) {
            $home->update(['manager_id' => null]);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return redirect()->route('homes.users.index', $home)->with('status', $user->is_active ? 'Home user activated.' : 'Home user disabled.');
    }

    private function ensureUserBelongsToHome(Home $home, User $user): void
    {
        abort_unless((int) $user->home_id === (int) $home->id, 404);
    }

    private function syncHomeManager(Home $home, User $user): void
    {
        if (! $user->roles()->where('name', 'Home Manager')->exists()) {
            return;
        }

        $home->update(['manager_id' => $user->id]);
    }
}
