<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('onboarding_status')->default('onboarding')->after('status');
            $table->timestamp('submitted_at')->nullable()->after('onboarding_status');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable()->after('reviewed_by');

            $table->index(['home_id', 'onboarding_status']);
        });

        Schema::create('client_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('assessment_date')->nullable();
            $table->string('assessor_name')->nullable();
            $table->string('assessment_type')->default('initial');
            $table->text('overall_summary')->nullable();
            $table->string('overall_risk_level')->nullable();
            $table->text('recommendations')->nullable();
            $table->date('next_review_date')->nullable();
            $table->string('status')->default('onboarding');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique('client_id');
            $table->index(['status', 'next_review_date']);
        });

        Schema::create('client_need_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_assessment_id')->constrained()->cascadeOnDelete();
            $table->text('physical_needs')->nullable();
            $table->text('psychological_needs')->nullable();
            $table->text('social_needs')->nullable();
            $table->text('spiritual_needs')->nullable();
            $table->text('environmental_needs')->nullable();
            $table->text('priority_needs')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('client_assessment_id');
        });

        Schema::create('client_functional_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('mobility_status')->nullable();
            $table->string('bathing_ability')->nullable();
            $table->string('dressing_ability')->nullable();
            $table->string('eating_ability')->nullable();
            $table->string('toileting_ability')->nullable();
            $table->string('transferring_ability')->nullable();
            $table->string('continence_status')->nullable();
            $table->string('independence_level')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('client_assessment_id');
        });

        Schema::create('client_medical_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_assessment_id')->constrained()->cascadeOnDelete();
            $table->text('diagnoses')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('medications')->nullable();
            $table->text('allergies')->nullable();
            $table->text('vital_signs')->nullable();
            $table->text('gp_details')->nullable();
            $table->boolean('medication_support_needed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('client_assessment_id');
        });

        Schema::create('client_mental_capacity_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('decision_type')->nullable();
            $table->boolean('understands_information')->default(false);
            $table->boolean('retains_information')->default(false);
            $table->boolean('weighs_information')->default(false);
            $table->boolean('communicates_decision')->default(false);
            $table->string('capacity_outcome')->nullable();
            $table->text('best_interest_decision')->nullable();
            $table->boolean('imca_involved')->default(false);
            $table->string('dols_lps_status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('client_assessment_id');
        });

        Schema::create('client_risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('falls_risk')->nullable();
            $table->string('pressure_ulcer_risk')->nullable();
            $table->string('manual_handling_risk')->nullable();
            $table->string('environmental_risk')->nullable();
            $table->string('behaviour_risk')->nullable();
            $table->string('safeguarding_risk')->nullable();
            $table->text('control_measures')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('client_assessment_id');
        });

        Schema::create('client_communication_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('preferred_language')->nullable();
            $table->string('communication_method')->nullable();
            $table->boolean('hearing_impairment')->default(false);
            $table->boolean('vision_impairment')->default(false);
            $table->boolean('speech_difficulty')->default(false);
            $table->boolean('interpreter_required')->default(false);
            $table->text('communication_aids')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('client_assessment_id');
        });

        Schema::create('client_equality_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('gender')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('religion')->nullable();
            $table->string('disability_status')->nullable();
            $table->string('sexual_orientation')->nullable();
            $table->text('cultural_needs')->nullable();
            $table->text('reasonable_adjustments')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('client_assessment_id');
        });

        Schema::create('client_social_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('living_arrangements')->nullable();
            $table->text('family_support')->nullable();
            $table->string('social_isolation_risk')->nullable();
            $table->text('community_engagement')->nullable();
            $table->string('employment_status')->nullable();
            $table->text('financial_concerns')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('client_assessment_id');
        });

        Schema::create('client_environmental_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('home_condition')->nullable();
            $table->text('safety_hazards')->nullable();
            $table->string('accessibility')->nullable();
            $table->text('equipment_needed')->nullable();
            $table->string('fire_risk')->nullable();
            $table->string('cleanliness_level')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('client_assessment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_environmental_assessments');
        Schema::dropIfExists('client_social_assessments');
        Schema::dropIfExists('client_equality_assessments');
        Schema::dropIfExists('client_communication_assessments');
        Schema::dropIfExists('client_risk_assessments');
        Schema::dropIfExists('client_mental_capacity_assessments');
        Schema::dropIfExists('client_medical_assessments');
        Schema::dropIfExists('client_functional_assessments');
        Schema::dropIfExists('client_need_assessments');
        Schema::dropIfExists('client_assessments');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['home_id', 'onboarding_status']);
            $table->dropColumn(['onboarding_status', 'submitted_at', 'reviewed_at', 'review_notes']);
        });
    }
};
