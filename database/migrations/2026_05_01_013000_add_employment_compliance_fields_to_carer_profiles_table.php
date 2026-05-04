<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('carer_profiles', 'job_title')) {
                $table->string('job_title')->nullable()->after('emergency_contact_phone');
            }

            if (! Schema::hasColumn('carer_profiles', 'employment_type')) {
                $table->string('employment_type')->nullable()->after('job_title');
            }

            if (! Schema::hasColumn('carer_profiles', 'start_date')) {
                $table->date('start_date')->nullable()->after('employment_type');
            }

            if (! Schema::hasColumn('carer_profiles', 'assigned_home_id')) {
                $table->foreignId('assigned_home_id')->nullable()->after('start_date')->constrained('homes')->nullOnDelete();
            }

            if (! Schema::hasColumn('carer_profiles', 'dbs_check_status')) {
                $table->string('dbs_check_status')->nullable()->after('assigned_home_id');
            }

            if (! Schema::hasColumn('carer_profiles', 'dbs_certificate_number')) {
                $table->string('dbs_certificate_number')->nullable()->after('dbs_check_status');
            }

            if (! Schema::hasColumn('carer_profiles', 'dbs_expiry_date')) {
                $table->date('dbs_expiry_date')->nullable()->after('dbs_certificate_number');
            }

            if (! Schema::hasColumn('carer_profiles', 'safeguarding_training_completed')) {
                $table->string('safeguarding_training_completed')->nullable()->after('dbs_expiry_date');
            }

            if (! Schema::hasColumn('carer_profiles', 'last_safeguarding_training_date')) {
                $table->date('last_safeguarding_training_date')->nullable()->after('safeguarding_training_completed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('carer_profiles', 'assigned_home_id')) {
                $table->dropConstrainedForeignId('assigned_home_id');
            }

            $columns = collect([
                'job_title',
                'employment_type',
                'start_date',
                'dbs_check_status',
                'dbs_certificate_number',
                'dbs_expiry_date',
                'safeguarding_training_completed',
                'last_safeguarding_training_date',
            ])
                ->filter(fn (string $column): bool => Schema::hasColumn('carer_profiles', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
