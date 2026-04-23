<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHomeRequest;
use App\Http\Requests\UpdateHomeRequest;
use App\Models\Home;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('homes.index', [
            'homes' => Home::with('manager')->withCount('users')->orderBy('name')->get(),
            'newHome' => new Home(['country' => 'United Kingdom', 'status' => 'active']),
            'managers' => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('homes.create', [
            'home' => new Home(['country' => 'United Kingdom', 'status' => 'active']),
            'managers' => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreHomeRequest $request): RedirectResponse
    {
        $attributes = Arr::except($request->validated(), ['logo']);

        if ($request->hasFile('logo')) {
            $attributes['logo_path'] = $request->file('logo')->store('home-logos', 'public');
        }

        $home = Home::create($attributes);
        $this->syncManagerMembership($home);

        return redirect()->route('homes.index')->with('status', 'Home created.');
    }

    public function edit(Home $home): View
    {
        return view('homes.edit', [
            'home' => $home,
            'managers' => User::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateHomeRequest $request, Home $home): RedirectResponse
    {
        $attributes = Arr::except($request->validated(), ['logo', 'remove_logo']);

        if ($request->boolean('remove_logo') && $home->logo_path !== null) {
            Storage::disk('public')->delete($home->logo_path);
            $attributes['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($home->logo_path !== null) {
                Storage::disk('public')->delete($home->logo_path);
            }

            $attributes['logo_path'] = $request->file('logo')->store('home-logos', 'public');
        }

        $home->update($attributes);
        $this->syncManagerMembership($home);

        return redirect()->route('homes.index')->with('status', 'Home updated.');
    }

    public function destroy(Home $home): RedirectResponse
    {
        $home->update(['status' => $home->status === 'active' ? 'inactive' : 'active']);

        return redirect()->route('homes.index')->with('status', $home->status === 'active' ? 'Home activated.' : 'Home disabled.');
    }

    private function syncManagerMembership(Home $home): void
    {
        if ($home->manager_id === null) {
            return;
        }

        $manager = User::find($home->manager_id);

        if ($manager === null) {
            return;
        }

        $manager->update([
            'home_id' => $home->id,
            'job_title' => $manager->job_title ?: 'Home Manager',
            'is_active' => true,
        ]);

        $managerRole = Role::where('name', 'Home Manager')->first();

        if ($managerRole !== null) {
            $manager->roles()->syncWithoutDetaching([$managerRole->id]);
        }
    }
}
