<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CarerClientsRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CarerClientsController extends Controller
{
    public function __invoke(CarerClientsRequest $request): JsonResponse
    {
        $carer = User::query()
            ->with(['home', 'roles'])
            ->findOrFail($request->integer('carer_id'));

        if (! $carer->is_active || ! $carer->roles->contains(fn ($role): bool => $role->name === 'Carer' && $role->is_active)) {
            abort(403, 'This endpoint is only available to active carers.');
        }

        if (! $carer->home_id) {
            return response()->json(['clients' => []]);
        }

        $clients = Client::query()
            ->with('home:id,name')
            ->where('home_id', $carer->home_id)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'clients' => $clients->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->fullName(),
                'address' => $client->address,
                'phone' => $client->phone,
                'email' => $client->email,
                'latitude' => $client->getAttribute('latitude'),
                'longitude' => $client->getAttribute('longitude'),
                'geofence_radius_meters' => $client->getAttribute('geofence_radius_meters'),
                'status' => $client->status,
                'onboarding_status' => $client->onboarding_status,
                'home_name' => $client->home?->name,
            ])->values(),
        ]);
    }
}
