<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('risk_domain');
            $table->string('risk_level');
            $table->text('hazards')->nullable();
            $table->text('control_measures')->nullable();
            $table->date('review_date');
            $table->date('next_review_date')->nullable();
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status', 'review_date']);
            $table->index(['home_id', 'risk_level', 'status']);
        });

        Schema::create('capacity_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_type');
            $table->string('capacity_outcome');
            $table->string('best_interest_status')->nullable();
            $table->string('advocate_status')->nullable();
            $table->date('review_date');
            $table->date('next_review_date')->nullable();
            $table->text('evidence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'review_date']);
            $table->index(['home_id', 'capacity_outcome']);
        });

        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('consent_type');
            $table->string('decision');
            $table->string('given_by');
            $table->dateTime('recorded_at');
            $table->date('review_date')->nullable();
            $table->text('evidence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'consent_type', 'recorded_at']);
            $table->index(['home_id', 'decision']);
        });

        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('dose')->nullable();
            $table->string('route')->nullable();
            $table->string('frequency')->nullable();
            $table->string('support_level')->default('Prompting');
            $table->string('status')->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['home_id', 'status']);
        });

        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('administered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('outcome');
            $table->dateTime('administered_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'administered_at']);
            $table->index(['medication_id', 'administered_at']);
            $table->index(['visit_id', 'administered_at']);
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category');
            $table->string('severity');
            $table->dateTime('occurred_at');
            $table->text('description');
            $table->text('immediate_actions')->nullable();
            $table->string('status')->default('open');
            $table->boolean('safeguarding_required')->default(false);
            $table->timestamps();

            $table->index(['client_id', 'status', 'occurred_at']);
            $table->index(['home_id', 'severity', 'status']);
        });

        Schema::create('safeguarding_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('concern_type');
            $table->string('risk_level');
            $table->string('status')->default('open');
            $table->dateTime('opened_at');
            $table->dateTime('referred_at')->nullable();
            $table->text('summary');
            $table->text('actions_taken')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status', 'opened_at']);
            $table->index(['home_id', 'risk_level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safeguarding_cases');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('medication_administrations');
        Schema::dropIfExists('medications');
        Schema::dropIfExists('consent_records');
        Schema::dropIfExists('capacity_reviews');
        Schema::dropIfExists('risk_reviews');
    }
};
