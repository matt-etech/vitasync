<?php

namespace Tests\Feature;

use App\Models\CarePlan;
use App\Models\Client;
use App\Models\Home;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitTimeMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VisitTimeMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_sends_due_reminders_and_marks_unattended_expired_visits_missed(): void
    {
        $now = Carbon::parse('2026-05-08 08:45:00');
        [$client, $carePlan, $worker] = $this->createVisitFixtures();

        $upcomingVisit = Visit::create([
            'home_id' => $client->home_id,
            'client_id' => $client->id,
            'care_plan_id' => $carePlan->id,
            'assigned_user_id' => $worker->id,
            'title' => 'Morning visit',
            'scheduled_start_at' => $now->copy()->addMinutes(10),
            'scheduled_end_at' => $now->copy()->addMinutes(40),
            'status' => 'scheduled',
        ]);
        $expiredVisit = Visit::create([
            'home_id' => $client->home_id,
            'client_id' => $client->id,
            'care_plan_id' => $carePlan->id,
            'assigned_user_id' => $worker->id,
            'title' => 'Missed morning visit',
            'scheduled_start_at' => $now->copy()->subHour(),
            'scheduled_end_at' => $now->copy()->subMinute(),
            'status' => 'scheduled',
        ]);

        $result = app(VisitTimeMonitor::class)->process($now);

        $this->assertSame(['reminders_sent' => 1, 'missed_marked' => 1], $result);
        $this->assertSame('missed', $expiredVisit->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'visit.reminder_15_minute_sent',
            'auditable_id' => $upcomingVisit->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.alert.visit_missed',
            'auditable_id' => $expiredVisit->id,
        ]);

        $secondResult = app(VisitTimeMonitor::class)->process($now);

        $this->assertSame(['reminders_sent' => 0, 'missed_marked' => 0], $secondResult);
    }

    /**
     * @return array{0: Client, 1: CarePlan, 2: User}
     */
    private function createVisitFixtures(): array
    {
        $home = Home::create([
            'name' => 'Oak House',
            'address_line_1' => '1 Care Street',
            'city' => 'Bristol',
            'postcode' => 'BS1 1AA',
            'country' => 'United Kingdom',
            'status' => 'active',
        ]);
        $client = Client::create([
            'home_id' => $home->id,
            'first_name' => 'Asha',
            'last_name' => 'Patel',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);
        $carePlan = CarePlan::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'title' => 'Morning support plan',
            'plan_type' => 'Initial',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $worker = User::create([
            'name' => 'Care Worker',
            'email' => 'visit-monitor-worker@example.com',
            'password' => Hash::make('password'),
            'home_id' => $home->id,
            'is_active' => true,
        ]);

        return [$client, $carePlan, $worker];
    }
}
