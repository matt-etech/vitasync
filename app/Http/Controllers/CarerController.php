<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarerRequest;
use App\Http\Requests\UpdateCarerRequest;
use App\Models\CarerProfile;
use App\Models\Home;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CarerController extends Controller
{
    public function create(): View
    {
        return view('carers.create', [
            'carer' => new User([
                'job_title' => 'Carer',
                'is_active' => false,
            ]),
            'homes' => Home::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function index(): View
    {
        $carers = $this->carerQuery()
            ->with(['home', 'roles', 'carerProfile.trainingRecords'])
            ->withCount('assignedVisits')
            ->orderBy('name')
            ->get();

        $carerRole = $this->carerRole();
        $carers
            ->filter(fn (User $carer): bool => ! $carer->roles->contains('id', $carerRole->id))
            ->each(fn (User $carer): mixed => $carer->roles()->syncWithoutDetaching([$carerRole->id]));

        return view('carers.index', [
            'carers' => $carers,
            'newCarer' => new User([
                'job_title' => 'Carer',
                'is_active' => false,
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
            'carerProfile.assignedHome',
            'carerProfile.trainingRecords',
            'assignedVisits' => fn ($query) => $query
                ->with(['client.home', 'carePlan'])
                ->latest('scheduled_start_at'),
        ]);

        return view('carers.show', [
            'carer' => $carer,
            'homes' => Home::where('status', 'active')->orWhere('id', $carer->home_id)->orderBy('name')->get(),
        ]);
    }

    public function edit(User $carer): View
    {
        abort_unless($this->isCarer($carer), 404);

        return view('carers.edit', [
            'carer' => $carer->load(['home', 'roles']),
            'homes' => Home::where('status', 'active')->orWhere('id', $carer->home_id)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCarerRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $attributes = Arr::only($validated, ['name', 'email', 'password', 'home_id', 'job_title', 'phone']);
        $attributes['is_active'] = false;
        $attributes['job_title'] = $attributes['job_title'] ?: 'Carer';

        $carer = DB::transaction(function () use ($attributes): User {
            $carer = User::create($attributes);
            $carer->roles()->syncWithoutDetaching([$this->carerRole()->id]);
            $carer->carerProfile()->firstOrCreate([], [
                'status' => CarerProfile::STATUS_ONBOARDING,
            ]);

            return $carer;
        });

        return redirect()->route('carers.assessments.edit', $carer)->with('status', 'Carer login created. Complete onboarding assessment before submission.');
    }

    public function update(UpdateCarerRequest $request, User $carer): RedirectResponse
    {
        abort_unless($this->isCarer($carer), 404);

        $validated = $request->validated();
        $attributes = Arr::only($validated, ['name', 'email', 'home_id', 'job_title', 'phone']);
        $attributes['is_active'] = $request->boolean('is_active');
        $attributes['job_title'] = $attributes['job_title'] ?: 'Carer';

        if (filled($validated['password'] ?? null)) {
            $attributes['password'] = $validated['password'];
        }

        if (($attributes['is_active'] ?? false) && ! $this->passesCriticalValidation($carer)) {
            return redirect()
                ->route('carers.show', $carer)
                ->withErrors(['assessment' => 'Cannot enable login until critical validation passes: '.$this->criticalValidationMessage($carer)]);
        }

        DB::transaction(function () use ($carer, $attributes): void {
            $carer->update($attributes);
            $carer->roles()->syncWithoutDetaching([$this->carerRole()->id]);

            if ($carer->carerProfile) {
                $carer->carerProfile->update([
                    'account_status' => $carer->is_active ? 'active' : 'suspended',
                ]);
            }
        });

        return redirect()->route('carers.show', $carer)->with('status', 'Carer profile and login account updated.');
    }

    public function destroy(User $carer): RedirectResponse
    {
        abort_unless($this->isCarer($carer), 404);

        if (! $carer->is_active && ! $this->passesCriticalValidation($carer)) {
            return redirect()
                ->route('carers.index')
                ->withErrors(['assessment' => 'Cannot activate carer until critical validation passes: '.$this->criticalValidationMessage($carer)]);
        }

        $carer->update(['is_active' => ! $carer->is_active]);
        $carer->carerProfile?->update([
            'account_status' => $carer->is_active ? 'active' : 'suspended',
        ]);

        return redirect()->route('carers.index')->with('status', $carer->is_active ? 'Carer activated.' : 'Carer disabled.');
    }

    private function passesCriticalValidation(User $carer): bool
    {
        return $this->criticalValidationFailures($carer) === [];
    }

    /**
     * @return list<string>
     */
    private function criticalValidationFailures(User $carer): array
    {
        $profile = $carer->carerProfile()->with('trainingRecords')->first();

        return $profile?->criticalValidationFailures() ?? ['Carer onboarding assessment is required.'];
    }

    private function criticalValidationMessage(User $carer): string
    {
        return implode(' ', $this->criticalValidationFailures($carer));
    }

    private function carerQuery()
    {
        return User::where(function ($query): void {
            $query
                ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'Carer'))
                ->orWhere('job_title', 'Carer');
        });
    }

    private function carerRole(): Role
    {
        $role = Role::firstOrCreate(
            ['name' => 'Carer'],
            [
                'description' => 'Delivers scheduled care visits and records EVV activity.',
                'is_active' => true,
            ],
        );

        if (! $role->is_active) {
            $role->update(['is_active' => true]);
        }

        return $role;
    }

    private function isCarer(User $user): bool
    {
        return $user->roles()->where('name', 'Carer')->exists() || $user->job_title === 'Carer';
    }
}
