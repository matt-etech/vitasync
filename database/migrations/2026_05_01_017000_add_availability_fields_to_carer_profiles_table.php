<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('carer_profiles', 'availability_pattern')) {
                $table->string('availability_pattern')->nullable()->after('languages');
            }

            if (! Schema::hasColumn('carer_profiles', 'max_weekly_hours')) {
                $table->unsignedTinyInteger('max_weekly_hours')->nullable()->after('availability_pattern');
            }

            if (! Schema::hasColumn('carer_profiles', 'shift_preference')) {
                $table->string('shift_preference')->nullable()->after('max_weekly_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            $columns = collect(['availability_pattern', 'max_weekly_hours', 'shift_preference'])
                ->filter(fn (string $column): bool => Schema::hasColumn('carer_profiles', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
