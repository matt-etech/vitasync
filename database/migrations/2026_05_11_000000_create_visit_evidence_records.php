<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_task_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('carer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('task_key');
            $table->string('title');
            $table->text('detail')->nullable();
            $table->string('status')->default('completed');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['visit_id', 'task_key']);
            $table->index(['carer_id', 'completed_at']);
        });

        Schema::create('visit_vital_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('carer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('bp_systolic');
            $table->unsignedSmallInteger('bp_diastolic');
            $table->unsignedSmallInteger('pulse');
            $table->decimal('temperature', 4, 1);
            $table->unsignedSmallInteger('blood_oxygen');
            $table->dateTime('recorded_at');
            $table->timestamps();

            $table->index(['visit_id', 'recorded_at']);
            $table->index(['client_id', 'recorded_at']);
        });

        Schema::create('visit_evidence_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('carer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evidence_type');
            $table->string('label');
            $table->string('file_name')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('captured_at');
            $table->timestamps();

            $table->index(['visit_id', 'evidence_type']);
            $table->index(['carer_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_evidence_records');
        Schema::dropIfExists('visit_vital_records');
        Schema::dropIfExists('visit_task_records');
    }
};
