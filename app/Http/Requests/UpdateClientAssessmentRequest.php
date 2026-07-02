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
            'assessment.overall_risk_level' => ['nullable', Rule::in(['none', 'low', 'medium', 'high', 'critical', 'not_assessed'])],
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

            'equality.gender' => ['nullable', Rule::in(['Female', 'Male', 'Non-binary', 'Other', 'Prefer not to say', 'Not recorded'])],
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

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'assessment.assessment_date' => 'assessment date',
            'assessment.assessor_name' => 'assessor name',
            'assessment.assessment_type' => 'assessment type',
            'assessment.overall_summary' => 'overall summary',
            'assessment.overall_risk_level' => 'overall risk level',
            'assessment.recommendations' => 'recommendations',
            'assessment.next_review_date' => 'next review date',
            'needs.physical_needs' => 'physical needs',
            'needs.psychological_needs' => 'psychological needs',
            'needs.social_needs' => 'social needs',
            'needs.spiritual_needs' => 'spiritual needs',
            'needs.environmental_needs' => 'environmental needs',
            'needs.priority_needs' => 'priority needs',
            'needs.notes' => 'needs notes',
            'functional.mobility_status' => 'mobility status',
            'functional.bathing_ability' => 'bathing ability',
            'functional.dressing_ability' => 'dressing ability',
            'functional.eating_ability' => 'eating ability',
            'functional.toileting_ability' => 'toileting ability',
            'functional.transferring_ability' => 'transferring ability',
            'functional.continence_status' => 'continence status',
            'functional.independence_level' => 'independence level',
            'functional.notes' => 'functional notes',
            'medical.diagnoses' => 'diagnoses',
            'medical.medical_conditions' => 'medical conditions',
            'medical.medications' => 'medication support',
            'medical.allergies' => 'allergies',
            'medical.vital_signs' => 'vital signs',
            'medical.gp_details' => 'GP details',
            'medical.medication_support_needed' => 'medication support needed',
            'medical.notes' => 'medical notes',
            'mental_capacity.decision_type' => 'decision type',
            'mental_capacity.understands_information' => 'understands information',
            'mental_capacity.retains_information' => 'retains information',
            'mental_capacity.weighs_information' => 'weighs information',
            'mental_capacity.communicates_decision' => 'communicates decision',
            'mental_capacity.capacity_outcome' => 'capacity outcome',
            'mental_capacity.best_interest_decision' => 'best-interest decision',
            'mental_capacity.imca_involved' => 'IMCA involved',
            'mental_capacity.dols_lps_status' => 'DoLS/LPS status',
            'mental_capacity.notes' => 'mental capacity notes',
            'risk.falls_risk' => 'falls risk',
            'risk.pressure_ulcer_risk' => 'pressure ulcer risk',
            'risk.manual_handling_risk' => 'manual handling risk',
            'risk.environmental_risk' => 'environmental risk',
            'risk.behaviour_risk' => 'behaviour risk',
            'risk.safeguarding_risk' => 'safeguarding risk',
            'risk.control_measures' => 'control measures',
            'risk.notes' => 'risk notes',
            'communication.preferred_language' => 'preferred language',
            'communication.communication_method' => 'communication method',
            'communication.hearing_impairment' => 'hearing impairment',
            'communication.vision_impairment' => 'vision impairment',
            'communication.speech_difficulty' => 'speech difficulty',
            'communication.interpreter_required' => 'interpreter required',
            'communication.communication_aids' => 'communication aids',
            'communication.notes' => 'communication notes',
            'equality.gender' => 'gender',
            'equality.ethnicity' => 'ethnicity',
            'equality.religion' => 'religion',
            'equality.disability_status' => 'disability status',
            'equality.sexual_orientation' => 'sexual orientation',
            'equality.cultural_needs' => 'cultural needs',
            'equality.reasonable_adjustments' => 'reasonable adjustments',
            'equality.notes' => 'equality notes',
            'social.living_arrangements' => 'living arrangements',
            'social.family_support' => 'family support',
            'social.social_isolation_risk' => 'social isolation risk',
            'social.community_engagement' => 'community engagement',
            'social.employment_status' => 'employment status',
            'social.financial_concerns' => 'financial concerns',
            'social.notes' => 'social notes',
            'environmental.home_condition' => 'home condition',
            'environmental.safety_hazards' => 'safety hazards',
            'environmental.accessibility' => 'accessibility',
            'environmental.equipment_needed' => 'equipment needed',
            'environmental.fire_risk' => 'fire risk',
            'environmental.cleanliness_level' => 'cleanliness level',
            'environmental.notes' => 'environmental notes',
        ];
    }
}
