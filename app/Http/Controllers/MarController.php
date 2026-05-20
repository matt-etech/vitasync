<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\MedicationAdministration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class MarController extends Controller
{
    public function __invoke(): View
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
}
