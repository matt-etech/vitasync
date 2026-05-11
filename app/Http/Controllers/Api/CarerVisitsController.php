<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CarerVisitsRequest;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitTimeMonitor;
use Illuminate\Http\JsonResponse;

class CarerVisitsController extends Controller
{
    public function __invoke(CarerVisitsRequest $request, VisitTimeMonitor $visitTimeMonitor): JsonResponse
    {
        $carer = User::query()
            ->with('roles')
            ->findOrFail($request->integer('carer_id'));

        if (! $carer->is_active || ! $carer->roles->contains(fn ($role): bool => $role->name === 'Carer' && $role->is_active)) {
            abort(403, 'This endpoint is only available to active carers.');
        }

        $visitTimeMonitor->process();

        $visits = Visit::query()
            ->with(['client:id,home_id,first_name,last_name,address,status', 'assignedWorker:id,name'])
            ->where('assigned_user_id', $carer->id)
            ->whereBetween('scheduled_start_at', [
                now()->subMonthsNoOverflow(2)->startOfMonth(),
                now()->addMonthsNoOverflow(2)->endOfMonth(),
            ])
            ->orderBy('scheduled_start_at')
            ->get([
                'id',
                'client_id',
                'assigned_user_id',
                'scheduled_start_at',
                'scheduled_end_at',
                'status',
            ]);

        return response()->json([
            'visits' => $visits->map(fn (Visit $visit): array => [
                'id' => $visit->id,
                'date' => $visit->scheduled_start_at->toDateString(),
                'day' => $visit->scheduled_start_at->format('d/m/Y'),
                'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
                'scheduled_end_at' => $visit->scheduled_end_at?->toIso8601String(),
                'client_name' => $visit->client->fullName(),
                'assigned_worker_name' => $visit->assignedWorker?->name,
                'time_window' => $visit->scheduled_start_at->format('H:i').' - '.$visit->scheduled_end_at->format('H:i'),
                'status' => $visit->status,
            ])->values(),
        ]);
    }
}
