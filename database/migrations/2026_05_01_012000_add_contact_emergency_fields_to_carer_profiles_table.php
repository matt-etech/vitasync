<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('carer_profiles', 'address_line_1')) {
                $table->string('address_line_1')->nullable()->after('visa_status');
            }

            if (! Schema::hasColumn('carer_profiles', 'address_line_2')) {
                $table->string('address_line_2')->nullable()->after('address_line_1');
            }

            if (! Schema::hasColumn('carer_profiles', 'city')) {
                $table->string('city')->nullable()->after('address_line_2');
            }

            if (! Schema::hasColumn('carer_profiles', 'postcode')) {
                $table->string('postcode', 20)->nullable()->after('city');
            }

            if (! Schema::hasColumn('carer_profiles', 'contact_phone')) {
                $table->string('contact_phone', 50)->nullable()->after('postcode');
            }

            if (! Schema::hasColumn('carer_profiles', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('contact_phone');
            }

            if (! Schema::hasColumn('carer_profiles', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('contact_email');
            }

            if (! Schema::hasColumn('carer_profiles', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 50)->nullable()->after('emergency_contact_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            $columns = collect([
                'address_line_1',
                'address_line_2',
                'city',
                'postcode',
                'contact_phone',
                'contact_email',
                'emergency_contact_name',
                'emergency_contact_phone',
            ])
                ->filter(fn (string $column): bool => Schema::hasColumn('carer_profiles', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
