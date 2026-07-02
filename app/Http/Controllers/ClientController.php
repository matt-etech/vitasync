<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\CarePlan;
use App\Models\Client;
use App\Models\Home;
use App\Models\User;
use App\Models\Visit;
use App\Services\AuditLogger;
use App\Services\Geocoding\GeocodingException;
use App\Services\Geocoding\GeocodingProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        return view('clients.index', [
            'clients' => Client::with(['home', 'assessment'])
                ->withExists(['assessments as has_onboarding_assessment' => fn ($query) => $query->where('status', 'onboarding')])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'newClient' => new Client(['status' => 'active', 'onboarding_status' => Client::ONBOARDING_STATUS_ONBOARDING]),
            'homes' => Home::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('clients.create', [
            'client' => new Client(['status' => 'active', 'onboarding_status' => Client::ONBOARDING_STATUS_ONBOARDING]),
            'homes' => Home::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function show(Client $client): View
    {
        $defaultVisitStart = now()->addMinutes(15)->startOfMinute();

        $client->load([
            'home',
            'reviewer',
            'assessment',
            'visits' => fn ($query) => $query->with(['carePlan', 'assignedWorker'])->latest('scheduled_start_at'),
            'carePlans' => fn ($query) => $query->latest('start_date')->latest('id'),
            'assessments' => fn ($query) => $query
                ->with([
                    'reviewer',
                    'needs',
                    'functional',
                    'medical',
                    'mentalCapacity',
                    'risk',
                    'communication',
                    'equality',
                    'social',
                    'environmental',
                ])
                ->latest('version'),
        ]);

        return view('clients.show', [
            'client' => $client,
            'newCarePlan' => new CarePlan([
                'client_id' => $client->id,
                'start_date' => now()->toDateString(),
                'status' => 'draft',
            ]),
            'newVisit' => new Visit([
                'client_id' => $client->id,
                'care_plan_id' => $client->carePlans->first()?->id,
                'scheduled_start_at' => $defaultVisitStart,
                'scheduled_end_at' => $defaultVisitStart->copy()->addHour(),
                'status' => 'scheduled',
            ]),
            'carePlanClients' => collect([$client]),
            'visitClients' => collect([$client]),
            'visitWorkers' => User::with(['home', 'carerProfile.trainingRecords', 'assignedVisits'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreClientRequest $request, GeocodingProvider $geocoding): RedirectResponse
    {
        [$payload, $geocodeMessage] = $this->clientPayload($request->validated(), $geocoding);

        $client = Client::create(array_merge($payload, [
            'onboarding_status' => Client::ONBOARDING_STATUS_ONBOARDING,
        ]));

        $message = 'Client created. Complete onboarding assessments before submission.';

        return redirect()
            ->route('clients.assessments.edit', $client)
            ->with('status', $geocodeMessage ? "{$message} {$geocodeMessage}" : $message);
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', [
            'client' => $client,
            'homes' => Home::where('status', 'active')->orWhere('id', $client->home_id)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client, GeocodingProvider $geocoding): RedirectResponse
    {
        [$payload, $geocodeMessage] = $this->clientPayload($request->validated(), $geocoding);

        $client->update($payload);

        return redirect()
            ->route('clients.index')
            ->with('status', $geocodeMessage ? "Client updated. {$geocodeMessage}" : 'Client updated.');
    }

    public function destroy(Client $client, AuditLogger $auditLogger): RedirectResponse
    {
        $wasActive = $client->status === 'active';
        $client->update(['status' => $client->status === 'active' ? 'inactive' : 'active']);

        $auditLogger->log($wasActive ? 'client.removed_from_workflows' : 'client.restored_to_workflows', [
            'auditable' => $client,
            'event' => 'Client',
            'new_values' => ['status' => $client->status],
            'friendly_summary' => $wasActive
                ? 'Removed a client from active operational workflows.'
                : 'Restored a client to active operational workflows.',
        ]);

        return redirect()->route('clients.index')->with('status', $client->status === 'active' ? 'Client activated.' : 'Client removed from active workflows.');
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{0: array<string, mixed>, 1: string|null}
     */
    private function clientPayload(array $validated, GeocodingProvider $geocoding): array
    {
        if (! Schema::hasColumn('clients', 'latitude')) {
            return [collect($validated)->except(['latitude', 'longitude', 'geofence_radius_meters'])->all(), null];
        }

        if (
            blank($validated['latitude'] ?? null)
            && blank($validated['longitude'] ?? null)
            && filled($validated['address'] ?? null)
        ) {
            try {
                $result = $geocoding->geocode((string) $validated['address']);

                if ($result !== null) {
                    $validated['latitude'] = $result->latitude;
                    $validated['longitude'] = $result->longitude;
                    $validated['geofence_radius_meters'] = $validated['geofence_radius_meters'] ?: 100;

                    return [$validated, 'Maps resolved the visit location. Please verify the pin before relying on auto EVV.'];
                }

                return [$validated, 'Maps could not resolve the address; enter the visit location manually.'];
            } catch (GeocodingException $exception) {
                return [$validated, 'Maps could not resolve the address: '.$exception->getMessage()];
            }
        }

        return [$validated, null];
    }
}
