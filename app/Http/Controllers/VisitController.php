<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVisitRequest;
use App\Http\Requests\UpdateVisitRequest;
use App\Models\CarePlan;
use App\Models\Client;
use App\Models\Home;
use App\Models\User;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function index(): View
    {
        $defaultVisitStart = now()->addMinutes(15)->startOfMinute();

        return view('visits.index', [
            'visits' => Visit::with(['client.home', 'carePlan', 'assignedWorker', 'home'])
                ->latest('scheduled_start_at')
                ->get(),
            'visit' => new Visit([
                'scheduled_start_at' => $defaultVisitStart,
                'scheduled_end_at' => $defaultVisitStart->copy()->addHour(),
                'status' => 'scheduled',
            ]),
            'clients' => Client::with(['home', 'carePlans' => fn ($query) => $query->where('status', 'active')->latest('start_date')])
                ->where('status', 'active')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'workers' => User::with(['home', 'carerProfile.trainingRecords', 'assignedVisits'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreVisitRequest $request): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));

        Visit::create(array_merge($request->validated(), [
            'home_id' => $client->home_id,
        ]));

        return $this->redirectAfterSave($request->input('return_to_client_id'), 'Visit booked.');
    }

    public function update(UpdateVisitRequest $request, Visit $visit): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));

        $visit->update(array_merge($request->validated(), [
            'home_id' => $client->home_id,
        ]));

        return $this->redirectAfterSave($request->input('return_to_client_id'), 'Visit updated.');
    }

    public function destroy(Visit $visit, AuditLogger $auditLogger): RedirectResponse
    {
        $wasCancelled = $visit->status === 'cancelled';
        $visit->update([
            'status' => $visit->status === 'cancelled' ? 'scheduled' : 'cancelled',
        ]);

        $auditLogger->log($wasCancelled ? 'visit.restored' : 'visit.removed_from_schedule', [
            'auditable' => $visit,
            'event' => 'Visit',
            'new_values' => ['status' => $visit->status],
            'friendly_summary' => $wasCancelled
                ? 'Restored a cancelled visit to the schedule.'
                : 'Removed a visit from the active schedule.',
        ]);

        return redirect()->route('visits.index')->with('status', $visit->status === 'cancelled' ? 'Visit removed from active schedule.' : 'Visit restored to scheduled.');
    }

    private function redirectAfterSave(mixed $clientId, string $message): RedirectResponse
    {
        if ($clientId && Client::whereKey($clientId)->exists()) {
            return redirect()->route('clients.show', $clientId)->with('status', $message);
        }

        return redirect()->route('visits.index')->with('status', $message);
    }
}
