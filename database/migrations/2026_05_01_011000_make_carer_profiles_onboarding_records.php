<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('carer_profiles', 'status')) {
                $table->string('status')->default('onboarding')->after('id');
            }

            if (! Schema::hasColumn('carer_profiles', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('id_document_path');
            }

            if (! Schema::hasColumn('carer_profiles', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            }

            if (! Schema::hasColumn('carer_profiles', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('carer_profiles', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('reviewed_by');
            }
        });

        if (DB::connection()->getDriverName() !== 'sqlite' && Schema::hasColumn('carer_profiles', 'legal_name')) {
            DB::statement('ALTER TABLE carer_profiles MODIFY legal_name VARCHAR(255) NULL');
            DB::statement('ALTER TABLE carer_profiles MODIFY date_of_birth DATE NULL');
            DB::statement('ALTER TABLE carer_profiles MODIFY national_insurance_number VARCHAR(20) NULL');
            DB::statement('ALTER TABLE carer_profiles MODIFY photo_id_type VARCHAR(255) NULL');
            DB::statement('ALTER TABLE carer_profiles MODIFY id_document_number VARCHAR(255) NULL');
            DB::statement('ALTER TABLE carer_profiles MODIFY right_to_work_status VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('carer_profiles', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }

            $columns = collect(['status', 'submitted_at', 'reviewed_at', 'review_notes'])
                ->filter(fn (string $column): bool => Schema::hasColumn('carer_profiles', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
