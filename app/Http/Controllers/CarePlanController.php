<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarePlanRequest;
use App\Http\Requests\UpdateCarePlanRequest;
use App\Models\CarePlan;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CarePlanController extends Controller
{
    public function index(): View
    {
        return view('care-plans.index', [
            'carePlans' => CarePlan::with(['client.home', 'home'])
                ->whereHas('client', fn ($query) => $query->where('status', 'active'))
                ->latest('start_date')
                ->get(),
            'clients' => Client::with('home')->where('status', 'active')->orderBy('last_name')->orderBy('first_name')->get(),
            'carePlan' => new CarePlan(['start_date' => now()->toDateString(), 'status' => 'draft']),
        ]);
    }

    public function store(StoreCarePlanRequest $request): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));

        CarePlan::create(array_merge($request->validated(), [
            'home_id' => $client->home_id,
        ]));

        return $this->redirectAfterSave($request->input('return_to_client_id'), 'Care plan created.');
    }

    public function update(UpdateCarePlanRequest $request, CarePlan $carePlan): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));

        $carePlan->update(array_merge($request->validated(), [
            'home_id' => $client->home_id,
        ]));

        return $this->redirectAfterSave($request->input('return_to_client_id'), 'Care plan updated.');
    }

    public function destroy(CarePlan $carePlan): RedirectResponse
    {
        $carePlan->update([
            'status' => $carePlan->status === 'inactive' ? 'active' : 'inactive',
        ]);

        return redirect()->route('care-plans.index')->with('status', $carePlan->status === 'inactive' ? 'Care plan disabled.' : 'Care plan activated.');
    }

    private function redirectAfterSave(mixed $clientId, string $message): RedirectResponse
    {
        if ($clientId && Client::whereKey($clientId)->exists()) {
            return redirect()->route('clients.show', $clientId)->with('status', $message);
        }

        return redirect()->route('care-plans.index')->with('status', $message);
    }
}
