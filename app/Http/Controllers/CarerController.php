<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarerRequest;
use App\Http\Requests\UpdateCarerRequest;
use App\Models\Client;
use App\Models\CarerProfile;
use App\Models\FamilyPortalMessage;
use App\Models\Home;
use App\Models\MedicationAdministration;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                ->with(['client.home', 'carePlan', 'medicationAdministrations' => fn ($recordQuery) => $recordQuery->latest('administered_at')->latest()])
                ->latest('scheduled_start_at'),
        ]);

        $messageClients = $carer->assignedVisits
            ->pluck('client')
            ->filter()
            ->unique('id')
            ->sortBy(fn (Client $client): string => $client->fullName())
            ->values();

        $familyMessages = FamilyPortalMessage::query()
            ->with(['client', 'sender'])
            ->whereIn('client_id', $messageClients->pluck('id'))
            ->where('sent_by_user_id', $carer->id)
            ->latest('sent_at')
            ->latest()
            ->get();

        $marAdministrations = MedicationAdministration::query()
            ->with(['client.home', 'visit'])
            ->where('carer_id', $carer->id)
            ->latest('administered_at')
            ->latest()
            ->get();

        $marVisits = $carer->assignedVisits
            ->filter(fn ($visit): bool => filled($visit->carePlan?->medication_support_level) || filled($visit->carePlan?->medication_support))
            ->values();

        return view('carers.show', [
            'carer' => $carer,
            'homes' => Home::where('status', 'active')->orWhere('id', $carer->home_id)->orderBy('name')->get(),
            'messageClients' => $messageClients,
            'familyMessages' => $familyMessages,
            'marAdministrations' => $marAdministrations,
            'marVisits' => $marVisits,
        ]);
    }

    public function sendFamilyMessage(Request $request, User $carer, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($this->isCarer($carer), 404);

        $validated = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $client = Client::query()->with('home')->findOrFail((int) $validated['client_id']);

        if (! $this->carerCanMessageClient($carer, $client)) {
            return redirect()
                ->route('carers.show', $carer)
                ->withErrors(['client_id' => 'Choose a client assigned to this carer.']);
        }

        $message = FamilyPortalMessage::create([
            'client_id' => $client->id,
            'sent_by_user_id' => $carer->id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'visible_to_family' => true,
            'sent_at' => now(),
        ]);

        $auditLogger->log('family.staff_message_sent', [
            'actor_id' => $carer->id,
            'auditable' => $message,
            'event' => 'Family message',
            'friendly_action' => 'sent a message to family',
            'friendly_subject' => $client->fullName(),
            'friendly_summary' => "{$carer->name} sent a family portal message about {$client->fullName()}.",
            'metadata' => [
                'Client' => $client->fullName(),
                'Home' => $client->home?->name,
                'Carer' => $carer->name,
                'Subject' => $message->subject,
                'Shown in family portal' => 'Yes, when Messages from staff is allowed for the family member',
            ],
        ]);

        return redirect()
            ->route('carers.show', $carer)
            ->with('status', 'Message sent to the family portal.');
    }

    public function administerMedication(Request $request, User $carer, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($this->isCarer($carer), 404);

        $validated = $request->validate([
            'visit_id' => ['required', 'integer', 'exists:visits,id'],
            'medication_name' => ['required', 'string', 'max:255'],
            'dose' => ['nullable', 'string', 'max:120'],
            'route' => ['nullable', 'string', 'max:120'],
            'outcome' => ['required', 'string', 'in:administered,refused,missed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $visit = Visit::query()->with(['client.home', 'carePlan'])->findOrFail((int) $validated['visit_id']);

        if ((int) $visit->assigned_user_id !== (int) $carer->id) {
            return redirect()
                ->route('carers.show', $carer)
                ->withErrors(['visit_id' => 'Choose a visit assigned to this carer.']);
        }

        if (blank($visit->carePlan?->medication_support_level) && blank($visit->carePlan?->medication_support)) {
            return redirect()
                ->route('carers.show', $carer)
                ->withErrors(['visit_id' => 'Choose a visit with medication support in the care plan.']);
        }

        $administration = MedicationAdministration::create([
            'visit_id' => $visit->id,
            'client_id' => $visit->client_id,
            'carer_id' => $carer->id,
            'care_plan_id' => $visit->care_plan_id,
            'medication_name' => $validated['medication_name'],
            'dose' => $validated['dose'] ?? null,
            'route' => $validated['route'] ?? null,
            'outcome' => $validated['outcome'],
            'notes' => $validated['notes'] ?? null,
            'administered_at' => now(),
        ]);

        $auditLogger->log('medication.administered', [
            'actor_id' => $carer->id,
            'auditable' => $administration,
            'event' => 'Medication administration',
            'friendly_action' => $administration->outcome === 'administered' ? 'administered medication for' : 'recorded medication '.$administration->outcome.' for',
            'friendly_subject' => $visit->client->fullName(),
            'friendly_summary' => $administration->outcome === 'administered'
                ? "{$carer->name} administered medication for {$visit->client->fullName()}."
                : "{$carer->name} recorded medication {$administration->outcome} for {$visit->client->fullName()}.",
            'new_values' => [
                'medication_name' => $administration->medication_name,
                'dose' => $administration->dose,
                'route' => $administration->route,
                'outcome' => $administration->outcome,
                'notes' => $administration->notes,
                'administered_at' => $administration->administered_at,
            ],
            'metadata' => [
                'Client' => $visit->client->fullName(),
                'Home' => $visit->client->home?->name,
                'Carer' => $carer->name,
                'Visit' => $visit->title,
            ],
        ]);

        return redirect()
            ->route('carers.show', $carer)
            ->with('status', 'Medication administration saved.');
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

    private function carerCanMessageClient(User $carer, Client $client): bool
    {
        return $carer->assignedVisits()
            ->where('client_id', $client->id)
            ->exists();
    }
}
