<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarerRequest;
use App\Http\Requests\UpdateCarerRequest;
use App\Models\Home;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class CarerController extends Controller
{
    public function index(): View
    {
        $carers = $this->carerQuery()
            ->with(['home', 'roles'])
            ->withCount('assignedVisits')
            ->orderBy('name')
            ->get();

        return view('carers.index', [
            'carers' => $carers,
            'newCarer' => new User([
                'job_title' => 'Carer',
                'is_active' => true,
            ]),
            'homes' => Home::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function show(User $carer): View
    {
        abort_unless($this->isCarer($carer), 404);

        $carer->load([
            'home',
            'roles',
            'assignedVisits' => fn ($query) => $query
                ->with(['client.home', 'carePlan'])
                ->latest('scheduled_start_at'),
        ]);

        return view('carers.show', [
            'carer' => $carer,
            'homes' => Home::where('status', 'active')->orWhere('id', $carer->home_id)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCarerRequest $request): RedirectResponse
    {
        $attributes = Arr::only($request->validated(), ['name', 'email', 'password', 'home_id', 'job_title', 'phone']);
        $attributes['is_active'] = $request->boolean('is_active', true);
        $attributes['job_title'] = $attributes['job_title'] ?: 'Carer';

        $carer = User::create($attributes);
        $carer->roles()->syncWithoutDetaching([$this->carerRole()->id]);

        return redirect()->route('carers.index')->with('status', 'Carer created.');
    }

    public function update(UpdateCarerRequest $request, User $carer): RedirectResponse
    {
        abort_unless($this->isCarer($carer), 404);

        $attributes = Arr::only($request->validated(), ['name', 'email', 'home_id', 'job_title', 'phone']);
        $attributes['is_active'] = $request->boolean('is_active');
        $attributes['job_title'] = $attributes['job_title'] ?: 'Carer';

        if (filled($request->validated('password'))) {
            $attributes['password'] = $request->validated('password');
        }

        $carer->update($attributes);
        $carer->roles()->syncWithoutDetaching([$this->carerRole()->id]);

        return redirect()->route('carers.show', $carer)->with('status', 'Carer updated.');
    }

    public function destroy(User $carer): RedirectResponse
    {
        abort_unless($this->isCarer($carer), 404);

        $carer->update(['is_active' => ! $carer->is_active]);

        return redirect()->route('carers.index')->with('status', $carer->is_active ? 'Carer activated.' : 'Carer disabled.');
    }

    private function carerQuery()
    {
        return User::whereHas('roles', fn ($query) => $query->where('name', 'Carer'));
    }

    private function carerRole(): Role
    {
        return Role::firstOrCreate(
            ['name' => 'Carer'],
            ['description' => 'Delivers scheduled care visits and records EVV activity.'],
        );
    }

    private function isCarer(User $user): bool
    {
        return $user->roles()->where('name', 'Carer')->exists();
    }
}
