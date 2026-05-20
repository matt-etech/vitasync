<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('carer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('care_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('medication_name');
            $table->string('dose')->nullable();
            $table->string('route')->nullable();
            $table->string('outcome');
            $table->text('notes')->nullable();
            $table->dateTime('administered_at');
            $table->timestamps();

            $table->index(['visit_id', 'administered_at']);
            $table->index(['client_id', 'administered_at']);
            $table->index(['carer_id', 'administered_at']);
            $table->index('outcome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_administrations');
    }
};
