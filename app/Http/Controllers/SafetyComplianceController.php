<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCapacityReviewRequest;
use App\Http\Requests\StoreConsentRecordRequest;
use App\Http\Requests\StoreIncidentRequest;
use App\Http\Requests\StoreMedicationAdministrationRequest;
use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\StoreRiskReviewRequest;
use App\Http\Requests\StoreSafeguardingCaseRequest;
use App\Models\CapacityReview;
use App\Models\Client;
use App\Models\ConsentRecord;
use App\Models\Incident;
use App\Models\Medication;
use App\Models\MedicationAdministration;
use App\Models\RiskReview;
use App\Models\SafeguardingCase;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SafetyComplianceController extends Controller
{
    public function index(): View
    {
        return view('safety.index', [
            'clients' => Client::with('home')->where('status', 'active')->orderBy('last_name')->orderBy('first_name')->get(),
            'visits' => Visit::with('client')->latest('scheduled_start_at')->limit(100)->get(),
            'riskReviews' => RiskReview::with(['client.home', 'reviewer'])->latest('review_date')->get(),
            'capacityReviews' => CapacityReview::with(['client.home', 'reviewer'])->latest('review_date')->get(),
            'consentRecords' => ConsentRecord::with(['client.home', 'recorder'])->latest('recorded_at')->get(),
            'medications' => Medication::with(['client.home'])->latest()->get(),
            'medicationAdministrations' => MedicationAdministration::with(['client.home', 'medication', 'visit', 'administrator'])->latest('administered_at')->get(),
            'incidents' => Incident::with(['client.home', 'visit', 'reporter', 'safeguardingCase'])->latest('occurred_at')->get(),
            'safeguardingCases' => SafeguardingCase::with(['client.home', 'incident', 'opener'])->latest('opened_at')->get(),
        ]);
    }

    public function storeRisk(StoreRiskReviewRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));
        $record = RiskReview::create($request->validated() + [
            'home_id' => $client->home_id,
            'reviewed_by' => Auth::id(),
        ]);

        $this->logWorkflow($auditLogger, 'risk.review_recorded', $record, 'RiskAssessment');

        return back()->with('status', 'Risk review recorded.');
    }

    public function storeCapacity(StoreCapacityReviewRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));
        $record = CapacityReview::create($request->validated() + [
            'home_id' => $client->home_id,
            'reviewed_by' => Auth::id(),
        ]);

        $this->logWorkflow($auditLogger, 'capacity.review_recorded', $record, 'CapacityAssessment');

        return back()->with('status', 'Capacity review recorded.');
    }

    public function storeConsent(StoreConsentRecordRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));
        $record = ConsentRecord::create($request->validated() + [
            'home_id' => $client->home_id,
            'recorded_by' => Auth::id(),
        ]);

        $this->logWorkflow($auditLogger, 'consent.recorded', $record, 'ConsentRecord');

        return back()->with('status', 'Consent record saved.');
    }

    public function storeMedication(StoreMedicationRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));
        $record = Medication::create($request->validated() + [
            'home_id' => $client->home_id,
        ]);

        $this->logWorkflow($auditLogger, 'medication.created', $record, 'Medication');

        return back()->with('status', 'Medication added to MAR.');
    }

    public function storeMedicationAdministration(StoreMedicationAdministrationRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $medication = Medication::findOrFail($request->validated('medication_id'));
        $record = MedicationAdministration::create($request->validated() + [
            'client_id' => $medication->client_id,
            'administered_by' => Auth::id(),
        ]);

        $this->logWorkflow($auditLogger, 'medication.administration_recorded', $record, 'MedicationAdministration');

        return back()->with('status', 'Medication administration recorded.');
    }

    public function storeIncident(StoreIncidentRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));
        $record = Incident::create($request->validated() + [
            'home_id' => $client->home_id,
            'reported_by' => Auth::id(),
            'safeguarding_required' => (bool) $request->boolean('safeguarding_required'),
        ]);

        $this->logWorkflow($auditLogger, 'incident.recorded', $record, 'Incident');

        if ($record->safeguarding_required) {
            $auditLogger->log('incident.safeguarding_required', [
                'auditable' => $record,
                'event' => 'SafeguardingEscalation',
                'metadata' => [
                    'incident_id' => $record->id,
                    'client_id' => $record->client_id,
                    'severity' => $record->severity,
                    'next_action' => 'Open a safeguarding case linked to this incident.',
                ],
            ]);
        }

        return back()->with('status', 'Incident recorded.');
    }

    public function storeSafeguarding(StoreSafeguardingCaseRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));
        $record = SafeguardingCase::create($request->validated() + [
            'home_id' => $client->home_id,
            'opened_by' => Auth::id(),
        ]);

        $this->logWorkflow($auditLogger, 'safeguarding.case_opened', $record, 'SafeguardingCase');

        return back()->with('status', 'Safeguarding case recorded.');
    }

    private function logWorkflow(AuditLogger $auditLogger, string $action, object $record, string $event): void
    {
        $auditLogger->log($action, [
            'auditable' => $record,
            'event' => $event,
            'metadata' => [
                'client_id' => $record->client_id,
                'home_id' => $record->home_id ?? $record->client?->home_id,
            ],
        ]);
    }
}
