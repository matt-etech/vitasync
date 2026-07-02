<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitAssignmentGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('status', 'active')],
            'care_plan_id' => ['nullable', 'integer', 'exists:care_plans,id'],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'scheduled_start_at' => ['required', 'date'],
            'scheduled_end_at' => ['required', 'date', 'after:scheduled_start_at'],
            'status' => ['required', Rule::in(['scheduled', 'in_progress', 'completed', 'missed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $clientId = $this->integer('client_id');
            $carePlanId = $this->integer('care_plan_id');
            $assignedUserId = $this->integer('assigned_user_id');

            if ($clientId <= 0) {
                return;
            }

            $client = Client::find($clientId);

            if ($client === null) {
                return;
            }

            if ($carePlanId > 0) {
                $matchesClient = $client->carePlans()
                    ->whereKey($carePlanId)
                    ->exists();

                if (! $matchesClient) {
                    $validator->errors()->add('care_plan_id', 'The selected care plan must belong to the selected client.');
                }
            }

            try {
                $scheduledStartAt = CarbonImmutable::parse((string) $this->input('scheduled_start_at'));
                $scheduledEndAt = CarbonImmutable::parse((string) $this->input('scheduled_end_at'));
            } catch (\Throwable) {
                return;
            }

            $this->validateVisitWindowAgainstClock($validator, $scheduledStartAt, $scheduledEndAt);

            if ($assignedUserId <= 0) {
                return;
            }

            $worker = User::find($assignedUserId);

            if ($worker === null) {
                return;
            }

            $visit = $this->route('visit');
            $ignoreVisitId = $visit instanceof Visit ? $visit->id : null;
            $violations = app(VisitAssignmentGuard::class)->violations(
                worker: $worker,
                homeId: (int) $client->home_id,
                scheduledStartAt: $scheduledStartAt,
                scheduledEndAt: $scheduledEndAt,
                ignoreVisitId: $ignoreVisitId,
            );

            foreach ($violations as $violation) {
                $validator->errors()->add('assigned_user_id', $violation);
            }
        });
    }

    private function validateVisitWindowAgainstClock($validator, CarbonImmutable $scheduledStartAt, CarbonImmutable $scheduledEndAt): void
    {
        $now = Carbon::now();
        $status = (string) $this->input('status');

        if ($status === 'scheduled' && $scheduledStartAt->lessThan($now)) {
            $validator->errors()->add('scheduled_start_at', 'Visits cannot be scheduled in the past.');
        }

        if ($status !== 'in_progress') {
            return;
        }

        if ($scheduledStartAt->greaterThan($now)) {
            $validator->errors()->add('status', 'A visit cannot be set in progress before its scheduled start time.');
        }

        if ($scheduledEndAt->lessThanOrEqualTo($now)) {
            $validator->errors()->add('status', 'A visit cannot be set in progress after its scheduled end time has passed.');
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'client',
            'care_plan_id' => 'care plan',
            'assigned_user_id' => 'assigned worker',
            'scheduled_start_at' => 'scheduled start',
            'scheduled_end_at' => 'scheduled end',
        ];
    }
}
