<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ThabisileMahlanzaAssessmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $client = DB::table('clients')
                ->where('first_name', 'Thabisile')
                ->first();

            if ($client === null) {
                throw new \RuntimeException('Client Thabisile was not found.');
            }

            $reviewer = DB::table('users')
                ->where('is_active', true)
                ->orderBy('id')
                ->first();

            $assessment = $this->editableAssessmentFor((int) $client->id);
            $clientName = trim($client->first_name.' '.$client->last_name);
            $now = now();
            $timestamp = $now->toDateTimeString();

            DB::table('client_assessments')
                ->where('id', $assessment->id)
                ->update([
                    'assessment_date' => $now->toDateString(),
                    'assessor_name' => 'VitaSync onboarding autofill',
                    'assessment_type' => ((int) $assessment->version) > 1 ? 'review' : 'initial',
                    'overall_summary' => "Initial onboarding assessment completed from the backend for {$clientName}. The record captures daily living support, medication prompting, communication preferences, environmental safety checks, and review requirements so care planning can proceed.",
                    'overall_risk_level' => 'medium',
                    'recommendations' => 'Create an active care plan with morning and evening personal-care prompts, medication support checks, mobility observation, hydration prompts, and escalation instructions for missed medication, falls, or change in presentation.',
                    'next_review_date' => $now->copy()->addMonthsNoOverflow(3)->toDateString(),
                    'status' => 'approved',
                    'submitted_at' => $timestamp,
                    'reviewed_at' => $timestamp,
                    'reviewed_by' => $reviewer?->id,
                    'review_notes' => null,
                    'updated_at' => $timestamp,
                ]);

            $this->syncSection('client_need_assessments', (int) $assessment->id, [
                'physical_needs' => 'Requires routine support with personal care, hygiene prompts, meal preparation checks, hydration prompts, and medication support in line with the care plan.',
                'psychological_needs' => 'Benefits from calm communication, clear explanations before care tasks, reassurance, and consistent carers where possible.',
                'social_needs' => 'Support should encourage safe contact with family or trusted contacts and reduce avoidable isolation.',
                'spiritual_needs' => 'No specific spiritual needs recorded during backend autofill; confirm preferences at the next review.',
                'environmental_needs' => 'Home environment should remain clear of trip hazards with safe access to bathroom, bedroom, and kitchen areas.',
                'priority_needs' => 'Medication support, personal care prompts, hydration, nutrition, and falls-risk observation are priority areas.',
                'notes' => 'Autofilled baseline assessment; carers should update if client preferences or risks differ during visits.',
            ], $timestamp);

            $this->syncSection('client_functional_assessments', (int) $assessment->id, [
                'mobility_status' => 'Supervision',
                'bathing_ability' => 'Assistance',
                'dressing_ability' => 'Prompting',
                'eating_ability' => 'Supervision',
                'toileting_ability' => 'Supervision',
                'transferring_ability' => 'Supervision',
                'continence_status' => 'Prompting',
                'independence_level' => 'Assistance',
                'notes' => 'Encourage independence where safe. Observe mobility and transfers at each visit and escalate any deterioration.',
            ], $timestamp);

            $this->syncSection('client_medical_assessments', (int) $assessment->id, [
                'diagnoses' => 'No confirmed diagnoses recorded in backend autofill.',
                'medical_conditions' => 'Monitor for change in presentation, pain, dizziness, confusion, reduced intake, or signs of infection.',
                'medications' => 'Medication list to be confirmed against the MAR chart before administration or prompting.',
                'allergies' => 'No allergies recorded in backend autofill. Confirm and update before medication support.',
                'vital_signs' => 'Record observations when clinically indicated or requested by the care plan.',
                'gp_details' => 'GP details to be confirmed and linked to the client record.',
                'medication_support_needed' => true,
                'notes' => 'Medication support must be timestamped and escalated if missed, refused, or unavailable.',
            ], $timestamp);

            $this->syncSection('client_mental_capacity_assessments', (int) $assessment->id, [
                'decision_type' => 'Consent to routine care and support plan',
                'understands_information' => true,
                'retains_information' => true,
                'weighs_information' => true,
                'communicates_decision' => true,
                'capacity_outcome' => 'Has capacity for routine care decisions unless presentation changes',
                'best_interest_decision' => 'No best-interest decision required for baseline routine care at this time.',
                'imca_involved' => false,
                'dols_lps_status' => 'Not applicable',
                'notes' => 'Capacity is decision-specific and must be reconsidered if cognition, presentation, or consent changes.',
            ], $timestamp);

            $this->syncSection('client_risk_assessments', (int) $assessment->id, [
                'falls_risk' => 'Medium',
                'pressure_ulcer_risk' => 'Low',
                'manual_handling_risk' => 'Medium',
                'environmental_risk' => 'Medium',
                'behaviour_risk' => 'Low',
                'safeguarding_risk' => 'Low',
                'control_measures' => 'Keep walkways clear, use agreed moving-and-handling prompts, observe transfers, confirm medication support, and escalate missed visits, missed medication, falls, or client-not-home events.',
                'notes' => 'Risk levels are baseline defaults and must be reviewed after first live care visits.',
            ], $timestamp);

            $this->syncSection('client_communication_assessments', (int) $assessment->id, [
                'preferred_language' => 'English',
                'communication_method' => 'Verbal communication with clear prompts',
                'hearing_impairment' => false,
                'vision_impairment' => false,
                'speech_difficulty' => false,
                'interpreter_required' => false,
                'communication_aids' => 'Use simple explanations, allow time for responses, and confirm understanding before care tasks.',
                'notes' => 'Confirm preferred language and communication needs during next direct review.',
            ], $timestamp);

            $this->syncSection('client_equality_assessments', (int) $assessment->id, [
                'gender' => 'Female',
                'ethnicity' => 'Not recorded',
                'religion' => 'Not recorded',
                'disability_status' => 'Not recorded',
                'sexual_orientation' => 'Not recorded',
                'cultural_needs' => 'Ask about cultural preferences during care planning and record any personal-care requirements.',
                'reasonable_adjustments' => 'Provide clear explanations, allow additional time, and adapt visit tasks to fatigue or distress.',
                'notes' => 'Equality fields require confirmation with the client or representative.',
            ], $timestamp);

            $this->syncSection('client_social_assessments', (int) $assessment->id, [
                'living_arrangements' => 'Lives at home',
                'family_support' => 'Family or trusted-contact involvement to be confirmed and recorded for escalation.',
                'social_isolation_risk' => 'Medium',
                'community_engagement' => 'Encourage safe social contact in line with client preferences.',
                'employment_status' => 'Not recorded',
                'financial_concerns' => 'No financial concerns recorded in backend autofill.',
                'notes' => 'Confirm emergency contacts and preferred representative during next review.',
            ], $timestamp);

            $this->syncSection('client_environmental_assessments', (int) $assessment->id, [
                'home_condition' => 'Suitable with routine safety checks',
                'safety_hazards' => 'Monitor for trip hazards, poor lighting, inaccessible essentials, and unsafe equipment.',
                'accessibility' => 'Accessible with supervision',
                'equipment_needed' => 'Confirm any mobility aids, bathroom aids, and medication storage requirements.',
                'fire_risk' => 'Low',
                'cleanliness_level' => 'Acceptable',
                'notes' => 'Carers should report environmental changes or hazards immediately.',
            ], $timestamp);

            DB::table('clients')
                ->where('id', $client->id)
                ->update([
                    'onboarding_status' => 'approved',
                    'submitted_at' => $timestamp,
                    'reviewed_at' => $timestamp,
                    'reviewed_by' => $reviewer?->id,
                    'review_notes' => null,
                    'updated_at' => $timestamp,
                ]);

            $this->writeAuditLog(
                (int) $assessment->id,
                $reviewer?->id,
                (int) $client->id,
                $clientName,
                (int) $assessment->version,
                $timestamp
            );
        });
    }

    private function editableAssessmentFor(int $clientId): object
    {
        $openAssessment = DB::table('client_assessments')
            ->where('client_id', $clientId)
            ->where('status', 'onboarding')
            ->orderByDesc('version')
            ->first();

        if ($openAssessment !== null) {
            return $openAssessment;
        }

        $latest = DB::table('client_assessments')
            ->where('client_id', $clientId)
            ->orderByDesc('version')
            ->first();

        if ($latest !== null) {
            return $latest;
        }

        $timestamp = now()->toDateTimeString();
        $id = DB::table('client_assessments')->insertGetId([
            'client_id' => $clientId,
            'version' => 1,
            'assessment_date' => now()->toDateString(),
            'assessment_type' => 'initial',
            'status' => 'onboarding',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return DB::table('client_assessments')->where('id', $id)->first();
    }

    /**
     * @param array<string, mixed> $values
     */
    private function syncSection(string $table, int $assessmentId, array $values, string $timestamp): void
    {
        $existing = DB::table($table)
            ->where('client_assessment_id', $assessmentId)
            ->first();

        if ($existing === null) {
            DB::table($table)->insert(array_merge($values, [
                'client_assessment_id' => $assessmentId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]));

            return;
        }

        DB::table($table)
            ->where('client_assessment_id', $assessmentId)
            ->update(array_merge($values, ['updated_at' => $timestamp]));
    }

    private function writeAuditLog(int $assessmentId, ?int $actorId, int $clientId, string $clientName, int $version, string $timestamp): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')->insert([
            'actor_id' => $actorId,
            'auditable_type' => 'App\\Models\\ClientAssessment',
            'auditable_id' => $assessmentId,
            'action' => 'assessment_autofilled',
            'event' => 'ClientAssessment',
            'route_name' => null,
            'method' => null,
            'url' => null,
            'ip_address' => null,
            'user_agent' => null,
            'old_values' => json_encode([]),
            'new_values' => json_encode(['status' => 'approved']),
            'metadata' => json_encode([
                'client_id' => $clientId,
                'client_name' => $clientName,
                'version' => $version,
                'source' => self::class,
            ]),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
