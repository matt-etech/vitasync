<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carer_training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('training_key');
            $table->string('training_name');
            $table->string('status')->default('not_started');
            $table->date('expiry_date')->nullable();
            $table->string('certificate_path')->nullable();
            $table->timestamps();

            $table->unique(['carer_profile_id', 'training_key']);
            $table->index(['training_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carer_training_records');
    }
};
