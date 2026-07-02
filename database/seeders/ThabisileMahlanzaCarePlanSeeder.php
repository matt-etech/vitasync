<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThabisileMahlanzaCarePlanSeeder extends Seeder
{
    public function run(): void
    {
        app(ThabisileMahlanzaAssessmentSeeder::class)->run();

        $client = DB::table('clients')
            ->where('first_name', 'Thabisile')
            ->firstOrFail();

        $today = now();
        $clientName = trim($client->first_name.' '.$client->last_name);
        $timestamp = $today->toDateTimeString();
        $title = "{$clientName} full support care plan";

        $values = [
                'home_id' => $client->home_id,
                'client_id' => $client->id,
                'title' => $title,
                'plan_type' => 'Initial',
                'care_level' => 'Medium',
                'visit_frequency' => 'Multiple daily',
                'review_frequency' => 'Quarterly',
                'start_date' => $today->toDateString(),
                'review_date' => $today->copy()->addMonthsNoOverflow(3)->toDateString(),
                'care_goals' => "Maintain {$clientName} safely at home with dignified personal care, reliable medication support, safe mobility, adequate food and fluid intake, and clear escalation when presentation or risk changes.",
                'personal_care_level' => 'Partial support',
                'personal_care_support' => 'Provide morning and evening prompts for washing, dressing, hygiene, grooming, continence routines, and comfort checks. Explain each task before support, preserve privacy, encourage independence where safe, and record any refusal, discomfort, skin concern, or change in ability.',
                'mobility_level' => 'One person assist',
                'mobility_support' => 'Supervise transfers and movement around the home. Keep walkways clear, check footwear and lighting, encourage steady pacing, and observe for dizziness, pain, weakness, or increased falls risk. Escalate any fall, near miss, or reduced mobility immediately.',
                'nutrition_support_level' => 'Meal preparation',
                'nutrition_hydration_support' => 'Prompt and support regular meals, snacks, and fluids during visits. Prepare or check meals according to preference, leave drinks within reach, monitor appetite and hydration, and report reduced intake, swallowing concerns, nausea, or weight/appetite changes.',
                'medication_support_level' => 'Prompting',
                'medication_support' => 'Confirm the current medication list against the MAR before prompting. Prompt at scheduled times, document support accurately, and escalate missed, refused, unavailable, dropped, or unclear medication immediately to the duty manager and follow local medication policy.',
                'communication_support_level' => 'Verbal',
                'communication_support' => 'Use calm verbal communication, short explanations, and enough time for responses. Confirm consent and understanding before care tasks. Reassure when anxious and report any confusion, distress, communication difficulty, or change in mental state.',
                'risk_level' => 'Medium',
                'risk_management' => 'Baseline risks are falls, medication omissions, reduced intake, environmental hazards, and change in presentation. Controls include clear walkways, safe transfers, medication checks, hydration prompts, visit documentation, and immediate reporting of hazards or deterioration.',
                'preferences_routines' => 'Offer care in a calm, respectful manner and confirm daily preferences at each visit. Support morning hygiene and breakfast routines, evening comfort and medication prompts, safe access to essentials, and family or trusted-contact involvement where agreed.',
                'escalation_instructions' => 'Call the duty manager for missed visits, missed or refused medication, falls, injury, safeguarding concerns, client not found at home, sudden confusion, chest pain, breathing difficulty, infection signs, reduced intake, or any urgent deterioration. Contact emergency services first if there is immediate danger.',
                'review_notes' => 'Autofilled from the approved baseline client assessment. Review after the first live care cycle and formally within three months, updating preferences, risks, medication details, emergency contacts, equipment needs, and confirmed clinical information.',
                'status' => 'active',
                'updated_at' => $timestamp,
        ];

        $existingId = DB::table('care_plans')
            ->where('client_id', $client->id)
            ->where('title', $title)
            ->value('id');

        if ($existingId === null) {
            DB::table('care_plans')->insert(array_merge($values, [
                'created_at' => $timestamp,
            ]));

            return;
        }

        DB::table('care_plans')
            ->where('id', $existingId)
            ->update($values);
    }
}
