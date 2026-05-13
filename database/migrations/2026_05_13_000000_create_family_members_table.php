<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->boolean('can_view_care_updates')->default(false);
            $table->boolean('can_view_medication')->default(false);
            $table->boolean('can_view_invoices')->default(false);
            $table->boolean('can_receive_incident_alerts')->default(false);
            $table->boolean('can_view_appointments')->default(false);
            $table->boolean('can_view_visits')->default(false);
            $table->boolean('can_upload_documents')->default(false);
            $table->boolean('can_view_staff_messages')->default(false);
            $table->boolean('can_view_shared_documents')->default(false);
            $table->boolean('can_view_sensitive_documents')->default(false);
            $table->boolean('can_view_safeguarding')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->text('access_notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'is_active']);
            $table->index(['home_id', 'is_active']);
        });

        $permissionId = DB::table('permissions')->where('name', 'family_members.manage')->value('id');

        if ($permissionId === null) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'family_members.manage',
                'description' => 'Create and manage permissioned family access.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('roles')
            ->whereIn('name', ['Administrator', 'Home Manager'])
            ->pluck('id')
            ->each(function (int $roleId) use ($permissionId): void {
                DB::table('permission_role')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ], []);
            });
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'family_members.manage')->value('id');

        if ($permissionId !== null) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('family_members');
    }
};
