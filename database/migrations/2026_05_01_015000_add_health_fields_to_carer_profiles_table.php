<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('carer_profiles', 'occupational_health_clearance')) {
                $table->string('occupational_health_clearance')->nullable()->after('last_safeguarding_training_date');
            }

            if (! Schema::hasColumn('carer_profiles', 'immunisation_status')) {
                $table->string('immunisation_status')->nullable()->after('occupational_health_clearance');
            }

            if (! Schema::hasColumn('carer_profiles', 'fit_to_work_declaration')) {
                $table->boolean('fit_to_work_declaration')->default(false)->after('immunisation_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            $columns = collect([
                'occupational_health_clearance',
                'immunisation_status',
                'fit_to_work_declaration',
            ])
                ->filter(fn (string $column): bool => Schema::hasColumn('carer_profiles', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
