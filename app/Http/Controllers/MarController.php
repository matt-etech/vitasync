<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\MedicationAdministration;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarController extends Controller
{
    public function index(): View
    {
        $administrations = MedicationAdministration::query()
            ->with(['client.home', 'carer', 'visit.carePlan'])
            ->latest('administered_at')
            ->latest()
            ->get();

        $visits = Visit::query()
            ->with(['client.home', 'carePlan', 'assignedWorker', 'medicationAdministrations' => fn ($query) => $query->latest('administered_at')->latest()])
            ->whereHas('carePlan', function (Builder $query): void {
                $query
                    ->whereNotNull('medication_support_level')
                    ->orWhereNotNull('medication_support');
            })
            ->latest('scheduled_start_at')
            ->get();

        return view('mar.index', [
            'administrations' => $administrations,
            'visits' => $visits,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'visit_id' => ['required', 'integer', 'exists:visits,id'],
            'medication_name' => ['required', 'string', 'max:255'],
            'dose' => ['nullable', 'string', 'max:120'],
            'route' => ['nullable', 'string', 'max:120'],
            'outcome' => ['required', 'string', 'in:administered,refused,missed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $visit = Visit::query()->with(['client.home', 'carePlan', 'assignedWorker'])->findOrFail((int) $validated['visit_id']);

        $administration = MedicationAdministration::create([
            'visit_id' => $visit->id,
            'client_id' => $visit->client_id,
            'carer_id' => $visit->assigned_user_id,
            'care_plan_id' => $visit->care_plan_id,
            'medication_name' => $validated['medication_name'],
            'dose' => $validated['dose'] ?? null,
            'route' => $validated['route'] ?? null,
            'outcome' => $validated['outcome'],
            'notes' => $validated['notes'] ?? null,
            'administered_at' => now(),
        ]);

        $actor = auth()->user();
        $carerName = $visit->assignedWorker?->name ?? 'Unassigned carer';

        $auditLogger->log('medication.administered', [
            'actor_id' => $actor?->id,
            'auditable' => $administration,
            'event' => 'Medication administration',
            'friendly_action' => $administration->outcome === 'administered' ? 'recorded medication administered for' : 'recorded medication '.$administration->outcome.' for',
            'friendly_subject' => $visit->client->fullName(),
            'friendly_summary' => ($actor?->name ?? 'System')." recorded medication {$administration->outcome} for {$visit->client->fullName()} on {$carerName}'s visit.",
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
                'Carer' => $carerName,
                'Visit' => $visit->title,
            ],
        ]);

        return redirect()->route('mar.index')->with('status', 'Medication administration added.');
    }
}
