<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->timestamp('login_created_at')->nullable()->after('last_login_at');
            $table->foreignId('login_created_by')->nullable()->after('login_created_at')->constrained('users')->nullOnDelete();

            $table->index(['login_created_at']);
        });

        DB::table('family_members')
            ->whereNull('login_created_at')
            ->update(['login_created_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->dropForeign(['login_created_by']);
            $table->dropIndex(['login_created_at']);
            $table->dropColumn(['login_created_at', 'login_created_by']);
        });
    }
};
