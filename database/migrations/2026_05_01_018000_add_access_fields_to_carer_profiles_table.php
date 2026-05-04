<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('carer_profiles', 'account_status')) {
                $table->string('account_status')->default('pending')->after('shift_preference');
            }

            if (! Schema::hasColumn('carer_profiles', 'mfa_enabled')) {
                $table->boolean('mfa_enabled')->default(false)->after('account_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carer_profiles', function (Blueprint $table) {
            $columns = collect(['account_status', 'mfa_enabled'])
                ->filter(fn (string $column): bool => Schema::hasColumn('carer_profiles', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
