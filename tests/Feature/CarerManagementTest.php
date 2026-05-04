<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Home;
use App\Models\User;
use App\Models\CarerTrainingRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CarerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_carer_crud_creates_user_with_carer_role(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->permissions()->attach(Permission::create([
            'name' => 'carers.manage',
            'description' => 'Manage carers',
        ]));
        Role::create([
            'name' => 'Carer',
            'description' => 'Delivers visits',
        ]);
        $home = Home::create([
            'name' => 'Green View',
            'address_line_1' => '1 Care Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('carers.store'), [
                'name' => 'Alex Carer',
                'email' => 'alex.carer@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'home_id' => $home->id,
                'job_title' => 'Carer',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $carer = User::where('email', 'alex.carer@example.com')->firstOrFail();

        $this->assertTrue($carer->roles()->where('name', 'Carer')->exists());
        $this->assertSame($home->id, $carer->home_id);
        $this->assertNotNull($carer->carerProfile);
        $this->assertFalse($carer->is_active);
    }

    public function test_incomplete_carer_cannot_be_activated_from_list_toggle(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->permissions()->attach(Permission::create([
            'name' => 'carers.manage',
            'description' => 'Manage carers',
        ]));
        $carerRole = Role::create([
            'name' => 'Carer',
            'description' => 'Delivers visits',
        ]);
        $home = Home::create([
            'name' => 'Green View',
            'address_line_1' => '1 Care Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'status' => 'active',
        ]);
        $carer = User::create([
            'name' => 'Alex Carer',
            'email' => 'alex.carer@example.com',
            'password' => Hash::make('password'),
            'home_id' => $home->id,
            'job_title' => 'Carer',
            'is_active' => false,
        ]);
        $carer->roles()->attach($carerRole);
        $carer->carerProfile()->create([
            'status' => 'onboarding',
        ]);

        $this->actingAs($admin)
            ->delete(route('carers.destroy', $carer))
            ->assertRedirect(route('carers.index', absolute: false))
            ->assertSessionHasErrors('assessment');

        $this->assertFalse($carer->refresh()->is_active);
    }

    public function test_carer_assessment_saves_contact_and_emergency_details(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->permissions()->attach(Permission::create([
            'name' => 'carers.manage',
            'description' => 'Manage carers',
        ]));
        $carerRole = Role::create([
            'name' => 'Carer',
            'description' => 'Delivers visits',
        ]);
        $home = Home::create([
            'name' => 'Green View',
            'address_line_1' => '1 Care Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'status' => 'active',
        ]);
        $carer = User::create([
            'name' => 'Alex Carer',
            'email' => 'alex.carer@example.com',
            'password' => Hash::make('password'),
            'home_id' => $home->id,
            'job_title' => 'Carer',
            'is_active' => false,
        ]);
        $carer->roles()->attach($carerRole);

        $this->actingAs($admin)
            ->put(route('carers.assessments.update', $carer), [
                'legal_name' => 'Alex Carer',
                'date_of_birth' => '1990-01-01',
                'national_insurance_number' => 'AA123456A',
                'photo_id_type' => 'passport',
                'id_document_number' => 'ABC123456',
                'right_to_work_status' => 'uk_citizen',
                'address_line_1' => '22 High Street',
                'address_line_2' => 'Flat 4',
                'city' => 'London',
                'postcode' => 'SW1A 1AA',
                'contact_phone' => '07123 456 789',
                'contact_email' => 'alex.carer@example.com',
                'emergency_contact_name' => 'Sam Contact',
                'emergency_contact_phone' => '07123 111 222',
                'job_title' => 'senior_carer',
                'employment_type' => 'full_time',
                'start_date' => '2026-05-01',
                'assigned_home_id' => $home->id,
                'dbs_check_status' => 'verified',
                'dbs_certificate_number' => 'DBS-123456',
                'dbs_expiry_date' => '2027-05-01',
                'safeguarding_training_completed' => 'yes',
                'last_safeguarding_training_date' => '2026-04-01',
                'trainings' => collect(CarerTrainingRecord::MANDATORY_TRAINING)
                    ->mapWithKeys(fn (string $trainingName, string $trainingKey): array => [
                        $trainingKey => [
                            'status' => 'completed',
                            'expiry_date' => '2027-05-01',
                        ],
                    ])
                    ->all(),
                'occupational_health_clearance' => 'fit',
                'immunisation_status' => 'up_to_date',
                'fit_to_work_declaration' => '1',
                'skills' => ['dementia_care', 'mobility_support'],
                'languages' => ['english', 'spanish'],
                'availability_pattern' => 'full_time',
                'max_weekly_hours' => '40',
                'shift_preference' => 'both',
                'account_status' => 'active',
                'mfa_enabled' => '1',
                'data_processing_consent' => '1',
                'privacy_policy_accepted' => '1',
                'data_retention_category' => 'active_staff',
            ])
            ->assertRedirect(route('carers.assessments.edit', $carer, absolute: false));

        $profile = $carer->carerProfile()->firstOrFail();

        $this->assertSame('22 High Street', $profile->address_line_1);
        $this->assertSame('Flat 4', $profile->address_line_2);
        $this->assertSame('London', $profile->city);
        $this->assertSame('SW1A 1AA', $profile->postcode);
        $this->assertSame('07123 456 789', $profile->contact_phone);
        $this->assertSame('alex.carer@example.com', $profile->contact_email);
        $this->assertSame('Sam Contact', $profile->emergency_contact_name);
        $this->assertSame('07123 111 222', $profile->emergency_contact_phone);
        $this->assertSame('senior_carer', $profile->job_title);
        $this->assertSame('full_time', $profile->employment_type);
        $this->assertSame($home->id, $profile->assigned_home_id);
        $this->assertSame('verified', $profile->dbs_check_status);
        $this->assertSame('DBS-123456', $profile->dbs_certificate_number);
        $this->assertSame('yes', $profile->safeguarding_training_completed);
        $this->assertSame('Senior Carer', $carer->refresh()->job_title);
        $this->assertCount(5, $profile->trainingRecords);
        $this->assertTrue($profile->trainingRecords->every(fn (CarerTrainingRecord $record): bool => $record->status === 'completed'));
        $this->assertSame('fit', $profile->occupational_health_clearance);
        $this->assertSame('up_to_date', $profile->immunisation_status);
        $this->assertTrue($profile->fit_to_work_declaration);
        $this->assertSame(['dementia_care', 'mobility_support'], $profile->skills);
        $this->assertSame(['english', 'spanish'], $profile->languages);
        $this->assertSame('full_time', $profile->availability_pattern);
        $this->assertSame(40, $profile->max_weekly_hours);
        $this->assertSame('both', $profile->shift_preference);
        $this->assertSame('active', $profile->account_status);
        $this->assertTrue($profile->mfa_enabled);
        $this->assertTrue($profile->data_processing_consent);
        $this->assertNotNull($profile->data_processing_consented_at);
        $this->assertTrue($profile->privacy_policy_accepted);
        $this->assertNotNull($profile->privacy_policy_accepted_at);
        $this->assertSame('v1', $profile->privacy_policy_version);
        $this->assertSame('active_staff', $profile->data_retention_category);
        $this->assertTrue($carer->refresh()->is_active);
    }
}
