<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVisitRequest;
use App\Http\Requests\UpdateVisitRequest;
use App\Models\CarePlan;
use App\Models\Client;
use App\Models\Home;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function index(): View
    {
        return view('visits.index', [
            'visits' => Visit::with(['client.home', 'carePlan', 'assignedWorker', 'home'])
                ->latest('scheduled_start_at')
                ->get(),
            'visit' => new Visit([
                'scheduled_start_at' => now()->startOfHour(),
                'scheduled_end_at' => now()->startOfHour()->addHour(),
                'status' => 'scheduled',
            ]),
            'clients' => Client::with(['home', 'carePlans' => fn ($query) => $query->where('status', 'active')->latest('start_date')])
                ->where('status', 'active')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'workers' => User::where('is_active', true)->orderBy('name')->get(),
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

    public function destroy(Visit $visit): RedirectResponse
    {
        $visit->update([
            'status' => $visit->status === 'cancelled' ? 'scheduled' : 'cancelled',
        ]);

        return redirect()->route('visits.index')->with('status', $visit->status === 'cancelled' ? 'Visit cancelled.' : 'Visit restored to scheduled.');
    }

    private function redirectAfterSave(mixed $clientId, string $message): RedirectResponse
    {
        if ($clientId && Client::whereKey($clientId)->exists()) {
            return redirect()->route('clients.show', $clientId)->with('status', $message);
        }

        return redirect()->route('visits.index')->with('status', $message);
    }
}
