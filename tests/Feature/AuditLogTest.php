<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_user_changes_are_written_to_the_audit_trail(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->permissions()->attach(Permission::create([
            'name' => 'users.manage',
            'description' => 'Manage users.',
        ]));

        $role = Role::create([
            'name' => 'Coordinator',
            'description' => 'Coordinates operations.',
        ]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Care Lead',
                'email' => 'care.lead@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'roles' => [$role->id],
            ])
            ->assertRedirect(route('users.index', absolute: false));

        $createdUser = User::where('email', 'care.lead@example.com')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => $createdUser->getMorphClass(),
            'auditable_id' => $createdUser->id,
            'actor_id' => $admin->id,
        ]);

        $accessSyncLog = AuditLog::where('action', 'user_access_synced')
            ->where('auditable_id', $createdUser->id)
            ->first();

        $this->assertNotNull($accessSyncLog);
        $this->assertSame([$role->id], $accessSyncLog->new_values['roles']);
    }

    public function test_audit_log_screen_is_visible_with_permission(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->permissions()->attach(Permission::where('name', 'audit_logs.view')->firstOrFail());

        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'logged_in',
            'event' => 'User',
            'metadata' => ['remember' => false],
        ]);

        $this->actingAs($admin)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Audit Trail')
            ->assertSee('Logged In');
    }
}
