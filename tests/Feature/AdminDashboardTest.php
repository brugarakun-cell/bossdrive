<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_overview_returns_filtered_year_month_week_and_day_rental_counts(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'name' => 'Dashboard Overview Sedan',
            'plate' => 'OVERVIEW 001',
            'price' => 1800,
            'status' => 'available',
        ]);
        $this->makeReservation($user, $vehicle, '2026-10-03 09:00:00', 'pending');
        $this->makeReservation($user, $vehicle, '2026-10-03 13:00:00', 'verified');
        $this->makeReservation($user, $vehicle, '2026-10-04 09:00:00', 'cancelled');
        $this->makeReservation($user, $vehicle, '2026-11-03 09:00:00', 'pending');

        $this->withSession(['is_admin' => true])
            ->getJson(route('admin.dashboard.live', ['period' => 'year', 'date' => '2026-10-03']))
            ->assertOk()
            ->assertJsonPath('chart.period', 'year')
            ->assertJsonCount(12, 'chart.labels')
            ->assertJsonPath('chart.values.9', 2);

        $this->withSession(['is_admin' => true])
            ->getJson(route('admin.dashboard.live', ['period' => 'month', 'date' => '2026-10-03']))
            ->assertOk()
            ->assertJsonCount(31, 'chart.labels')
            ->assertJsonPath('chart.values.2', 2);

        $this->withSession(['is_admin' => true])
            ->getJson(route('admin.dashboard.live', ['period' => 'week', 'date' => '2026-11-08']))
            ->assertOk()
            ->assertJsonCount(7, 'chart.labels')
            ->assertJsonPath('chart.values.1', 1);

        $this->withSession(['is_admin' => true])
            ->getJson(route('admin.dashboard.live', ['period' => 'day', 'date' => '2026-10-03']))
            ->assertOk()
            ->assertJsonCount(24, 'chart.labels')
            ->assertJsonPath('chart.values.9', 1)
            ->assertJsonPath('chart.values.13', 1);
    }

    public function test_active_users_are_tracked_by_recent_authenticated_activity(): void
    {
        $activeUser = User::factory()->create([
            'role' => 'user',
            'last_seen_at' => now()->subMinute(),
        ]);
        User::factory()->create([
            'role' => 'user',
            'last_seen_at' => now()->subMinutes(10),
        ]);
        User::factory()->create([
            'role' => 'admin',
            'last_seen_at' => now(),
        ]);

        $this->withSession(['is_admin' => true])
            ->getJson(route('admin.dashboard.live'))
            ->assertOk()
            ->assertJsonPath('activeUserCount', 1)
            ->assertJsonPath('activeUsers.0.email', $activeUser->email);

        $activeUser->forceFill(['last_seen_at' => null])->save();
        $this->actingAs($activeUser)
            ->get(route('user.dashboard'))
            ->assertOk();
        $this->assertNotNull($activeUser->fresh()->last_seen_at);
    }

    private function makeReservation(User $user, Vehicle $vehicle, string $createdAt, string $status): void
    {
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'vehicle' => $vehicle->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => '2026-11-01',
            'return_date' => '2026-11-02',
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'OVERVIEW-'.strtoupper(str_replace(['-', ':', ' '], '', $createdAt)),
            'status' => $status,
        ]);
        $reservation->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
    }
}
