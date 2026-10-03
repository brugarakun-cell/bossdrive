<?php

namespace Tests\Feature;

use App\Models\PickupConditionReport;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_report_delete_buttons_are_wired_to_persistent_delete_routes(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $cancelled = $this->createReservation($user, 'cancelled');
        $voided = $this->createReservation($user, 'void');
        $pickupReservation = $this->createReservation($user, 'released');
        $photoPath = 'reservations/conditions/report-photo.jpg';
        Storage::disk('public')->put($photoPath, 'photo');
        $pickupReport = PickupConditionReport::create([
            'reservation_id' => $pickupReservation->id,
            'user_id' => $user->id,
            'checks' => ['Vehicle checked'],
            'photo_path' => $photoPath,
        ]);

        $this->withSession(['is_admin' => true])
            ->get(route('admin.reports'))
            ->assertOk()
            ->assertSee("deleteRow(this, 'Cancelled Booking', '".route('admin.reservations.destroy', $cancelled), false)
            ->assertSee("deleteRow(this, 'Voided Booking', '".route('admin.reservations.destroy', $voided), false)
            ->assertSee("deleteRow(this, 'Pickup Condition Report', '".route('admin.reports.pickup.destroy', $pickupReport), false);
    }

    public function test_admin_can_delete_cancelled_and_voided_reservations_and_pickup_report_records(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $cancelled = $this->createReservation($user, 'cancelled');
        $voided = $this->createReservation($user, 'void');
        $pickupReservation = $this->createReservation($user, 'released');
        $photoPath = 'reservations/conditions/delete-report-photo.jpg';
        Storage::disk('public')->put($photoPath, 'photo');
        $pickupReport = PickupConditionReport::create([
            'reservation_id' => $pickupReservation->id,
            'user_id' => $user->id,
            'checks' => ['Vehicle checked'],
            'photo_path' => $photoPath,
        ]);

        $this->withSession(['is_admin' => true])
            ->deleteJson(route('admin.reservations.destroy', $cancelled))
            ->assertOk();
        $this->assertDatabaseMissing('reservations', ['id' => $cancelled->id]);

        $this->withSession(['is_admin' => true])
            ->deleteJson(route('admin.reservations.destroy', $voided))
            ->assertOk();
        $this->assertDatabaseMissing('reservations', ['id' => $voided->id]);

        $this->withSession(['is_admin' => true])
            ->deleteJson(route('admin.reports.pickup.destroy', $pickupReport))
            ->assertOk();
        $this->assertDatabaseMissing('pickup_condition_reports', ['id' => $pickupReport->id]);
        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_admin_can_persistently_remove_a_vehicle_feedback_reply(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Feedback Reply Vehicle',
            'plate' => 'FEEDBACK-1',
            'price' => 1800,
            'status' => 'available',
            'feedbacks' => [[
                'name' => 'Renter',
                'date' => 'Jan 1, 2026',
                'stars' => 5,
                'comment' => 'Great vehicle.',
                'adminReply' => [
                    'text' => 'Thank you!',
                    'by' => 'Admin',
                    'date' => 'Jan 2, 2026',
                ],
            ]],
        ]);

        $this->withSession(['is_admin' => true])
            ->deleteJson(route('admin.vehicles.feedback.reply.destroy', [$vehicle, 0]))
            ->assertOk()
            ->assertJsonPath('feedbacks.0.adminReply', null)
            ->assertJsonPath('feedbacks.0.comment', 'Great vehicle.');

        $this->assertNull($vehicle->fresh()->feedbacks[0]['adminReply']);
    }

    public function test_admin_can_delete_a_completed_reservation_and_vehicle_with_json_responses(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'name' => 'Completed Rental Vehicle',
            'plate' => 'COMPLETED-1',
            'price' => 1800,
            'status' => 'available',
        ]);
        $completed = $this->createReservation($user, 'completed');
        $completed->update(['vehicle_id' => $vehicle->id]);

        $this->withSession(['is_admin' => true])
            ->deleteJson(route('admin.reservations.destroy', $completed))
            ->assertOk()
            ->assertJsonPath('message', 'Reservation '.$completed->control_number.' was deleted.');
        $this->assertDatabaseMissing('reservations', ['id' => $completed->id]);

        $this->withSession(['is_admin' => true])
            ->deleteJson(route('admin.vehicles.destroy', $vehicle))
            ->assertOk()
            ->assertJsonPath('message', 'Vehicle removed.');
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    public function test_admin_json_delete_requests_receive_an_authentication_error_when_session_expires(): void
    {
        $reservation = $this->createReservation(User::factory()->create(), 'pending');

        $this->deleteJson(route('admin.reservations.destroy', $reservation))
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Your administrator session has expired. Please sign in again.');
    }

    private function createReservation(User $user, string $status): Reservation
    {
        return Reservation::create([
            'user_id' => $user->id,
            'vehicle' => 'Report Delete Vehicle',
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->addDays(10)->toDateString(),
            'return_date' => now()->addDays(11)->toDateString(),
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'REPORT-DELETE-'.strtoupper($status).'-'.uniqid(),
            'status' => $status,
        ]);
    }
}
