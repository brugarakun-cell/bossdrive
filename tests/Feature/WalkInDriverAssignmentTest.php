<?php

namespace Tests\Feature;

use App\Models\OnCallDriver;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WalkInDriverAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_reservation_reviews_show_documents_uploaded_by_users(): void
    {
        Storage::fake('public');
        $documentPaths = [
            'driver_license' => 'reservations/documents/customer-license.jpg',
            'valid_id' => 'reservations/documents/customer-id.png',
            'proof_of_billing' => 'reservations/documents/customer-billing.webp',
        ];
        foreach ($documentPaths as $path) {
            Storage::disk('public')->put($path, 'test document image');
        }
        $paymentProofPath = 'reservations/payments/customer-payment.png';
        Storage::disk('public')->put($paymentProofPath, 'payment proof image');

        $user = User::factory()->create(['role' => 'user']);
        $vehicle = $this->createVehicle('DOC 3001');
        Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'booking_source' => 'user',
            'vehicle' => $vehicle->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->addDays(10)->toDateString(),
            'return_date' => now()->addDays(11)->toDateString(),
            'document_paths' => $documentPaths,
            'payment_proof_path' => $paymentProofPath,
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'USER-DOCS-0001',
            'status' => 'pending',
        ]);

        $adminPage = $this->withSession(['is_admin' => true])
            ->get(route('admin.reservations'))
            ->assertOk()
            ->assertSee('verificationDocumentsPanel')
            ->assertSee('customer-license.jpg')
            ->assertSee('customer-id.png')
            ->assertSee('customer-billing.webp');

        $staffPage = $this->withSession(['is_staff' => true])
            ->get(route('staff.reservations'))
            ->assertOk()
            ->assertDontSee('staffVerificationDocuments')
            ->assertDontSee('customer-license.jpg')
            ->assertDontSee('customer-id.png')
            ->assertDontSee('customer-billing.webp')
            ->assertSee('staffPaymentProof')
            ->assertSee('customer-payment.png');

        $this->assertStringContainsString('"bookingSource":"user"', $adminPage->getContent());
        $this->assertStringNotContainsString('customer-license.jpg', $staffPage->getContent());

        $this->withSession(['is_staff' => true, 'is_admin' => false])
            ->getJson(route('staff.reservations.live'))
            ->assertOk()
            ->assertJsonMissingPath('0.documents')
            ->assertDontSee('customer-license.jpg')
            ->assertDontSee('customer-id.png')
            ->assertDontSee('customer-billing.webp')
            ->assertJsonPath('0.paymentProof', asset('storage/'.$paymentProofPath));

        $this->withSession(['is_admin' => true])
            ->getJson(route('admin.reservations.live'))
            ->assertOk()
            ->assertJsonPath('0.documents.driver_license', asset('storage/'.$documentPaths['driver_license']))
            ->assertJsonPath('0.documents.valid_id', asset('storage/'.$documentPaths['valid_id']))
            ->assertJsonPath('0.documents.proof_of_billing', asset('storage/'.$documentPaths['proof_of_billing']));
    }

    public function test_missing_uploaded_documents_are_not_rendered_as_broken_admin_or_staff_image_links(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'user']);
        $vehicle = $this->createVehicle('DOC 3002');
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'booking_source' => 'user',
            'vehicle' => $vehicle->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->addDays(10)->toDateString(),
            'return_date' => now()->addDays(11)->toDateString(),
            'document_paths' => [
                'driver_license' => 'reservations/documents/missing-license.png',
                'valid_id' => 'reservations/documents/missing-id.png',
                'proof_of_billing' => 'reservations/documents/missing-billing.png',
            ],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'USER-DOCS-MISSING',
            'status' => 'pending',
        ]);

        $adminPage = $this->withSession(['is_admin' => true])
            ->get(route('admin.reservations'))
            ->assertOk()
            ->assertSee('File is missing. Ask the customer to upload it again.')
            ->assertSee('"driver_license":null', false)
            ->assertSee('"valid_id":null', false)
            ->assertSee('"proof_of_billing":null', false);

        $this->withSession(['is_staff' => true, 'is_admin' => false])
            ->get(route('staff.reservations'))
            ->assertOk()
            ->assertDontSee('File is missing. Ask the customer to upload it again.')
            ->assertDontSee('"driver_license":null', false)
            ->assertDontSee('missing-license.png');

        $this->withSession(['is_staff' => true, 'is_admin' => false])
            ->getJson(route('staff.reservations.live'))
            ->assertOk()
            ->assertJsonMissingPath('0.documents')
            ->assertDontSee('missing-license.png');

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Update Documents');

        $this->assertSame('USER-DOCS-MISSING', $reservation->control_number);
        $this->assertStringNotContainsString('storage/reservations/documents/missing-license.png', $adminPage->getContent());
    }

    public function test_user_can_restore_missing_documents_on_a_completed_booking(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'user']);
        $vehicle = $this->createVehicle('DOC 3003');
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'booking_source' => 'user',
            'vehicle' => $vehicle->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->subDays(10)->toDateString(),
            'return_date' => now()->subDays(9)->toDateString(),
            'document_paths' => [
                'driver_license' => 'reservations/documents/lost-license.png',
                'valid_id' => 'reservations/documents/lost-id.png',
                'proof_of_billing' => 'reservations/documents/lost-billing.png',
            ],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'USER-DOCS-COMPLETED',
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Update Documents')
            ->assertSee('Some documents are missing from storage.');

        foreach ([
            'driver_license' => 'restored-license.png',
            'valid_id' => 'restored-id.png',
            'proof_of_billing' => 'restored-billing.png',
        ] as $type => $filename) {
            $this->post(route('user.reservations.documents.update', $reservation), [
                'document_type' => $type,
                'document' => UploadedFile::fake()->create($filename, 100, 'image/png'),
            ])->assertRedirect()->assertSessionHas('success');
        }

        $reservation->refresh();
        foreach ($reservation->document_paths as $path) {
            Storage::disk('public')->assertExists($path);
        }

        $this->withSession(['is_admin' => true])
            ->get(route('admin.reservations'))
            ->assertOk()
            ->assertSee(basename($reservation->document_paths['driver_license']))
            ->assertSee(basename($reservation->document_paths['valid_id']))
            ->assertSee(basename($reservation->document_paths['proof_of_billing']));
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->post(route('user.reservations.documents.update', $reservation), [
                'document_type' => 'driver_license',
                'document' => UploadedFile::fake()->create('replacement-again.png', 100, 'image/png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $reservation->refresh();
        $this->assertSame('completed', $reservation->status);
        $this->assertStringEndsWith('.png', $reservation->document_paths['driver_license']);
    }

    public function test_admin_and_staff_vehicle_pages_receive_driver_management_options(): void
    {
        $driver = OnCallDriver::create([
            'name' => 'Test Driver',
            'contact' => '09170000000',
            'available_this_week' => true,
            'availability_week' => now()->startOfWeek()->toDateString(),
        ]);

        $this->withSession(['is_admin' => true])
            ->get(route('admin.vehicles'))
            ->assertOk()
            ->assertSee('b-driver-id')
            ->assertSee('id="bookError" role="alert" aria-live="polite"', false)
            ->assertSee('Test Driver')
            ->assertSee((string) $driver->id);

        $this->withSession(['is_staff' => true])
            ->get(route('staff.vehicles'))
            ->assertOk()
            ->assertSee('b-driver-id')
            ->assertSee('id="bookError" role="alert" aria-live="polite"', false)
            ->assertSee('Test Driver')
            ->assertSee((string) $driver->id);
    }

    public function test_walk_in_with_driver_booking_persists_the_selected_managed_driver(): void
    {
        $vehicle = $this->createVehicle();
        $driver = $this->createDriver();

        $response = $this->withSession(['is_admin' => true])
            ->postJson(route('admin.reservations.store'), $this->bookingData($vehicle, [
                'driver_option' => 'with_driver',
                'driver_id' => $driver->id,
            ]));

        $response->assertCreated();
        $reservation = Reservation::findOrFail($response->json('reservation_id'));
        $this->assertSame($driver->name, $reservation->assigned_driver_name);
        $this->assertSame($driver->contact, $reservation->assigned_driver_contact);
    }

    public function test_walk_in_with_driver_rejects_missing_unavailable_or_date_conflicted_driver(): void
    {
        $vehicle = $this->createVehicle();
        $driver = $this->createDriver();
        $data = $this->bookingData($vehicle, ['driver_option' => 'with_driver']);

        $this->withSession(['is_admin' => true])
            ->postJson(route('admin.reservations.store'), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('driver_id');

        $driver->update([
            'available_this_week' => false,
            'availability_week' => now()->startOfWeek()->toDateString(),
        ]);
        $this->withSession(['is_admin' => true])
            ->postJson(route('admin.reservations.store'), $this->bookingData($vehicle, [
                'driver_option' => 'with_driver',
                'driver_id' => $driver->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This driver is marked unavailable in Driver Management.');

        $driver->update(['available_this_week' => true]);
        $otherVehicle = $this->createVehicle('TEST 1002');
        $this->createDriverReservation($otherVehicle, $driver, $data['start_date'], $data['end_date']);
        $this->withSession(['is_admin' => true])
            ->postJson(route('admin.reservations.store'), $this->bookingData($vehicle, [
                'driver_option' => 'with_driver',
                'driver_id' => $driver->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This driver is already assigned to another booking for the selected dates.');
    }

    public function test_self_drive_walk_in_does_not_assign_a_driver(): void
    {
        $vehicle = $this->createVehicle();
        $driver = $this->createDriver();

        $response = $this->withSession(['is_admin' => true])
            ->postJson(route('admin.reservations.store'), $this->bookingData($vehicle, [
                'driver_option' => 'self_drive',
                'driver_id' => $driver->id,
            ]));

        $response->assertCreated();
        $reservation = Reservation::findOrFail($response->json('reservation_id'));
        $this->assertNull($reservation->assigned_driver_name);
        $this->assertNull($reservation->assigned_driver_contact);
    }

    public function test_admin_and_staff_walk_in_delivery_saves_drop_off_and_notes_without_a_map(): void
    {
        foreach ([
            ['admin', 'admin.vehicles', 'admin.reservations.store', 'is_admin', 'DROP 2001'],
            ['staff', 'staff.vehicles', 'staff.reservations.store', 'is_staff', 'DROP 2002'],
        ] as [$role, $pageRoute, $storeRoute, $sessionKey, $plate]) {
            $vehicle = $this->createVehicle($plate);
            if ($role === 'staff') {
                $this->withSession([$sessionKey => true])
                    ->get(route($pageRoute))
                    ->assertOk()
                    ->assertSee('staff-sidebar-toggle')
                    ->assertSee('.staff-sidebar ~ .main-content', false)
                    ->assertSee('max-width:575.98px', false);
            }

            $this->withSession([$sessionKey => true])
                ->get(route($pageRoute))
                ->assertOk()
                ->assertSee('b-delivery-province')
                ->assertSee('b-delivery-city')
                ->assertSee('b-delivery-barangay')
                ->assertSee('b-delivery-street')
                ->assertSee('b-delivery-notes')
                ->assertSee('b-next-customer-step')
                ->assertSee('id="bookError" role="alert" aria-live="polite"', false)
                ->assertDontSee('delivery-location-picker.js')
                ->assertDontSee('b-delivery-map')
                ->assertSee("document.getElementById('b-opt-deliver').classList.contains('active')", false)
                ->assertSee("const required = ['b-name', 'b-phone', 'b-birth-date', 'b-email'];", false)
                ->assertSee('Please complete the customer information first.');

            $booking = $this->bookingData($vehicle, [
                'service_option' => 'delivery',
                'delivery_province' => 'Cavite',
                'delivery_city' => 'General Mariano Alvarez',
                'delivery_barangay' => 'San Gabriel',
                'delivery_street' => 'SM Dasmariñas main entrance',
                'delivery_notes' => 'Meet beside the north gate.',
            ]);

            $reservationCount = Reservation::count();
            $missingDropOffPoint = $booking;
            unset($missingDropOffPoint['delivery_street']);
            $this->withSession([$sessionKey => true])
                ->postJson(route($storeRoute), $missingDropOffPoint)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('delivery_street');
            $this->assertDatabaseCount('reservations', $reservationCount);

            $missingBarangay = $booking;
            unset($missingBarangay['delivery_barangay']);
            $this->withSession([$sessionKey => true])
                ->postJson(route($storeRoute), $missingBarangay)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('delivery_barangay');
            $this->assertDatabaseCount('reservations', $reservationCount);

            $response = $this->withSession([$sessionKey => true])
                ->postJson(route($storeRoute), $booking)
                ->assertCreated();

            $reservation = Reservation::findOrFail($response->json('reservation_id'));
            $this->assertSame('admin_staff', $reservation->booking_source, $role);
            $this->assertSame(
                'Cavite, General Mariano Alvarez, San Gabriel, SM Dasmariñas main entrance',
                $reservation->delivery_address
            );
            $this->assertSame('Meet beside the north gate.', $reservation->delivery_notes);
            $this->assertNull($reservation->delivery_latitude);
            $this->assertNull($reservation->delivery_longitude);

            $reviewRoute = $role === 'admin' ? 'admin.walk-in-bookings' : 'staff.walk-in-bookings';
            $this->withSession([$sessionKey => true])
                ->get(route($reviewRoute))
                ->assertOk()
                ->assertSee('Delivery Notes')
                ->assertSee('Meet beside the north gate.');
        }
    }

    public function test_walk_in_booking_counts_full_24_hour_periods_and_enforces_long_distance_minimum(): void
    {
        $cityVehicle = $this->createVehicle();
        $cityBooking = $this->bookingData($cityVehicle, [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
            'pickup_time' => '12:00',
            'return_time' => '12:00',
        ]);

        $cityResponse = $this->withSession(['is_admin' => true])
            ->postJson(route('admin.reservations.store'), $cityBooking);

        $cityResponse->assertCreated();
        $cityReservation = Reservation::findOrFail($cityResponse->json('reservation_id'));
        $this->assertSame('12:00', substr($cityReservation->pickup_time, 0, 5));
        $this->assertSame('12:00', substr($cityReservation->return_time, 0, 5));
        $this->assertEquals(1800, (float) $cityReservation->total_amount);
        $this->assertSame(1, $cityReservation->rentalDays());

        $provinceVehicle = $this->createVehicle('TEST 1004');
        $provinceResponse = $this->withSession(['is_admin' => true])
            ->postJson(route('admin.reservations.store'), $this->bookingData($provinceVehicle, [
                'rate_type' => 'province',
                'start_date' => now()->addDays(15)->toDateString(),
                'end_date' => now()->addDays(16)->toDateString(),
                'pickup_time' => '12:00',
                'return_time' => '12:00',
            ]));
        $provinceResponse->assertCreated();
        $provinceReservation = Reservation::findOrFail($provinceResponse->json('reservation_id'));
        $this->assertEquals(2800, (float) $provinceReservation->total_amount);
        $this->assertSame(1, $provinceReservation->rentalDays());

        $longDistanceVehicle = $this->createVehicle('TEST 1003');
        $oneDayLongDistance = $this->bookingData($longDistanceVehicle, [
            'rate_type' => 'long_distance',
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(21)->toDateString(),
            'pickup_time' => '12:00',
            'return_time' => '12:00',
        ]);
        $this->withSession(['is_admin' => true])
            ->postJson(route('admin.reservations.store'), $oneDayLongDistance)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Long Distance bookings require a minimum rental period of 2 full days.');

        $twoDayLongDistance = array_replace($oneDayLongDistance, [
            'end_date' => now()->addDays(22)->toDateString(),
        ]);
        $longDistanceResponse = $this->withSession(['is_admin' => true])
            ->postJson(route('admin.reservations.store'), $twoDayLongDistance);
        $longDistanceResponse->assertCreated();
        $longDistanceReservation = Reservation::findOrFail($longDistanceResponse->json('reservation_id'));
        $this->assertEquals(6600, (float) $longDistanceReservation->total_amount);
        $this->assertSame(2, $longDistanceReservation->rentalDays());
    }

    public function test_completing_a_user_reservation_keeps_its_uploaded_documents_available(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['name' => 'Test Admin', 'role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $vehicle = $this->createVehicle('DOC 3004');
        $documentPaths = [
            'driver_license' => 'reservations/documents/complete-license.png',
            'valid_id' => 'reservations/documents/complete-id.png',
            'proof_of_billing' => 'reservations/documents/complete-billing.png',
        ];
        foreach ($documentPaths as $path) {
            Storage::disk('public')->put($path, 'saved document');
        }

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'booking_source' => 'user',
            'vehicle' => $vehicle->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->subDays(2)->toDateString(),
            'return_date' => now()->subDay()->toDateString(),
            'document_paths' => $documentPaths,
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'USER-DOCS-RETURNED',
            'status' => 'released',
        ]);

        $this->withSession([
            'is_admin' => true,
            'admin_user_id' => $admin->id,
            'admin_name' => $admin->name,
        ])->patchJson(route('admin.reservations.status', $reservation), [
            'status' => 'completed',
            'return_condition_checks' => ['Clean'],
        ])->assertOk()->assertJsonPath('reservation.status', 'completed');

        foreach ($documentPaths as $path) {
            Storage::disk('public')->assertExists($path);
        }

        $this->withSession(['is_admin' => true])
            ->get(route('admin.reservations'))
            ->assertOk()
            ->assertSee('complete-license.png')
            ->assertSee('complete-id.png')
            ->assertSee('complete-billing.png');
    }

    public function test_admin_can_return_walk_in_booking_using_the_session_admin_identity(): void
    {
        $admin = User::factory()->create(['name' => 'Test Admin', 'role' => 'admin']);
        $vehicle = $this->createVehicle();
        $reservation = $this->createReleasedWalkInReservation($vehicle);

        $this->withSession([
            'is_admin' => true,
            'admin_user_id' => $admin->id,
            'admin_name' => $admin->name,
        ])->get(route('admin.reservations'))
            ->assertOk()
            ->assertSee('id="returnConditionForm" data-no-page-loader', false)
            ->assertSee("if (!form.hasAttribute('data-no-page-loader'))", false);

        $this->withSession([
            'is_admin' => true,
            'admin_user_id' => $admin->id,
            'admin_name' => $admin->name,
        ])->patchJson(route('admin.reservations.status', $reservation), [
            'status' => 'completed',
            'return_condition_checks' => ['Clean', 'No new damage'],
            'return_condition_notes' => 'Returned in good condition.',
        ])->assertOk()
            ->assertJsonPath('reservation.status', 'completed');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'completed',
            'return_condition_checked_by' => 'Test Admin',
            'return_condition_checked_by_role' => 'Admin',
        ]);
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => 'available',
        ]);
    }

    public function test_staff_can_return_walk_in_booking_using_the_session_staff_identity(): void
    {
        $staff = User::factory()->create(['name' => 'Test Staff', 'role' => 'staff']);
        $vehicle = $this->createVehicle();
        $reservation = $this->createReleasedWalkInReservation($vehicle);

        $this->withSession([
            'is_staff' => true,
            'staff_user_id' => $staff->id,
            'staff_name' => $staff->name,
        ])->get(route('staff.reservations'))
            ->assertOk()
            ->assertSee('id="returnConditionForm" data-no-page-loader', false)
            ->assertSee("if (!form.hasAttribute('data-no-page-loader'))", false);

        $this->withSession([
            'is_staff' => true,
            'staff_user_id' => $staff->id,
            'staff_name' => $staff->name,
        ])->patchJson(route('staff.reservations.status', $reservation), [
            'status' => 'completed',
            'return_condition_checks' => ['Clean', 'No new damage'],
            'return_condition_notes' => 'Returned in good condition.',
        ])->assertOk()
            ->assertJsonPath('reservation.status', 'completed');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'completed',
            'return_condition_checked_by' => 'Test Staff',
            'return_condition_checked_by_role' => 'Staff',
        ]);
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => 'available',
        ]);
    }

    private function createVehicle(string $plate = 'TEST 1001'): Vehicle
    {
        return Vehicle::create([
            'name' => 'Honda Civic',
            'plate' => $plate,
            'price' => 1800,
            'status' => 'available',
        ]);
    }

    private function createDriver(): OnCallDriver
    {
        return OnCallDriver::create([
            'name' => 'Test Driver',
            'contact' => '09170000000',
            'available_this_week' => true,
            'availability_week' => now()->startOfWeek()->toDateString(),
        ]);
    }

    private function bookingData(Vehicle $vehicle, array $overrides = []): array
    {
        $start = now()->addDays(10)->toDateString();
        $end = now()->addDays(12)->toDateString();

        return array_replace([
            'vehicle_id' => $vehicle->id,
            'customer_name' => 'Test Customer',
            'customer_phone' => '09171234567',
            'customer_age' => 30,
            'customer_birth_date' => '1990-01-01',
            'customer_email' => 'walkin@example.com',
            'start_date' => $start,
            'end_date' => $end,
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'rate_type' => 'city',
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'payment_mode' => 'walkin',
        ], $overrides);
    }

    private function createDriverReservation(Vehicle $vehicle, OnCallDriver $driver, string $start, string $end): Reservation
    {
        return Reservation::create([
            'user_id' => null,
            'vehicle_id' => $vehicle->id,
            'booking_source' => 'admin_staff',
            'customer_name' => 'Existing Customer',
            'customer_phone' => '09170000001',
            'customer_age' => 30,
            'customer_birth_date' => '1990-01-01',
            'customer_email' => 'existing@example.com',
            'vehicle' => $vehicle->name,
            'service_option' => 'pickup',
            'driver_option' => 'with_driver',
            'assigned_driver_name' => $driver->name,
            'assigned_driver_contact' => $driver->contact,
            'pickup_date' => $start,
            'return_date' => $end,
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'walkin',
            'control_number' => 'TEST-DRIVER-0001',
            'status' => 'verified',
        ]);
    }

    private function createReleasedWalkInReservation(Vehicle $vehicle): Reservation
    {
        return Reservation::create([
            'user_id' => null,
            'vehicle_id' => $vehicle->id,
            'booking_source' => 'admin_staff',
            'customer_name' => 'Walk-in Return Customer',
            'customer_phone' => '09171234567',
            'vehicle' => $vehicle->name,
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->subDays(2)->toDateString(),
            'return_date' => now()->subDay()->toDateString(),
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'walkin',
            'control_number' => 'WALKIN-RETURN-'.uniqid(),
            'status' => 'released',
        ]);
    }
}
