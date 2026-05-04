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
        Schema::create('carer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('onboarding');
            $table->string('legal_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('national_insurance_number', 20)->nullable();
            $table->string('photo_id_type')->nullable();
            $table->string('id_document_number')->nullable();
            $table->string('id_document_path')->nullable();
            $table->string('right_to_work_status')->nullable();
            $table->string('visa_status')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('postcode', 20)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->string('job_title')->nullable();
            $table->string('employment_type')->nullable();
            $table->date('start_date')->nullable();
            $table->foreignId('assigned_home_id')->nullable()->constrained('homes')->nullOnDelete();
            $table->string('dbs_check_status')->nullable();
            $table->string('dbs_certificate_number')->nullable();
            $table->date('dbs_expiry_date')->nullable();
            $table->string('safeguarding_training_completed')->nullable();
            $table->date('last_safeguarding_training_date')->nullable();
            $table->string('occupational_health_clearance')->nullable();
            $table->string('immunisation_status')->nullable();
            $table->boolean('fit_to_work_declaration')->default(false);
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            $table->string('availability_pattern')->nullable();
            $table->unsignedTinyInteger('max_weekly_hours')->nullable();
            $table->string('shift_preference')->nullable();
            $table->string('account_status')->default('pending');
            $table->boolean('mfa_enabled')->default(false);
            $table->boolean('data_processing_consent')->default(false);
            $table->timestamp('data_processing_consented_at')->nullable();
            $table->boolean('privacy_policy_accepted')->default(false);
            $table->timestamp('privacy_policy_accepted_at')->nullable();
            $table->string('privacy_policy_version')->nullable();
            $table->string('data_retention_category')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['right_to_work_status', 'visa_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carer_profiles');
    }
};
