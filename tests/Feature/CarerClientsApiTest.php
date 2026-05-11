<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Home;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CarerClientsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_carer_can_fetch_active_clients_for_their_home(): void
    {
        $carerRole = Role::create([
            'name' => 'Carer',
            'description' => 'Delivers care visits.',
            'is_active' => true,
        ]);
        $home = Home::create([
            'name' => 'Oak House',
            'address_line_1' => '1 Care Street',
            'city' => 'Bristol',
            'postcode' => 'BS1 1AA',
            'country' => 'United Kingdom',
            'status' => 'active',
        ]);
        $otherHome = Home::create([
            'name' => 'Rose House',
            'address_line_1' => '2 Care Street',
            'city' => 'Bristol',
            'postcode' => 'BS2 2AA',
            'country' => 'United Kingdom',
            'status' => 'active',
        ]);
        $carer = User::create([
            'name' => 'Default Carer',
            'email' => 'carer@example.com',
            'password' => Hash::make('password'),
            'home_id' => $home->id,
            'is_active' => true,
        ]);
        $carer->roles()->attach($carerRole);

        Client::create([
            'home_id' => $home->id,
            'first_name' => 'Asha',
            'last_name' => 'Patel',
            'address' => '10 Client Road',
            'phone' => '07123456789',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);
        Client::create([
            'home_id' => $home->id,
            'first_name' => 'Inactive',
            'last_name' => 'Client',
            'status' => 'inactive',
        ]);
        Client::create([
            'home_id' => $otherHome->id,
            'first_name' => 'Other',
            'last_name' => 'Client',
            'status' => 'active',
        ]);

        $response = $this->getJson(route('api.carer.clients', [
            'carer_id' => $carer->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'clients')
            ->assertJsonPath('clients.0.name', 'Asha Patel')
            ->assertJsonPath('clients.0.home_name', 'Oak House')
            ->assertJsonPath('clients.0.status', 'active');
    }
}
