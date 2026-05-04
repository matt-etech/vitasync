<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('carer_profiles', 'data_processing_consent')) {
                $table->boolean('data_processing_consent')->default(false)->after('mfa_enabled');
            }

            if (! Schema::hasColumn('carer_profiles', 'data_processing_consented_at')) {
                $table->timestamp('data_processing_consented_at')->nullable()->after('data_processing_consent');
            }

            if (! Schema::hasColumn('carer_profiles', 'privacy_policy_accepted')) {
                $table->boolean('privacy_policy_accepted')->default(false)->after('data_processing_consented_at');
            }

            if (! Schema::hasColumn('carer_profiles', 'privacy_policy_accepted_at')) {
                $table->timestamp('privacy_policy_accepted_at')->nullable()->after('privacy_policy_accepted');
            }

            if (! Schema::hasColumn('carer_profiles', 'privacy_policy_version')) {
                $table->string('privacy_policy_version')->nullable()->after('privacy_policy_accepted_at');
            }

            if (! Schema::hasColumn('carer_profiles', 'data_retention_category')) {
                $table->string('data_retention_category')->nullable()->after('privacy_policy_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            $columns = collect([
                'data_processing_consent',
                'data_processing_consented_at',
                'privacy_policy_accepted',
                'privacy_policy_accepted_at',
                'privacy_policy_version',
                'data_retention_category',
            ])
                ->filter(fn (string $column): bool => Schema::hasColumn('carer_profiles', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
