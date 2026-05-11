<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CarerTasksRequest;
use App\Models\CarePlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class CarerTasksController extends Controller
{
    public function __invoke(CarerTasksRequest $request): JsonResponse
    {
        $carer = User::query()
            ->with('roles')
            ->findOrFail($request->integer('carer_id'));

        if (! $carer->is_active || ! $carer->roles->contains(fn ($role): bool => $role->name === 'Carer' && $role->is_active)) {
            abort(403, 'This endpoint is only available to active carers.');
        }

        if (! $carer->home_id) {
            return response()->json(['tasks' => []]);
        }

        $carePlans = CarePlan::query()
            ->with('client:id,home_id,first_name,last_name,status')
            ->where('home_id', $carer->home_id)
            ->where('status', 'active')
            ->whereHas('client', fn ($query) => $query->where('status', 'active'))
            ->latest('start_date')
            ->get();

        return response()->json([
            'tasks' => $carePlans
                ->flatMap(fn (CarePlan $carePlan): Collection => $this->tasksForCarePlan($carePlan))
                ->values(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function tasksForCarePlan(CarePlan $carePlan): Collection
    {
        return collect([
            $this->taskPayload($carePlan, 'personal_care', 'Personal care', $carePlan->personal_care_level, $carePlan->personal_care_support),
            $this->taskPayload($carePlan, 'mobility', 'Mobility', $carePlan->mobility_level, $carePlan->mobility_support),
            $this->taskPayload($carePlan, 'nutrition', 'Nutrition and hydration', $carePlan->nutrition_support_level, $carePlan->nutrition_hydration_support),
            $this->taskPayload($carePlan, 'medication', 'Medication', $carePlan->medication_support_level, $carePlan->medication_support),
            $this->taskPayload($carePlan, 'communication', 'Communication', $carePlan->communication_support_level, $carePlan->communication_support),
            $this->taskPayload($carePlan, 'risk', 'Risk management', $carePlan->risk_level, $carePlan->risk_management),
        ])->filter();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function taskPayload(CarePlan $carePlan, string $sectionKey, string $section, ?string $level, ?string $instructions): ?array
    {
        if (blank($instructions) && blank($level)) {
            return null;
        }

        return [
            'id' => "{$carePlan->id}:{$sectionKey}",
            'client_name' => $carePlan->client->fullName(),
            'care_plan_title' => $carePlan->title,
            'section' => $section,
            'title' => $level ?: $section,
            'instructions' => $instructions ?: 'Follow the current care plan instructions.',
            'risk_level' => $carePlan->risk_level,
            'status' => 'pending',
        ];
    }
}
