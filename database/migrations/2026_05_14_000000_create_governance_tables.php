<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_complaints', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('complainant_name');
            $table->string('complainant_contact')->nullable();
            $table->string('source');
            $table->string('category');
            $table->string('severity');
            $table->string('status')->default('open');
            $table->text('summary');
            $table->text('outcome')->nullable();
            $table->timestamp('received_at');
            $table->date('due_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('gdpr_cases', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requester_name');
            $table->string('requester_contact')->nullable();
            $table->string('request_type');
            $table->string('risk_level')->default('medium');
            $table->string('status')->default('open');
            $table->text('summary');
            $table->text('outcome')->nullable();
            $table->timestamp('received_at');
            $table->date('response_due_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'request_type']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('governance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('category');
            $table->string('version')->default('v1');
            $table->string('status')->default('draft');
            $table->text('summary');
            $table->date('review_due_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index('review_due_at');
        });

        Schema::create('governance_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('chair_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('meeting_type');
            $table->string('status')->default('scheduled');
            $table->timestamp('scheduled_at');
            $table->text('attendees')->nullable();
            $table->text('agenda');
            $table->text('minutes')->nullable();
            $table->text('outcome')->nullable();
            $table->timestamps();

            $table->index(['status', 'meeting_type']);
            $table->index('scheduled_at');
        });

        Schema::create('governance_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governance_complaint_id')->nullable()->constrained('governance_complaints')->cascadeOnDelete();
            $table->foreignId('gdpr_case_id')->nullable()->constrained('gdpr_cases')->cascadeOnDelete();
            $table->foreignId('governance_policy_id')->nullable()->constrained('governance_policies')->cascadeOnDelete();
            $table->foreignId('governance_meeting_id')->nullable()->constrained('governance_meetings')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->date('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('outcome')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index('due_at');
        });

        $permissionId = DB::table('permissions')->where('name', 'governance.manage')->value('id');

        if ($permissionId === null) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'governance.manage',
                'description' => 'Manage complaints, GDPR cases, and governance actions.',
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
        $permissionId = DB::table('permissions')->where('name', 'governance.manage')->value('id');

        if ($permissionId !== null) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('governance_actions');
        Schema::dropIfExists('governance_meetings');
        Schema::dropIfExists('governance_policies');
        Schema::dropIfExists('gdpr_cases');
        Schema::dropIfExists('governance_complaints');
    }
};
