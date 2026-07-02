<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ControlledDrugRegisterEntry;
use App\Models\Home;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class ControlledDrugRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_controlled_drug_register_records_received_stock_and_administration(): void
    {
        $manager = $this->manager();
        $witness = User::create([
            'name' => 'Second Nurse',
            'email' => 'witness@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $home = Home::create([
            'name' => 'Green View',
            'address_line_1' => '1 Care Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'status' => 'active',
        ]);
        $client = Client::create([
            'home_id' => $home->id,
            'first_name' => 'Ada',
            'last_name' => 'Resident',
            'status' => 'active',
        ]);

        $this->actingAs($manager)
            ->post(route('controlled-drugs.store'), [
                'home_id' => $home->id,
                'transaction_type' => 'received',
                'occurred_at' => '2026-07-01 09:00:00',
                'drug_name' => 'Morphine Sulfate',
                'form' => 'Liquid',
                'strength' => '10mg/5ml',
                'unit' => 'ml',
                'quantity' => '100',
                'actual_balance_after' => '100',
                'source_or_destination' => 'Main Pharmacy',
                'batch_number' => 'BATCH-1',
                'expiry_date' => '2027-07-01',
            ])
            ->assertRedirect(route('controlled-drugs.index', absolute: false))
            ->assertSessionHasNoErrors();

        $this->actingAs($manager)
            ->post(route('controlled-drugs.store'), [
                'home_id' => $home->id,
                'client_id' => $client->id,
                'transaction_type' => 'administered',
                'occurred_at' => '2026-07-01 10:00:00',
                'drug_name' => 'Morphine Sulfate',
                'form' => 'Liquid',
                'strength' => '10mg/5ml',
                'unit' => 'ml',
                'quantity' => '5',
                'actual_balance_after' => '95',
                'reason' => 'Prescribed breakthrough pain relief.',
                'witness_user_id' => $witness->id,
            ])
            ->assertRedirect(route('controlled-drugs.index', absolute: false))
            ->assertSessionHasNoErrors();

        $administration = ControlledDrugRegisterEntry::query()
            ->where('transaction_type', 'administered')
            ->firstOrFail();

        $this->assertSame('-5.00', $administration->signed_quantity);
        $this->assertSame('100.00', $administration->expected_balance_before);
        $this->assertSame('95.00', $administration->expected_balance_after);
        $this->assertSame('95.00', $administration->actual_balance_after);
        $this->assertTrue($administration->witness_required);
        $this->assertSame($witness->id, $administration->witness_user_id);
    }

    public function test_discrepancy_reason_is_only_required_for_negative_reasons(): void
    {
        $manager = $this->manager();
        $home = Home::create([
            'name' => 'Green View',
            'address_line_1' => '1 Care Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'status' => 'active',
        ]);

        $this->actingAs($manager)
            ->post(route('controlled-drugs.store'), [
                'home_id' => $home->id,
                'transaction_type' => 'received',
                'occurred_at' => '2026-07-01 09:00:00',
                'drug_name' => 'Diazepam',
                'unit' => 'tablets',
                'quantity' => '28',
                'actual_balance_after' => '27',
                'reason' => 'Prescribed regular dose',
            ])
            ->assertRedirect(route('controlled-drugs.index', absolute: false))
            ->assertSessionHasNoErrors();

        $this->actingAs($manager)
            ->post(route('controlled-drugs.store'), [
                'home_id' => $home->id,
                'transaction_type' => 'wasted',
                'occurred_at' => '2026-07-01 10:00:00',
                'drug_name' => 'Diazepam',
                'unit' => 'tablets',
                'quantity' => '1',
                'actual_balance_after' => '25',
                'reason' => 'Dropped or spoiled dose',
                'witness_name' => 'Second Witness',
            ])
            ->assertSessionHasErrors('discrepancy_reason');
    }

    public function test_other_dropdown_values_are_saved_as_custom_text(): void
    {
        $manager = $this->manager();
        $home = Home::create([
            'name' => 'Green View',
            'address_line_1' => '1 Care Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'status' => 'active',
        ]);

        $this->actingAs($manager)
            ->post(route('controlled-drugs.store'), [
                'home_id' => $home->id,
                'transaction_type' => 'received',
                'occurred_at' => '2026-07-01 09:00:00',
                'drug_name' => ControlledDrugRegisterEntry::OTHER_VALUE,
                'drug_name_other' => 'Custom Controlled Drug',
                'form' => ControlledDrugRegisterEntry::OTHER_VALUE,
                'form_other' => 'Sachet',
                'unit' => ControlledDrugRegisterEntry::OTHER_VALUE,
                'unit_other' => 'sachets',
                'quantity' => '12',
                'actual_balance_after' => '12',
                'source_or_destination' => ControlledDrugRegisterEntry::OTHER_VALUE,
                'source_or_destination_other' => 'Specialist pharmacy',
            ])
            ->assertRedirect(route('controlled-drugs.index', absolute: false))
            ->assertSessionHasNoErrors();

        $entry = ControlledDrugRegisterEntry::query()->firstOrFail();

        $this->assertSame('Custom Controlled Drug', $entry->drug_name);
        $this->assertSame('Sachet', $entry->form);
        $this->assertSame('sachets', $entry->unit);
        $this->assertSame('Specialist pharmacy', $entry->source_or_destination);
    }

    public function test_submitted_entries_cannot_be_edited(): void
    {
        $entry = ControlledDrugRegisterEntry::create([
            'home_id' => Home::create([
                'name' => 'Green View',
                'address_line_1' => '1 Care Street',
                'city' => 'London',
                'postcode' => 'SW1A 1AA',
                'status' => 'active',
            ])->id,
            'transaction_type' => 'received',
            'occurred_at' => now(),
            'drug_name' => 'Oxycodone',
            'unit' => 'capsules',
            'stock_key' => ControlledDrugRegisterEntry::stockKeyFor('Oxycodone', null, null, 'capsules'),
            'quantity' => 10,
            'signed_quantity' => 10,
            'expected_balance_before' => 0,
            'expected_balance_after' => 10,
            'actual_balance_after' => 10,
            'submitted_at' => now(),
        ]);

        $this->expectException(LogicException::class);

        $entry->update(['quantity' => 11]);
    }

    private function manager(): User
    {
        $permission = Permission::firstOrCreate([
            'name' => 'controlled_drugs.manage',
        ], [
            'description' => 'Manage controlled drugs',
        ]);

        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $manager->permissions()->attach($permission);

        return $manager;
    }
}
