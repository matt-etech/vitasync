<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('care_plans', function (Blueprint $table) {
            $table->string('personal_care_level')->nullable();
            $table->string('mobility_level')->nullable();
            $table->string('nutrition_support_level')->nullable();
            $table->string('medication_support_level')->nullable();
            $table->string('communication_support_level')->nullable();
            $table->string('risk_level')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('care_plans', function (Blueprint $table) {
            $table->dropColumn([
                'personal_care_level',
                'mobility_level',
                'nutrition_support_level',
                'medication_support_level',
                'communication_support_level',
                'risk_level',
            ]);
        });
    }
};
