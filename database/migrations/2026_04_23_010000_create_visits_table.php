<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('care_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->dateTime('scheduled_start_at');
            $table->dateTime('scheduled_end_at');
            $table->string('status')->default('scheduled');
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['home_id', 'status', 'scheduled_start_at']);
            $table->index(['client_id', 'scheduled_start_at']);
            $table->index(['assigned_user_id', 'scheduled_start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
