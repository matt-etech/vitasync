<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientAssessmentRequest extends FormRequest
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
        $nullableText = ['nullable', 'string', 'max:5000'];
        $nullableShortText = ['nullable', 'string', 'max:255'];

        return [
            'assessment.assessment_date' => ['nullable', 'date', 'before_or_equal:today'],
            'assessment.assessor_name' => $nullableShortText,
            'assessment.assessment_type' => ['required', Rule::in(['initial', 'review'])],
            'assessment.overall_summary' => $nullableText,
            'assessment.overall_risk_level' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'assessment.recommendations' => $nullableText,
            'assessment.next_review_date' => ['nullable', 'date', 'after_or_equal:today'],

            'needs.physical_needs' => $nullableText,
            'needs.psychological_needs' => $nullableText,
            'needs.social_needs' => $nullableText,
            'needs.spiritual_needs' => $nullableText,
            'needs.environmental_needs' => $nullableText,
            'needs.priority_needs' => $nullableText,
            'needs.notes' => $nullableText,

            'functional.mobility_status' => $nullableShortText,
            'functional.bathing_ability' => $nullableShortText,
            'functional.dressing_ability' => $nullableShortText,
            'functional.eating_ability' => $nullableShortText,
            'functional.toileting_ability' => $nullableShortText,
            'functional.transferring_ability' => $nullableShortText,
            'functional.continence_status' => $nullableShortText,
            'functional.independence_level' => $nullableShortText,
            'functional.notes' => $nullableText,

            'medical.diagnoses' => $nullableText,
            'medical.medical_conditions' => $nullableText,
            'medical.medications' => $nullableText,
            'medical.allergies' => $nullableText,
            'medical.vital_signs' => $nullableText,
            'medical.gp_details' => $nullableText,
            'medical.medication_support_needed' => ['nullable', 'boolean'],
            'medical.notes' => $nullableText,

            'mental_capacity.decision_type' => $nullableShortText,
            'mental_capacity.understands_information' => ['nullable', 'boolean'],
            'mental_capacity.retains_information' => ['nullable', 'boolean'],
            'mental_capacity.weighs_information' => ['nullable', 'boolean'],
            'mental_capacity.communicates_decision' => ['nullable', 'boolean'],
            'mental_capacity.capacity_outcome' => $nullableShortText,
            'mental_capacity.best_interest_decision' => $nullableText,
            'mental_capacity.imca_involved' => ['nullable', 'boolean'],
            'mental_capacity.dols_lps_status' => $nullableShortText,
            'mental_capacity.notes' => $nullableText,

            'risk.falls_risk' => $nullableShortText,
            'risk.pressure_ulcer_risk' => $nullableShortText,
            'risk.manual_handling_risk' => $nullableShortText,
            'risk.environmental_risk' => $nullableShortText,
            'risk.behaviour_risk' => $nullableShortText,
            'risk.safeguarding_risk' => $nullableShortText,
            'risk.control_measures' => $nullableText,
            'risk.notes' => $nullableText,

            'communication.preferred_language' => $nullableShortText,
            'communication.communication_method' => $nullableShortText,
            'communication.hearing_impairment' => ['nullable', 'boolean'],
            'communication.vision_impairment' => ['nullable', 'boolean'],
            'communication.speech_difficulty' => ['nullable', 'boolean'],
            'communication.interpreter_required' => ['nullable', 'boolean'],
            'communication.communication_aids' => $nullableText,
            'communication.notes' => $nullableText,

            'equality.gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'equality.ethnicity' => $nullableShortText,
            'equality.religion' => $nullableShortText,
            'equality.disability_status' => $nullableShortText,
            'equality.sexual_orientation' => $nullableShortText,
            'equality.cultural_needs' => $nullableText,
            'equality.reasonable_adjustments' => $nullableText,
            'equality.notes' => $nullableText,

            'social.living_arrangements' => $nullableShortText,
            'social.family_support' => $nullableText,
            'social.social_isolation_risk' => $nullableShortText,
            'social.community_engagement' => $nullableText,
            'social.employment_status' => $nullableShortText,
            'social.financial_concerns' => $nullableText,
            'social.notes' => $nullableText,

            'environmental.home_condition' => $nullableShortText,
            'environmental.safety_hazards' => $nullableText,
            'environmental.accessibility' => $nullableShortText,
            'environmental.equipment_needed' => $nullableText,
            'environmental.fire_risk' => $nullableShortText,
            'environmental.cleanliness_level' => $nullableShortText,
            'environmental.notes' => $nullableText,
        ];
    }
}
