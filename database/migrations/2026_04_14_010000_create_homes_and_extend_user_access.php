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
        Schema::create('homes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number')->nullable()->unique();
            $table->string('care_type')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('county')->nullable();
            $table->string('postcode');
            $table->string('country')->default('United Kingdom');
            $table->string('status')->default('active');
            $table->string('logo_path')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'name']);
            $table->index('manager_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('home_id')->nullable()->after('password')->constrained('homes')->nullOnDelete();
            $table->string('job_title')->nullable()->after('home_id');
            $table->string('phone')->nullable()->after('job_title');
            $table->boolean('is_active')->default(true)->after('phone');

            $table->index(['home_id', 'is_active']);
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('home_id');
            $table->dropColumn(['job_title', 'phone', 'is_active']);
        });

        Schema::dropIfExists('homes');
    }
};
