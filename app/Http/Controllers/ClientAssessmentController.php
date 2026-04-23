<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewClientAssessmentRequest;
use App\Http\Requests\UpdateClientAssessmentRequest;
use App\Models\Client;
use App\Models\ClientAssessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientAssessmentController extends Controller
{
    public function edit(Client $client): View
    {
        $assessment = $this->editableAssessmentFor($client)->load($this->sectionRelations());

        return view('clients.assessments.edit', [
            'client' => $client->refresh()->load('home'),
            'assessment' => $assessment,
        ]);
    }

    public function update(UpdateClientAssessmentRequest $request, Client $client): RedirectResponse
    {
        DB::transaction(function () use ($request, $client): void {
            $assessment = $this->editableAssessmentFor($client);
            $validated = $request->validated();

            $assessment->update(array_merge(data_get($validated, 'assessment', []), [
                'status' => ClientAssessment::STATUS_ONBOARDING,
                'submitted_at' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'review_notes' => null,
            ]));

            $this->syncSection($assessment, 'needs', data_get($validated, 'needs', []));
            $this->syncSection($assessment, 'functional', data_get($validated, 'functional', []));
            $this->syncSection($assessment, 'medical', data_get($validated, 'medical', []));
            $this->syncSection($assessment, 'mentalCapacity', data_get($validated, 'mental_capacity', []));
            $this->syncSection($assessment, 'risk', data_get($validated, 'risk', []));
            $this->syncSection($assessment, 'communication', data_get($validated, 'communication', []));
            $this->syncSection($assessment, 'equality', data_get($validated, 'equality', []));
            $this->syncSection($assessment, 'social', data_get($validated, 'social', []));
            $this->syncSection($assessment, 'environmental', data_get($validated, 'environmental', []));

            $client->update([
                'onboarding_status' => Client::ONBOARDING_STATUS_ONBOARDING,
                'submitted_at' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'review_notes' => null,
            ]);
        });

        return redirect()->route('clients.assessments.edit', $client)->with('status', 'Client assessment saved.');
    }

    public function submit(Client $client): RedirectResponse
    {
        DB::transaction(function () use ($client): void {
            $assessment = $this->editableAssessmentFor($client);

            $assessment->update([
                'status' => ClientAssessment::STATUS_PENDING,
                'submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'review_notes' => null,
            ]);

            $client->update([
                'onboarding_status' => Client::ONBOARDING_STATUS_PENDING,
                'submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'review_notes' => null,
            ]);
        });

        return redirect()->route('clients.assessments.edit', $client)->with('status', 'Client onboarding submitted for review.');
    }

    public function approve(Client $client): RedirectResponse
    {
        DB::transaction(function () use ($client): void {
            $assessment = $this->pendingAssessmentFor($client);

            $assessment->update([
                'status' => ClientAssessment::STATUS_APPROVED,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_notes' => null,
            ]);

            $client->update([
                'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_notes' => null,
            ]);
        });

        return redirect()->route('clients.index')->with('status', 'Client onboarding approved.');
    }

    public function decline(ReviewClientAssessmentRequest $request, Client $client): RedirectResponse
    {
        DB::transaction(function () use ($request, $client): void {
            $assessment = $this->pendingAssessmentFor($client);
            $notes = $request->validated('review_notes');

            $assessment->update([
                'status' => ClientAssessment::STATUS_DECLINED,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_notes' => $notes,
            ]);

            $client->update([
                'onboarding_status' => Client::ONBOARDING_STATUS_DECLINED,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_notes' => $notes,
            ]);
        });

        return redirect()->route('clients.assessments.edit', $client)->with('status', 'Client onboarding declined with review notes.');
    }

    private function editableAssessmentFor(Client $client): ClientAssessment
    {
        $openAssessment = $client->assessments()
            ->where('status', ClientAssessment::STATUS_ONBOARDING)
            ->latest('version')
            ->first();

        if ($openAssessment !== null) {
            return $openAssessment;
        }

        $latest = $this->latestAssessmentFor($client);

        if ($latest === null) {
            $assessment = $client->assessments()->create([
                'assessment_date' => now()->toDateString(),
                'assessment_type' => 'initial',
                'status' => ClientAssessment::STATUS_ONBOARDING,
                'version' => 1,
            ]);

            $this->markClientOnboarding($client);

            return $assessment;
        }

        if ($latest->status === ClientAssessment::STATUS_ONBOARDING) {
            return $latest;
        }

        return DB::transaction(fn (): ClientAssessment => $this->createNewVersionFrom($client, $latest));
    }

    private function pendingAssessmentFor(Client $client): ClientAssessment
    {
        $assessment = $client->assessments()
            ->where('status', ClientAssessment::STATUS_PENDING)
            ->latest('version')
            ->first();

        if ($assessment !== null) {
            return $assessment;
        }

        return $this->latestAssessmentFor($client) ?? $this->editableAssessmentFor($client);
    }

    private function latestAssessmentFor(Client $client): ?ClientAssessment
    {
        return $client->assessments()->latest('version')->first();
    }

    private function createNewVersionFrom(Client $client, ClientAssessment $source): ClientAssessment
    {
        $source->loadMissing($this->sectionRelations());

        $assessment = $client->assessments()->create([
            'version' => ((int) $source->version) + 1,
            'assessment_date' => now()->toDateString(),
            'assessor_name' => $source->assessor_name,
            'assessment_type' => 'review',
            'overall_summary' => $source->overall_summary,
            'overall_risk_level' => $source->overall_risk_level,
            'recommendations' => $source->recommendations,
            'next_review_date' => $source->next_review_date,
            'status' => ClientAssessment::STATUS_ONBOARDING,
        ]);

        foreach ($this->sectionRelations() as $relation) {
            $section = $source->{$relation};

            if ($section === null) {
                continue;
            }

            $assessment->{$relation}()->create(collect($section->getAttributes())
                ->except(['id', 'client_assessment_id', 'created_at', 'updated_at'])
                ->all());
        }

        $this->markClientOnboarding($client);

        return $assessment;
    }

    private function markClientOnboarding(Client $client): void
    {
        $client->update([
            'onboarding_status' => Client::ONBOARDING_STATUS_ONBOARDING,
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'review_notes' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function syncSection(ClientAssessment $assessment, string $relation, array $data): void
    {
        $assessment->{$relation}()->updateOrCreate([], $data);
    }

    /**
     * @return list<string>
     */
    private function sectionRelations(): array
    {
        return [
            'needs',
            'functional',
            'medical',
            'mentalCapacity',
            'risk',
            'communication',
            'equality',
            'social',
            'environmental',
        ];
    }
}
