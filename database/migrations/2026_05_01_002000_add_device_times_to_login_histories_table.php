<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('login_histories', function (Blueprint $table) {
            $table->string('logged_in_device_at')->nullable()->after('logout_timezone');
            $table->string('logged_out_device_at')->nullable()->after('logged_in_device_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_histories', function (Blueprint $table) {
            $table->dropColumn(['logged_in_device_at', 'logged_out_device_at']);
        });
    }
};
