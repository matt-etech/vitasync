<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('plan_type')->default('Initial');
            $table->string('care_level')->nullable();
            $table->string('visit_frequency')->nullable();
            $table->string('review_frequency')->nullable();
            $table->date('start_date');
            $table->date('review_date')->nullable();
            $table->text('care_goals')->nullable();
            $table->text('personal_care_support')->nullable();
            $table->text('mobility_support')->nullable();
            $table->text('nutrition_hydration_support')->nullable();
            $table->text('medication_support')->nullable();
            $table->text('communication_support')->nullable();
            $table->text('risk_management')->nullable();
            $table->text('preferences_routines')->nullable();
            $table->text('escalation_instructions')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index(['home_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index('review_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_plans');
    }
};
