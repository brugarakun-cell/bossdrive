<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_vehicle_availability_returns_live_reservations_and_maintenance(): void
    {
        $user = User::factory()->create();
        $reservedVehicle = Vehicle::create([
            'name' => 'Live Availability Sedan',
            'plate' => 'LIVE 1001',
            'price' => 1800,
            'status' => 'available',
        ]);
        $maintenanceVehicle = Vehicle::create([
            'name' => 'Live Availability SUV',
            'plate' => 'LIVE 1002',
            'price' => 2500,
            'status' => 'maintenance',
            'schedule' => [[
                'id' => 1,
                'type' => 'Maintenance',
                'start' => now()->addDays(2)->toDateString(),
                'end' => now()->addDays(4)->toDateString(),
            ]],
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $reservedVehicle->id,
            'vehicle' => $reservedVehicle->name,
            'service_option' => 'delivery',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->addDays(5)->toDateString(),
            'return_date' => now()->addDays(7)->toDateString(),
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'LIVE-AVAILABILITY-0001',
            'status' => 'verified',
        ]);

        $availability = $this->getJson(route('vehicles.availability'))
            ->assertOk()
            ->assertJsonMissingPath('reservations.0.customer_email')
            ->assertJsonMissingPath('reservations.0.controlNumber')
            ->json();
        $reserved = collect($availability['vehicles'])->firstWhere('id', $reservedVehicle->id);
        $maintenance = collect($availability['vehicles'])->firstWhere('id', $maintenanceVehicle->id);

        $this->assertSame('Rented', $reserved['status']);
        $this->assertSame('reserved', $reserved['currentReservation']['status']);
        $this->assertSame($reservation->id, $reserved['schedule'][0]['reservationId']);
        $this->assertSame('Special', $reserved['schedule'][0]['type']);
        $this->assertSame('Maintenance', $maintenance['status']);
        $this->assertSame($maintenanceVehicle->id, $availability['maintenance'][0]['vehicleId']);

        $this->withSession(['is_admin' => true])
            ->getJson(route('vehicles.availability'))
            ->assertOk()
            ->assertJsonPath('reservations.0.controlNumber', 'LIVE-AVAILABILITY-0001');
    }

    public function test_dashboard_live_endpoint_returns_all_own_bookings_for_tracker_updates(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $vehicle = Vehicle::create([
            'name' => 'Live Tracker Sedan',
            'plate' => 'TRACK 1001',
            'price' => 1800,
            'status' => 'rented',
        ]);
        $olderReservation = Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'vehicle' => $vehicle->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->addDays(2)->toDateString(),
            'return_date' => now()->addDays(3)->toDateString(),
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'TRACKER-OLDER-0001',
            'status' => 'verified',
        ]);
        $latestReservation = Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'vehicle' => $vehicle->name,
            'service_option' => 'delivery',
            'driver_option' => 'with_driver',
            'pickup_date' => now()->addDays(5)->toDateString(),
            'return_date' => now()->addDays(6)->toDateString(),
            'delivery_address' => 'SM Dasma',
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'full',
            'control_number' => 'TRACKER-LATEST-0002',
            'status' => 'processing',
        ]);
        Reservation::create([
            'user_id' => $otherUser->id,
            'vehicle' => 'Private Reservation',
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->addDays(4)->toDateString(),
            'return_date' => now()->addDays(5)->toDateString(),
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'PRIVATE-TRACKER-0003',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->getJson(route('user.dashboard.live'))->assertOk();
        $liveReservations = collect($response->json('reservations'));

        $this->assertCount(2, $liveReservations);
        $this->assertSame($latestReservation->id, $liveReservations->first()['id']);
        $this->assertSame('PROCESSING', $liveReservations->first()['status']);
        $this->assertSame('SM Dasma', $liveReservations->first()['delivery_address']);
        $this->assertSame($olderReservation->id, $liveReservations->last()['id']);
        $this->assertStringNotContainsString('PRIVATE-TRACKER-0003', $response->getContent());

        $this->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('rentalProcessTracker')
            ->assertSee('syncDashboard')
            ->assertSee('car-loader-icon')
            ->assertSee('dashboardTripContent');
    }

    public function test_verified_user_can_update_documents_and_admin_can_review_the_latest_upload(): void
    {
        Storage::fake('public');

        $verifiedAt = now()->subDay();
        $user = User::factory()->create([
            'documents_verified_at' => $verifiedAt,
            'documents_rejected_at' => null,
        ]);
        $vehicle = Vehicle::create([
            'name' => 'Document Review Sedan',
            'plate' => 'DOC 1001',
            'price' => 1800,
            'status' => 'rented',
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'vehicle' => $vehicle->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => '2026-10-01',
            'return_date' => '2026-10-03',
            'document_paths' => [
                'driver_license' => 'reservations/documents/old-license.png',
                'valid_id' => 'reservations/documents/old-id.png',
                'proof_of_billing' => 'reservations/documents/bill.png',
            ],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'DOC-REVIEW-0001',
            'status' => 'verified',
        ]);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('class="row g-3 mb-4 dashboard-summary-row"', false)
            ->assertSee('Update Documents')
            ->assertSee('accept=".jpg,.jpeg,.png,.webp,.gif"', false);

        $this->post(route('user.reservations.documents.update', $reservation), [
            'document_type' => 'valid_id',
            'document' => UploadedFile::fake()->create('replacement-id.png', 100, 'image/png'),
        ])->assertRedirect()
            ->assertSessionHas('success');

        $reservation->refresh();
        $user->refresh();
        $replacementPath = $reservation->document_paths['valid_id'];
        $this->assertNotSame('reservations/documents/old-id.png', $replacementPath);
        Storage::disk('public')->assertExists($replacementPath);
        $this->assertTrue($user->documents_rejected_at->greaterThan($user->documents_verified_at));
        $this->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Updated documents are waiting for admin review.');

        $this->withSession(['is_admin' => true])
            ->get(route('admin.reservations'))
            ->assertOk()
            ->assertSee('documentsNeedReview":true', false)
            ->assertSee('Confirm Updated Documents');

        $this->withSession(['is_admin' => true])
            ->get(route('admin.reservations.live'))
            ->assertOk()
            ->assertJsonFragment([
                'documentsNeedReview' => true,
                'valid_id' => asset('storage/'.$replacementPath),
            ]);

        $this->withSession(['is_admin' => true])
            ->patchJson(route('admin.reservations.status', $reservation), [
                'status' => 'verified',
                'verify_documents' => true,
            ])
            ->assertOk();

        $this->assertNull($user->fresh()->documents_rejected_at);
    }

    public function test_users_can_replace_documents_for_any_reservation_status_and_admin_review_keeps_status(): void
    {
        Storage::fake('public');

        foreach (['pending', 'verified', 'processing', 'released', 'completed', 'cancelled', 'void'] as $status) {
            $user = User::factory()->create([
                'documents_verified_at' => now()->subDay(),
                'documents_rejected_at' => null,
            ]);
            $vehicle = Vehicle::create([
                'name' => 'Document Update '.$status,
                'plate' => 'DOC '.strtoupper(substr($status, 0, 4)).random_int(100, 999),
                'price' => 1800,
                'status' => 'available',
            ]);
            $reservation = Reservation::create([
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'vehicle' => $vehicle->name,
                'service_option' => 'pickup',
                'driver_option' => 'self_drive',
                'pickup_date' => now()->addDays(10)->toDateString(),
                'return_date' => now()->addDays(11)->toDateString(),
                'document_paths' => [
                    'driver_license' => 'reservations/documents/old-license.png',
                    'valid_id' => 'reservations/documents/old-id.png',
                    'proof_of_billing' => 'reservations/documents/bill.png',
                ],
                'privacy_consent' => true,
                'payment_mode' => 'deposit',
                'control_number' => 'DOC-UPDATE-'.strtoupper($status).random_int(100, 999),
                'status' => $status,
                'rejection_comment' => $status === 'cancelled' ? 'Cancelled by customer.' : null,
            ]);

            $this->actingAs($user)
                ->get(route('user.dashboard'))
                ->assertOk()
                ->assertSee('Update Documents / Replace File');

            $this->post(route('user.reservations.documents.update', $reservation), [
                'document_type' => 'valid_id',
                'document' => UploadedFile::fake()->create('replacement-'.$status.'.png', 100, 'image/png'),
            ])->assertRedirect()
                ->assertSessionHas('success');

            $reservation->refresh();
            $user->refresh();
            Storage::disk('public')->assertExists($reservation->document_paths['valid_id']);
            $this->assertTrue($user->documents_rejected_at->greaterThan($user->documents_verified_at), $status);

            if ($status === 'released') {
                $this->withSession(['is_staff' => true])
                    ->patchJson(route('staff.reservations.status', $reservation), [
                        'status' => 'completed',
                        'return_condition_checks' => ['Vehicle condition checked'],
                    ])
                    ->assertUnprocessable()
                    ->assertJsonPath('message', 'Updated customer documents must be reviewed before this reservation can be returned.');
            }

            $this->withSession(['is_admin' => true])
                ->get(route('admin.reservations'))
                ->assertOk()
                ->assertSee('documentsNeedReview":true', false)
                ->assertSee('Confirm Updated Documents');

            $this->withSession(['is_admin' => true])
                ->patchJson(route('admin.reservations.status', $reservation), [
                    'status' => $status,
                    'verify_documents' => true,
                ])
                ->assertOk();

            $reservation->refresh();
            $this->assertSame($status, $reservation->status, $status);
            $this->assertNull($user->fresh()->documents_rejected_at, $status);
        }
    }

    public function test_user_cannot_update_another_customers_booking_documents(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $vehicle = Vehicle::create([
            'name' => 'Private Documents Sedan',
            'plate' => 'DOC 1002',
            'price' => 1800,
            'status' => 'rented',
        ]);
        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'vehicle_id' => $vehicle->id,
            'vehicle' => $vehicle->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => '2026-10-01',
            'return_date' => '2026-10-03',
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'DOC-REVIEW-0002',
            'status' => 'pending',
        ]);

        $this->actingAs($otherUser)
            ->post(route('user.reservations.documents.update', $reservation), [
                'document_type' => 'valid_id',
                'document' => UploadedFile::fake()->create('replacement-id.png', 100, 'image/png'),
            ])
            ->assertForbidden();
    }

    public function test_reservations_and_date_conflicts_are_scoped_to_the_selected_unit(): void
    {
        $user = User::factory()->create();
        $firstUnit = Vehicle::create([
            'name' => 'Honda Civic',
            'plate' => 'ABC 1234',
            'price' => 1800,
            'status' => 'rented',
        ]);
        $secondUnit = Vehicle::create([
            'name' => 'Honda Civic',
            'plate' => 'DEF 5678',
            'price' => 1800,
            'status' => 'available',
        ]);

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $firstUnit->id,
            'vehicle' => 'Honda Civic',
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => '2026-10-01',
            'return_date' => '2026-10-03',
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'TEST-UNIT-0001',
            'status' => 'pending',
        ]);

        $this->assertTrue($firstUnit->hasAvailabilityConflict('2026-10-02', '2026-10-04'));
        $this->assertFalse($secondUnit->hasAvailabilityConflict('2026-10-02', '2026-10-04'));
        $this->assertSame($firstUnit->id, $reservation->vehicleUnit->id);
        $this->assertCount(1, $firstUnit->reservations);
        $this->assertCount(0, $secondUnit->reservations);
        $this->assertSame('available', $secondUnit->fresh()->status);

        $guestCatalog = $this->get(route('reservations'))->assertOk();
        $this->assertStringContainsString('Stock: 3 Units', $guestCatalog->getContent());

        $this->actingAs($user)
            ->get(route('user.reservations'))
            ->assertOk();
    }

    public function test_guest_selected_unit_is_preserved_through_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('test-password'),
        ]);
        $unit = Vehicle::create([
            'name' => 'Honda Civic',
            'plate' => 'DEF 5678',
            'price' => 1800,
            'status' => 'available',
        ]);

        $this->get(route('login', ['vehicle_id' => $unit->id]))
            ->assertOk()
            ->assertSessionHas('booking_vehicle_id', $unit->id);

        $this->post(route('user.login.submit'), [
            'email' => $user->email,
            'password' => 'test-password',
        ])->assertRedirect(route('user.reservations', ['vehicle_id' => $unit->id]));
    }

    public function test_admin_and_staff_vehicle_management_receive_every_same_model_unit(): void
    {
        Vehicle::create([
            'name' => 'Honda Civic',
            'plate' => 'ADM 1001',
            'price' => 1800,
            'status' => 'available',
        ]);
        Vehicle::create([
            'name' => 'Honda Civic',
            'plate' => 'ADM 1002',
            'price' => 1800,
            'status' => 'maintenance',
        ]);

        $adminPage = $this->withSession(['is_admin' => true])
            ->get(route('admin.vehicles'))
            ->assertOk();
        $this->assertStringContainsString('ADM 1001', $adminPage->getContent());
        $this->assertStringContainsString('ADM 1002', $adminPage->getContent());
        $this->assertStringContainsString("Stock: ' + units.length", $adminPage->getContent());
        $this->assertStringContainsString('.admin-sidebar { width:250px;', $adminPage->getContent());
        $this->assertStringContainsString('.main-content { margin-left:250px;', $adminPage->getContent());
        $this->assertStringContainsString('.admin-top-nav { margin-left:250px; width:calc(100% - 250px);', $adminPage->getContent());
        $this->assertStringContainsString('syncFleetAvailability', $adminPage->getContent());
        $this->assertStringContainsString('/vehicles/availability', $adminPage->getContent());
        $this->assertStringContainsString('car-loader-icon', $adminPage->getContent());
        $this->assertSame(2, substr_count($adminPage->getContent(), '>Pick-up / Expanded</option>'));
        $this->assertSame(2, substr_count($adminPage->getContent(), '>Expanded</option>'));
        $this->assertStringContainsString('function displayVehicleCategory(category)', $adminPage->getContent());
        $this->assertStringContainsString("return 'Expanded';", $adminPage->getContent());
        $this->assertSame(1, substr_count($adminPage->getContent(), 'id="vehicleSpecificationsModal"'));
        $this->assertStringContainsString('View Specifications', $adminPage->getContent());
        $this->assertStringContainsString('function openVehicleSpecifications(vehicleId)', $adminPage->getContent());
        $this->assertStringContainsString("['Transmission Type', vehicle.transmission]", $adminPage->getContent());
        $this->assertStringContainsString("['Fuel Type', vehicle.fuel]", $adminPage->getContent());
        $this->assertStringContainsString("['Capacity', vehicle.capacity_type", $adminPage->getContent());
        $this->assertStringNotContainsString("' + v.transmission + '</span>'", $adminPage->getContent());
        $this->assertStringContainsString("formData.append('status', addStatus)", $adminPage->getContent());
        $this->assertStringContainsString("status: addStatus === 'maintenance' ? 'Maintenance' : 'Available'", $adminPage->getContent());

        $this->postJson(route('admin.vehicles.store'), [
            'name' => 'Honda Civic',
            'plate' => 'ADM 1003',
            'price' => 1800,
            'status' => 'maintenance',
        ])->assertCreated();
        $this->assertDatabaseHas('vehicles', [
            'name' => 'Honda Civic',
            'plate' => 'ADM 1003',
            'status' => 'maintenance',
        ]);

        $staffPage = $this->withSession(['is_staff' => true])
            ->get(route('staff.vehicles'))
            ->assertOk();
        $this->assertStringContainsString('ADM 1001', $staffPage->getContent());
        $this->assertStringContainsString('ADM 1002', $staffPage->getContent());
        $this->assertStringContainsString('ADM 1003', $staffPage->getContent());
        $this->assertStringContainsString("Stock: ' + units.length", $staffPage->getContent());
        $this->assertStringContainsString('.staff-sidebar { width:250px;', $staffPage->getContent());
        $this->assertStringContainsString('.staff-main-content { margin-left:250px;', $staffPage->getContent());
        $this->assertStringContainsString('.staff-top-nav { margin-left:250px; width:calc(100% - 250px);', $staffPage->getContent());
        $this->assertStringContainsString('syncFleetAvailability', $staffPage->getContent());
        $this->assertStringContainsString('car-loader-icon', $staffPage->getContent());
        $this->assertSame(1, substr_count($staffPage->getContent(), 'id="vehicleSpecificationsModal"'));
        $this->assertStringContainsString('View Specifications', $staffPage->getContent());
    }

    public function test_admin_and_staff_calendars_mark_upcoming_deliveries_as_special_until_rental_starts(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'name' => 'Delivery Calendar Sedan',
            'plate' => 'DEL 1001',
            'price' => 1800,
            'status' => 'rented',
            'schedule' => [[
                'id' => 1,
                'type' => 'Reserved',
                'start' => '2026-10-10',
                'end' => '2026-10-12',
                'reservationId' => 999,
            ]],
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'vehicle' => $vehicle->name,
            'service_option' => 'delivery',
            'driver_option' => 'self_drive',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'DELIVERY-TEST-001',
            'status' => 'verified',
        ]);
        $vehicle->update(['schedule' => [[
            'id' => 1,
            'type' => 'Reserved',
            'start' => $reservation->pickup_date->toDateString(),
            'end' => $reservation->return_date->toDateString(),
            'reservationId' => $reservation->id,
        ]]]);

        foreach ([
            ['admin', 'admin.vehicles', 'is_admin'],
            ['staff', 'staff.vehicles', 'is_staff'],
        ] as [$role, $route, $sessionKey]) {
            $response = $this->withSession([$sessionKey => true])
                ->get(route($route))
                ->assertOk();
            $response->assertSee('Delivery')
                ->assertSee('bg-success')
                ->assertDontSee('Special Delivery')
                ->assertSee('Control Number:')
                ->assertSee('Delivery Date:');
            $this->assertStringContainsString('class="modal fade calendar-details-modal" id="adminCalendarDetailsModal"', $response->getContent(), $role);
            $this->assertStringContainsString('.calendar-details-modal .modal-body { max-height: min(15rem, calc(100vh - 8rem)); overflow-y: auto;', $response->getContent(), $role);
            $scheduleEntry = collect($response->viewData('vehicleRows'))
                ->firstWhere('id', $vehicle->id)['schedule'][0];

            $this->assertSame('Special', $scheduleEntry['type'], $role);
            $this->assertSame('2026-10-10', $scheduleEntry['start'], $role);
            $this->assertSame('2026-10-12', $scheduleEntry['end'], $role);
            $this->assertSame('DELIVERY-TEST-001', $scheduleEntry['controlNumber'], $role);
            $this->assertSame('October 10, 2026', $scheduleEntry['deliveryDate'], $role);
        }

        $reservation->update(['status' => 'released']);
        $response = $this->withSession(['is_admin' => true])
            ->get(route('admin.vehicles'))
            ->assertOk();
        $scheduleEntry = collect($response->viewData('vehicleRows'))
            ->firstWhere('id', $vehicle->id)['schedule'][0];

        $this->assertSame('Rented', $scheduleEntry['type']);
        $this->assertSame('2026-10-12', $scheduleEntry['end']);
    }

    public function test_staff_reservation_actions_render_with_selected_vehicle_specs(): void
    {
        $user = User::factory()->create();
        $unit = Vehicle::create([
            'name' => 'Reservation Detail Test Vehicle',
            'plate' => 'SPEC 4001',
            'price' => 1800,
            'category' => 'Sedan',
            'transmission' => 'Automatic',
            'fuel' => 'Diesel',
            'capacity' => 4,
            'capacity_type' => '4 Seater',
            'status' => 'available',
        ]);
        Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $unit->id,
            'vehicle' => $unit->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->addDays(5)->toDateString(),
            'return_date' => now()->addDays(7)->toDateString(),
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'TEST-SPECS-0001',
            'status' => 'pending',
        ]);

        $guestPage = $this->get(route('reservations'))->assertOk();
        $guestContent = $guestPage->getContent();
        $this->assertStringContainsString('class="guest-vehicle-grid"', $guestContent);
        $this->assertStringContainsString('repeat(auto-fit, minmax(min(100%, 340px), 1fr))', $guestContent);
        $this->assertStringContainsString('class="guest-vehicle-grid-item"', $guestContent);
        $this->assertSame(1, substr_count($guestContent, 'id="guestSpecificationsModal"'));
        $this->assertStringContainsString('View Specifications', $guestContent);
        $this->assertStringContainsString('class="specifications-button"', $guestContent);
        $this->assertStringContainsString('function showGuestSpecifications(button)', $guestContent);
        $this->assertStringContainsString('function displayGuestCategory(category)', $guestContent);
        $this->assertStringContainsString("if (normalized.includes('diesel')) return 'Diesel';", $guestContent);
        $this->assertStringContainsString("return 'Pick-up / Expanded';", $guestContent);
        $this->assertStringContainsString('id="guest-category-', $guestContent);
        $this->assertStringNotContainsString("['Category', unit.category]", $guestContent);
        $this->assertStringContainsString('syncGuestAvailability', $guestContent);
        $this->assertStringContainsString('/vehicles/availability', $guestContent);
        $this->assertStringContainsString('car-loader-icon', $guestContent);

        $userPage = $this->actingAs($user)->get(route('user.reservations'))->assertOk();
        $this->assertStringNotContainsString('Selected Vehicle Details', $userPage->getContent());
        $this->assertStringNotContainsString('rentVehicleSpecs', $userPage->getContent());
        $this->assertSame(1, substr_count($userPage->getContent(), 'id="vehicleSpecificationsModal"'));
        $this->assertStringContainsString('View Specifications', $userPage->getContent());
        $this->assertStringContainsString('class="specifications-button"', $userPage->getContent());
        $this->assertStringContainsString("['Transmission Type', vehicle.transmission]", $userPage->getContent());
        $this->assertStringContainsString("['Fuel Type', vehicle.fuel]", $userPage->getContent());
        $this->assertStringContainsString("['Capacity', vehicle.capacity_type", $userPage->getContent());
        $this->assertStringContainsString('function displayReservationCategory(category)', $userPage->getContent());
        $this->assertStringContainsString("if (normalized.includes('diesel')) return 'Diesel';", $userPage->getContent());
        $this->assertStringContainsString("return 'Pick-up / Expanded';", $userPage->getContent());
        $this->assertStringContainsString('escapeHtml(displayReservationCategory(v.category))', $userPage->getContent());
        $this->assertStringNotContainsString("['Category', vehicle.category]", $userPage->getContent());
        $this->assertStringContainsString('"transmission":"Automatic"', $userPage->getContent());
        $this->assertStringContainsString('syncUserVehicleAvailability', $userPage->getContent());
        $this->assertStringContainsString('/vehicles/availability', $userPage->getContent());
        $this->assertStringContainsString('car-loader-icon', $userPage->getContent());

        $adminPage = $this->withSession(['is_admin' => true])->get(route('admin.reservations'))->assertOk();
        $this->assertStringContainsString('id="mVehicleSpecs"', $adminPage->getContent());
        $this->assertStringContainsString('"vehicleTransmission":"Automatic"', $adminPage->getContent());
        $this->assertStringContainsString('"vehicleFuel":"Diesel"', $adminPage->getContent());
        $this->assertStringContainsString('"vehicleCapacity":"4 Seater"', $adminPage->getContent());

        $staffPage = $this->withSession(['is_staff' => true])->get(route('staff.reservations'))->assertOk();
        $this->assertStringContainsString('id="mVehicleSpecs"', $staffPage->getContent());
        $this->assertStringContainsString('"vehicleTransmission":"Automatic"', $staffPage->getContent());
        $this->assertStringContainsString('"vehicleFuel":"Diesel"', $staffPage->getContent());
        $this->assertStringContainsString('"vehicleCapacity":"4 Seater"', $staffPage->getContent());
        $this->assertStringContainsString('data-reservation-action="review"', $staffPage->getContent());
        $this->assertStringContainsString("button[data-reservation-action]", $staffPage->getContent());
        $this->assertStringNotContainsString('onclick="viewDetailsById(', $staffPage->getContent());
    }

    public function test_guest_calendar_matches_user_calendar_and_does_not_duplicate_rentals(): void
    {
        $user = User::factory()->create();
        Vehicle::create([
            'name' => 'Calendar Test Sedan',
            'plate' => 'CAL 4000',
            'price' => 1800,
            'capacity' => 4,
            'status' => 'available',
        ]);
        $rentedUnit = Vehicle::create([
            'name' => 'Calendar Test Sedan',
            'plate' => 'CAL 4001',
            'price' => 1800,
            'capacity' => 4,
            'status' => 'rented',
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $rentedUnit->id,
            'vehicle' => $rentedUnit->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->addDays(5)->toDateString(),
            'return_date' => now()->addDays(7)->toDateString(),
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'TEST-CALENDAR-0001',
            'status' => 'pending',
        ]);
        $rentedUnit->update([
            'schedule' => [[
                'type' => 'Rented',
                'start' => $reservation->pickup_date->toDateString(),
                'end' => $reservation->return_date->toDateString(),
                'reservationId' => $reservation->id,
            ]],
        ]);
        Vehicle::create([
            'name' => 'Filtered Out Maintenance Van',
            'plate' => 'CAL 7001',
            'price' => 2500,
            'capacity' => 7,
            'status' => 'maintenance',
            'schedule' => [[
                'type' => 'Maintenance',
                'start' => now()->addDays(4)->toDateString(),
                'end' => now()->addDays(6)->toDateString(),
            ]],
        ]);

        $response = $this->get(route('reservations', ['capacity' => '4']))->assertOk();
        $content = $response->getContent();
        $this->assertSame(1, substr_count($content, '"vehicle":"Calendar Test Sedan"'));
        $this->assertStringContainsString('"unitNumber":2', $content);
        $this->assertStringContainsString('guestCalendarVehicleLabel(entry)', $content);
        $this->assertStringContainsString('"vehicle":"Filtered Out Maintenance Van"', $content);
        $this->assertStringContainsString('aria-label="Previous month"', $content);
        $this->assertStringContainsString('aria-label="Next month"', $content);
        $this->assertStringContainsString('event-maintenance', $content);
        $this->assertStringContainsString('class="modal fade calendar-details-modal" id="calendarDetailsModal"', $content);
        $this->assertStringContainsString('.calendar-details-modal .modal-body { max-height: min(15rem, calc(100vh - 8rem)); overflow-y: auto;', $content);

        $this->actingAs($user)
            ->get(route('user.reservations'))
            ->assertOk()
            ->assertSee('onclick="changeMonth(-1)"', false)
            ->assertSee('onclick="changeMonth(1)"', false)
            ->assertSee('class="modal fade calendar-details-modal" id="calendarDetailsModal"', false)
            ->assertSee('.calendar-details-modal .modal-body { max-height: min(15rem, calc(100vh - 8rem)); overflow-y: auto;', false)
            ->assertSee('Unit #', false);
    }

    public function test_guest_cannot_book_a_model_when_its_only_unit_is_reserved(): void
    {
        $user = User::factory()->create();
        $unit = Vehicle::create([
            'name' => 'Nissan Test Model',
            'plate' => 'NIS 1000',
            'price' => 1800,
            'status' => 'rented',
        ]);
        Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $unit->id,
            'vehicle' => $unit->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->addDays(2)->toDateString(),
            'return_date' => now()->addDays(3)->toDateString(),
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'TEST-UNIT-0002',
            'status' => 'pending',
        ]);

        $response = $this->get(route('reservations'))->assertOk();
        $response->assertSee(
            'id="guest-status-'.$unit->id.'" class="status-badge bg-primary text-white">Reserved',
            false
        )->assertSee(
            'href="#" class="btn btn-secondary disabled rounded-pill px-4 rent-now-link"',
            false
        );
    }

    public function test_online_booking_saves_and_reserves_only_the_selected_unit(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $firstUnit = Vehicle::create([
            'name' => 'Honda Civic',
            'plate' => 'ABC 1234',
            'price' => 1800,
            'status' => 'available',
        ]);
        $secondUnit = Vehicle::create([
            'name' => 'Honda Civic',
            'plate' => 'DEF 5678',
            'price' => 1800,
            'status' => 'available',
        ]);
        $pickupDate = now()->addDays(5)->toDateString();
        $returnDate = now()->addDays(6)->toDateString();

        $this->actingAs($user)
            ->post(route('user.reservations.store'), [
                'vehicle' => $firstUnit->name,
                'vehicle_id' => $firstUnit->id,
                'rate_type' => 'city',
                'service_option' => 'pickup',
                'driver_option' => 'self_drive',
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate,
                'pickup_time' => '12:00',
                'return_time' => '12:00',
                'valid_id' => UploadedFile::fake()->create('valid-id.png', 10, 'image/png'),
                'driver_license' => UploadedFile::fake()->create('license.png', 10, 'image/png'),
                'proof_of_billing' => UploadedFile::fake()->create('billing.png', 10, 'image/png'),
                'privacy_consent' => '1',
                'final_agreement' => '1',
                'payment_mode' => 'deposit',
                'gcash_screenshot' => UploadedFile::fake()->createWithContent(
                    'deposit-proof.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jS7sAAAAASUVORK5CYII=')
                ),
                'gcash_reference' => '1234567890123',
            ])
            ->assertRedirect(route('user.dashboard'));

        $reservation = Reservation::firstOrFail();
        $this->assertNotNull($reservation->payment_proof_path);
        Storage::disk('public')->assertExists($reservation->payment_proof_path);
        $this->assertSame($firstUnit->id, $reservation->vehicle_id);
        $this->assertSame(1, $reservation->rentalDays());
        $this->assertEquals(1800, (float) $reservation->total_amount);
        $this->assertSame('12:00', substr($reservation->return_time, 0, 5));
        $this->assertSame('rented', $firstUnit->fresh()->status);
        $this->assertSame('available', $secondUnit->fresh()->status);
    }

    public function test_user_delivery_booking_uses_text_drop_off_and_optional_notes_without_a_map(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'name' => 'Map Delivery Sedan',
            'plate' => 'MAP 1001',
            'price' => 1800,
            'status' => 'available',
        ]);

        $this->actingAs($user)
            ->get(route('user.reservations'))
            ->assertOk()
            ->assertSee('id="deliveryProvince"', false)
            ->assertSee('id="deliveryCity"', false)
            ->assertSee('id="deliveryBarangay"', false)
            ->assertSee('Select Barangay')
            ->assertSee('loadDeliveryBarangays()')
            ->assertSee('id="deliveryStreet"', false)
            ->assertSee('id="deliveryNotes"', false)
            ->assertSee('id="deliveryNextStep"', false)
            ->assertDontSee('delivery-location-picker.js')
            ->assertDontSee('deliveryMap');

        $bookingData = [
            'vehicle' => $vehicle->name,
            'vehicle_id' => $vehicle->id,
            'rate_type' => 'city',
            'service_option' => 'delivery',
            'driver_option' => 'self_drive',
            'delivery_province' => 'Cavite',
            'delivery_city' => 'General Mariano Alvarez',
            'delivery_barangay' => 'San Gabriel',
            'delivery_street' => 'SM Dasma main entrance',
            'delivery_notes' => 'Wait beside the north gate.',
            'pickup_date' => now()->addDays(10)->toDateString(),
            'return_date' => now()->addDays(11)->toDateString(),
            'pickup_time' => '12:00',
            'return_time' => '12:00',
            'valid_id' => UploadedFile::fake()->create('valid-id.png', 10, 'image/png'),
            'driver_license' => UploadedFile::fake()->create('license.png', 10, 'image/png'),
            'proof_of_billing' => UploadedFile::fake()->create('billing.png', 10, 'image/png'),
            'privacy_consent' => '1',
            'final_agreement' => '1',
            'payment_mode' => 'walkin',
        ];

        $missingDropOffPoint = $bookingData;
        unset($missingDropOffPoint['delivery_street']);
        $this->from(route('user.reservations'))
            ->post(route('user.reservations.store'), $missingDropOffPoint)
            ->assertSessionHasErrors('delivery_street');
        $this->assertDatabaseCount('reservations', 0);

        $missingBarangay = $bookingData;
        unset($missingBarangay['delivery_barangay']);
        $this->from(route('user.reservations'))
            ->post(route('user.reservations.store'), $missingBarangay)
            ->assertSessionHasErrors('delivery_barangay');
        $this->assertDatabaseCount('reservations', 0);

        $this->post(route('user.reservations.store'), $bookingData)
            ->assertRedirect(route('user.dashboard'));

        $reservation = Reservation::firstOrFail();
        $this->assertSame(
            'Cavite, General Mariano Alvarez, San Gabriel, SM Dasma main entrance',
            $reservation->delivery_address
        );
        $this->assertSame('Wait beside the north gate.', $reservation->delivery_notes);
        $this->assertNull($reservation->delivery_latitude);
        $this->assertNull($reservation->delivery_longitude);

        $this->withSession(['is_admin' => true])
            ->get(route('admin.reservations'))
            ->assertOk()
            ->assertSee('SM Dasma main entrance')
            ->assertSee('Wait beside the north gate.');

        $this->withSession(['is_staff' => true])
            ->get(route('staff.reservations'))
            ->assertOk()
            ->assertSee('SM Dasma main entrance')
            ->assertSee('Wait beside the north gate.');
    }

    public function test_online_long_distance_booking_requires_two_full_days(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'name' => 'Honda Civic',
            'plate' => 'LNG 1001',
            'price' => 1800,
            'status' => 'available',
        ]);
        $pickupDate = now()->addDays(10)->toDateString();

        $oneDayResponse = $this->actingAs($user)
            ->from(route('user.reservations'))
            ->post(route('user.reservations.store'), [
                'vehicle' => $vehicle->name,
                'vehicle_id' => $vehicle->id,
                'rate_type' => 'long_distance',
                'service_option' => 'pickup',
                'driver_option' => 'self_drive',
                'pickup_date' => $pickupDate,
                'return_date' => now()->addDays(11)->toDateString(),
                'pickup_time' => '12:00',
                'return_time' => '12:00',
                'valid_id' => UploadedFile::fake()->create('valid-id.png', 10, 'image/png'),
                'driver_license' => UploadedFile::fake()->create('license.png', 10, 'image/png'),
                'proof_of_billing' => UploadedFile::fake()->create('billing.png', 10, 'image/png'),
                'privacy_consent' => '1',
                'final_agreement' => '1',
                'payment_mode' => 'walkin',
            ]);

        $oneDayResponse->assertSessionHasErrors('return_date');
        $this->assertDatabaseCount('reservations', 0);

        $twoDayResponse = $this->from(route('user.reservations'))
            ->post(route('user.reservations.store'), [
                'vehicle' => $vehicle->name,
                'vehicle_id' => $vehicle->id,
                'rate_type' => 'long_distance',
                'service_option' => 'pickup',
                'driver_option' => 'self_drive',
                'pickup_date' => $pickupDate,
                'return_date' => now()->addDays(12)->toDateString(),
                'pickup_time' => '12:00',
                'return_time' => '12:00',
                'valid_id' => UploadedFile::fake()->create('valid-id.png', 10, 'image/png'),
                'driver_license' => UploadedFile::fake()->create('license.png', 10, 'image/png'),
                'proof_of_billing' => UploadedFile::fake()->create('billing.png', 10, 'image/png'),
                'privacy_consent' => '1',
                'final_agreement' => '1',
                'payment_mode' => 'walkin',
            ]);

        $twoDayResponse->assertRedirect(route('user.dashboard'));
        $reservation = Reservation::firstOrFail();
        $this->assertSame(2, $reservation->rentalDays());
        $this->assertEquals(6600, (float) $reservation->total_amount);
    }

    public function test_online_province_booking_can_be_one_full_day_and_shows_pickup_and_return_times(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'name' => 'Honda Civic',
            'plate' => 'PROV 1001',
            'price' => 1800,
            'status' => 'available',
        ]);
        $pickupDate = now()->addDays(10)->toDateString();

        $this->actingAs($user)->post(route('user.reservations.store'), [
            'vehicle' => $vehicle->name,
            'vehicle_id' => $vehicle->id,
            'rate_type' => 'province',
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => $pickupDate,
            'return_date' => now()->addDays(11)->toDateString(),
            'pickup_time' => '12:00',
            'return_time' => '12:00',
            'valid_id' => UploadedFile::fake()->create('valid-id.png', 10, 'image/png'),
            'driver_license' => UploadedFile::fake()->create('license.png', 10, 'image/png'),
            'proof_of_billing' => UploadedFile::fake()->create('billing.png', 10, 'image/png'),
            'privacy_consent' => '1',
            'final_agreement' => '1',
            'payment_mode' => 'walkin',
        ])->assertRedirect(route('user.dashboard'));

        $reservation = Reservation::firstOrFail();
        $this->assertSame(1, $reservation->rentalDays());
        $this->assertSame('12:00', substr($reservation->pickup_time, 0, 5));
        $this->assertSame('12:00', substr($reservation->return_time, 0, 5));
        $this->assertEquals(2800, (float) $reservation->total_amount);
    }
}
