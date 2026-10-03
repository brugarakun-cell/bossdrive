<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\RentalExtension;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_center_shows_extension_amount_and_pending_payment_status_without_proof_method_choices(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $extension = RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => 2,
            'amount' => 3600,
            'requested_return_date' => now()->addDays(7)->toDateString(),
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('user.payments'))->assertOk();

        $response->assertSee('Extension Total: ₱3,600.00');
        $response->assertSee('Pending Balance: ₱3,600.00');
        $response->assertSee('Pending Payment Book');
        $response->assertSee('Extended Book');
        $response->assertSee('View Completed');
        $response->assertSee('#f5c451');
        $response->assertDontSee('Original Book');
        $response->assertSee('Control Number: '.$reservation->control_number);
        $response->assertSee('Enter Your Control Number');
        $response->assertSee('paymentControlNumber');
        $response->assertSee('paymentToast');
        $response->assertSee('name="payment_amount"', false);
        $response->assertSee('name="reference_id"', false);
        $response->assertSee('name="payment_proof"', false);
        $response->assertSee('id="paymentDetailsNextButton"', false);
        $response->assertSee('id="paymentControlStep" class="payment-step"', false);
        $response->assertSee('id="paymentDetailsStep" class="payment-step" hidden', false);
        $response->assertSee('class="row g-4 payment-layout-row"', false);
        $response->assertSee('.card.payment-panel { height:760px; min-height:760px; max-height:760px; display:flex; flex-direction:column; overflow:hidden; }');
        $response->assertSee('.payment-card.payment-panel { height:auto; min-height:0; max-height:760px; align-self:flex-start; overflow-y:auto; }');
        $response->assertSee('class="tab-content payment-history-scroll"', false);
        $response->assertDontSee('id="paymentDetails"');
        $response->assertDontSee('>CHECK</button>', false);
        $response->assertSee('Pay This Extension');
        $response->assertSee('GCash Ref (or upload a screenshot)');
        $response->assertSee('GCash Screenshot (optional if GCash Ref is provided)');
        $response->assertSee('Amount You Are Paying');
        $response->assertDontSee('Payment Proof');
        $response->assertDontSee('Enter a reference number or upload a screenshot. Either one is enough.');
        $response->assertDontSee('Paying for:');
        $response->assertDontSee('selectedPaymentBalance');
        $response->assertDontSee('Payment Proof Method');
        $response->assertDontSee('name="payment_method"', false);
        $this->assertNotNull($extension->fresh());
    }

    public function test_paid_booking_is_not_listed_as_pending_when_only_its_extension_has_a_balance(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $reservation->update([
            'total_amount' => 2500,
            'paid_amount' => 2500,
            'payment_status' => 'approved',
        ]);
        RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => 2,
            'amount' => 5000,
            'requested_return_date' => now()->addDays(7)->toDateString(),
            'status' => 'approved',
        ]);

        $this->actingAs($user)->get(route('user.payments'))
            ->assertOk()
            ->assertSee('No pending booking payments.')
            ->assertDontSee('Pay This Booking')
            ->assertSee('Toyota Vios · Extension')
            ->assertSee('Pending Balance: ₱5,000.00');
    }

    public function test_user_extension_keeps_original_booking_total_and_payment_status_unchanged(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $reservation->update([
            'total_amount' => 2500,
            'paid_amount' => 2500,
            'payment_status' => 'approved',
        ]);

        $this->actingAs($user)->post(route('user.active-rental.extension', $reservation), [
            'requested_return_date' => now()->addDays(7)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'total_amount' => 2500,
            'paid_amount' => 2500,
            'payment_status' => 'approved',
        ]);
        $this->assertDatabaseHas('rental_extensions', [
            'reservation_id' => $reservation->id,
            'days' => 2,
            'amount' => 0,
            'status' => 'approved',
        ]);
    }

    public function test_extension_payment_accepts_a_reference_number_without_method_selection(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $extension = RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => 1,
            'amount' => 1800,
            'requested_return_date' => now()->addDays(6)->toDateString(),
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'extension_id' => $extension->id,
            'payment_amount' => 1200,
            'reference_id' => 'GCash REF ABC 123',
        ])->assertRedirect();

        $this->assertDatabaseHas('rental_extensions', [
            'id' => $extension->id,
            'payment_reference_id' => 'GCash REF ABC 123',
            'payment_proof_path' => null,
            'payment_submitted_amount' => 1200,
            'status' => 'payment_pending',
        ]);
        $this->get(route('user.payments'))->assertSee('PENDING VERIFICATION');
    }

    public function test_extension_payment_accepts_a_screenshot_without_method_selection(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $extension = RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => 1,
            'amount' => 1800,
            'requested_return_date' => now()->addDays(6)->toDateString(),
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'extension_id' => $extension->id,
            'payment_amount' => 1800,
            'payment_proof' => UploadedFile::fake()->createWithContent(
                'gcash-proof.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jS7sAAAAASUVORK5CYII=')
            ),
        ])->assertRedirect();

        $updatedExtension = $extension->fresh();
        $this->assertNotNull($updatedExtension->payment_proof_path);
        $this->assertSame('1800.00', $updatedExtension->payment_submitted_amount);
        $this->assertNull($updatedExtension->payment_reference_id);
        $this->assertSame('payment_pending', $updatedExtension->status);
        Storage::disk('public')->assertExists($updatedExtension->payment_proof_path);

        $this->actingAs($user)->get(route('user.payments'))
            ->assertSee('id="paymentScreenshotModal"', false)
            ->assertSee('onclick="openPaymentScreenshot(this)"', false)
            ->assertSee('payment-proof-thumbnail')
            ->assertSee('alt="Submitted extension payment screenshot"', false)
            ->assertSee('View submitted screenshot')
            ->assertDontSee('target="_blank"', false);
    }

    public function test_booking_payment_screenshot_and_profile_age_appear_in_admin_reservation_review(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['age' => 29]);
        $reservation = $this->createReservation($user);
        $reservation->update([
            'customer_age' => null,
            'total_amount' => 10000,
            'paid_amount' => 1000,
            'payment_status' => 'pending',
            'payment_submitted_amount' => null,
        ]);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'payment_amount' => 500,
            'payment_proof' => UploadedFile::fake()->createWithContent(
                'deposit-proof.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jS7sAAAAASUVORK5CYII=')
            ),
        ])->assertRedirect()->assertSessionHas('success');

        $reservation->refresh();
        $this->assertNotNull($reservation->payment_proof_path);
        Storage::disk('public')->assertExists($reservation->payment_proof_path);

        $adminPage = $this->withSession(['is_admin' => true])
            ->get(route('admin.reservations'))
            ->assertOk()
            ->assertSee('mPaymentProofMissing')
            ->assertSee(basename($reservation->payment_proof_path))
            ->assertSee('"age":29', false);

        $this->assertStringContainsString('setUploadedPreview(\'mPaymentProof\', \'mPaymentProofLink\', \'mPaymentProofMissing\', reservation.paymentProof)', $adminPage->getContent());
        $this->withSession(['is_admin' => true])
            ->get(route('admin.reservations.live'))
            ->assertOk()
            ->assertJsonFragment([
                'age' => 29,
                'paymentProof' => asset('storage/'.$reservation->payment_proof_path),
            ]);
    }

    public function test_completed_payment_view_shows_payment_and_approving_admin_or_staff(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['name' => 'Ava Admin', 'role' => 'admin']);
        $staff = User::factory()->create(['name' => 'Sam Staff', 'role' => 'staff']);
        $reservation = $this->createReservation($user);
        $reservation->update([
            'total_amount' => 5000,
            'paid_amount' => 5000,
            'payment_status' => 'approved',
            'payment_proof_path' => 'reservations/payments/completed.png',
        ]);
        $extension = RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => 1,
            'amount' => 1800,
            'paid_amount' => 1800,
            'requested_return_date' => now()->addDays(6)->toDateString(),
            'status' => 'paid',
        ]);

        AuditLog::record('reservation.payment_added', $reservation, ['amount' => 5000], $admin->id);
        AuditLog::record('rental_extension.payment_confirmed', $extension, ['amount' => 1800], $staff->id);

        $response = $this->actingAs($user)->get(route('user.payments'))
            ->assertOk()
            ->assertSee('View Completed')
            ->assertSee('Completed Payments')
            ->assertSee('Original Booking')
            ->assertSee('Ava Admin')
            ->assertSee('Admin')
            ->assertSee('Extension Total: ₱1,800.00')
            ->assertSee('Sam Staff')
            ->assertSee('Staff')
            ->assertSee('View payment screenshot')
            ->assertDontSee('alt="Completed booking payment screenshot"', false);
        $response->assertSee('col-md-6', false);
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'data-completed-control="'.$reservation->control_number.'"')
        );
    }

    public function test_fully_paid_booking_waiting_for_verification_is_not_marked_completed_until_staff_confirms(): void
    {
        $user = User::factory()->create();
        $staff = User::factory()->create(['name' => 'Jordan Staff', 'role' => 'staff']);
        $reservation = $this->createReservation($user);
        $reservation->update([
            'total_amount' => 5000,
            'paid_amount' => 5000,
            'payment_status' => 'pending',
            'payment_reference_id' => 'INITIAL-FULL-PAYMENT',
        ]);

        $this->actingAs($user)->get(route('user.payments'))
            ->assertOk()
            ->assertSee('PENDING VERIFICATION')
            ->assertSee($reservation->control_number)
            ->assertDontSee('Jordan Staff');

        $this->withSession([
            'is_staff' => true,
            'staff_user_id' => $staff->id,
            'staff_name' => $staff->name,
        ])->patchJson(route('staff.billing.confirm', $reservation))
            ->assertOk()
            ->assertJsonPath('paid_amount', 5000)
            ->assertJsonPath('payment_status', 'approved')
            ->assertJsonPath('confirmed_by', 'Jordan Staff')
            ->assertJsonPath('confirmed_role', 'Staff');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'paid_amount' => 5000,
            'payment_status' => 'approved',
        ]);
        $this->actingAs($user)->get(route('user.payments'))
            ->assertOk()
            ->assertSee('Completed Payments')
            ->assertSee('Toyota Vios · Original Booking')
            ->assertSee('Control Number: '.$reservation->control_number)
            ->assertSee('Jordan Staff')
            ->assertSee('Staff');
    }

    public function test_confirming_a_pending_deposit_does_not_add_the_initial_deposit_twice(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['name' => 'Taylor Admin', 'role' => 'admin']);
        $reservation = $this->createReservation($user);
        $reservation->update([
            'total_amount' => 5000,
            'paid_amount' => 1000,
            'payment_status' => 'pending',
            'payment_reference_id' => 'INITIAL-DEPOSIT',
        ]);

        $this->withSession([
            'is_admin' => true,
            'admin_user_id' => $admin->id,
        ])->patchJson(route('admin.billing.confirm', $reservation))
            ->assertOk()
            ->assertJsonPath('paid_amount', 1000)
            ->assertJsonPath('balance', 4000)
            ->assertJsonPath('confirmed_by', 'Taylor Admin');

        $this->actingAs($user)->get(route('user.payments'))
            ->assertOk()
            ->assertSee('PENDING PAYMENT')
            ->assertSee('Pending Balance: ₱4,000.00')
            ->assertDontSee('COMPLETED');
    }

    public function test_confirmation_applies_only_the_new_submitted_booking_payment_amount(): void
    {
        $user = User::factory()->create();
        $staff = User::factory()->create(['name' => 'Morgan Staff', 'role' => 'staff']);
        $reservation = $this->createReservation($user);
        $reservation->update([
            'total_amount' => 5000,
            'paid_amount' => 1000,
            'payment_submitted_amount' => 750,
            'payment_status' => 'pending',
            'payment_reference_id' => 'EXTRA-PAYMENT-REF',
        ]);

        $this->withSession([
            'is_staff' => true,
            'staff_user_id' => $staff->id,
        ])->patchJson(route('staff.billing.confirm', $reservation))
            ->assertOk()
            ->assertJsonPath('amount', 750)
            ->assertJsonPath('paid_amount', 1750)
            ->assertJsonPath('balance', 3250);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'paid_amount' => 1750,
            'payment_submitted_amount' => null,
            'payment_status' => 'approved',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $staff->id,
            'action' => 'reservation.payment_added',
            'entity_id' => $reservation->id,
        ]);
    }

    public function test_payment_submission_requires_a_reference_or_screenshot(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $extension = RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => 1,
            'amount' => 1800,
            'requested_return_date' => now()->addDays(6)->toDateString(),
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'extension_id' => $extension->id,
            'payment_amount' => 100,
        ])->assertSessionHasErrors(['payment_proof', 'reference_id']);
    }

    public function test_user_can_resubmit_booking_payment_while_previous_submission_is_waiting_for_verification(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $reservation->update([
            'payment_status' => 'pending',
            'payment_submitted_amount' => 1000,
            'payment_reference_id' => 'OLD-PENDING-REF',
            'total_amount' => 5000,
        ]);

        $this->actingAs($user)->get(route('user.payments'))
            ->assertOk()
            ->assertSee('Payment is waiting for Admin or Staff verification.')
            ->assertSee('the new submission will replace the unverified one.');

        $this->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'payment_amount' => 1500,
            'reference_id' => 'UPDATED-PENDING-REF',
        ])->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'payment_status' => 'pending',
            'payment_submitted_amount' => 1500,
            'payment_reference_id' => 'UPDATED-PENDING-REF',
        ]);
    }

    public function test_user_booking_payment_submission_is_visible_in_admin_and_staff_billing(): void
    {
        $user = User::factory()->create(['name' => 'Billing Review Customer']);
        $reservation = $this->createReservation($user);
        $reservation->update(['total_amount' => 5000]);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'payment_amount' => 1250,
            'reference_id' => 'STAFF-REVIEW-REF',
        ])->assertRedirect();

        $this->withSession(['is_admin' => true])
            ->get(route('admin.billing'))
            ->assertOk()
            ->assertSee('Billing Review Customer')
            ->assertSee('STAFF-REVIEW-REF')
            ->assertSee('1250');

        $this->withSession(['is_staff' => true])
            ->get(route('staff.billing'))
            ->assertOk()
            ->assertSee('Billing Review Customer')
            ->assertSee('STAFF-REVIEW-REF')
            ->assertSee('1250');
    }

    public function test_pending_extension_payment_is_excluded_from_paid_bookings_and_counted_for_admin_and_staff(): void
    {
        $user = User::factory()->create(['name' => 'Extension Billing Customer']);
        $staff = User::factory()->create(['name' => 'Billing Staff', 'role' => 'staff']);
        $reservation = $this->createReservation($user);
        $reservation->update([
            'total_amount' => 2500,
            'paid_amount' => 2500,
            'payment_status' => 'approved',
        ]);
        $extension = RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => 2,
            'amount' => 5000,
            'paid_amount' => 0,
            'requested_return_date' => now()->addDays(7)->toDateString(),
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'extension_id' => $extension->id,
            'payment_amount' => 5000,
            'reference_id' => 'EXTENSION-TO-CONFIRM',
        ])->assertRedirect();

        foreach ([
            ['is_admin' => true, 'route' => 'admin'],
            ['is_staff' => true, 'staff_user_id' => $staff->id, 'route' => 'staff'],
        ] as $billingUser) {
            $routePrefix = $billingUser['route'];
            unset($billingUser['route']);
            $billingResponse = $this->withSession($billingUser)
                ->get(route($routePrefix.'.billing'))
                ->assertOk()
                ->assertSee('Extension Payments')
                ->assertSee('Extension Payment Pending')
                ->assertSee('Submitted for verification:')
                ->assertSee('EXTENSION-TO-CONFIRM')
                ->assertSee('Confirm Extension Payment');
            $billingResponse->assertSee('function invoiceTotals(inv)', false)
                ->assertSee('const total = Number(inv.total) + extensions.reduce', false)
                ->assertSee('const paid = Number(inv.paid) + extensions.reduce', false)
                ->assertSee('peso(totals.total)', false)
                ->assertSee('Grand Total (including extensions):', false)
                ->assertSee('Ref ID:', false)
                ->assertDontSee("invoiceType: 'extension'", false)
                ->assertDontSee('function rebuildExtensionInvoices()', false)
                ->assertDontSee("displayId: booking.id + ' · Extension '", false);
            $billingContent = $billingResponse->getContent();
            $this->assertLessThan(
                strpos($billingContent, 'Processed Invoices'),
                strpos($billingContent, 'Paid Bookings')
            );
            $this->assertLessThan(
                strpos($billingContent, 'Extension Payments'),
                strpos($billingContent, 'Processed Invoices')
            );
            $this->assertLessThan(
                strpos($billingContent, 'Unpaid Bookings'),
                strpos($billingContent, 'Extension Payments')
            );

            $this->withSession($billingUser)
                ->get(route($routePrefix.'.billing.live'))
                ->assertOk()
                ->assertJsonPath('0.extensionPayments.0.status', 'payment_pending')
                ->assertJsonPath('0.extensionPayments.0.submittedAmount', 5000);
        }
    }

    public function test_original_booking_payment_stores_the_amount_for_verification_and_history(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $reservation->update(['total_amount' => 5000, 'payment_status' => 'unpaid']);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'payment_amount' => 1500,
            'reference_id' => 'ORIGINAL-REF-1500',
        ])->assertRedirect();

        $this->assertSame('1500.00', $reservation->fresh()->payment_submitted_amount);
        $this->assertSame('pending', $reservation->fresh()->payment_status);
        $this->get(route('user.payments'))
            ->assertSee('Submitted for verification: ₱1,500.00')
            ->assertSee('PENDING VERIFICATION');
    }

    public function test_fully_paid_original_booking_is_shown_in_completed_history(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $reservation->update([
            'total_amount' => 5000,
            'paid_amount' => 5000,
            'payment_status' => 'approved',
        ]);

        $this->actingAs($user)->get(route('user.payments'))
            ->assertOk()
            ->assertSee('Completed Payments')
            ->assertSee('COMPLETED')
            ->assertSee('Control Number: '.$reservation->control_number);
    }

    public function test_payment_amount_cannot_exceed_booking_balance_or_extension_due(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $reservation->update(['total_amount' => 1000]);
        $extension = RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => 1,
            'amount' => 1800,
            'requested_return_date' => now()->addDays(6)->toDateString(),
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'payment_amount' => 1001,
            'reference_id' => 'ORIGINAL-REF-OVER',
        ])->assertSessionHasErrors('payment_amount');

        $reservation->update(['paid_amount' => 1000]);
        $this->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'extension_id' => $extension->id,
            'payment_amount' => 1801,
            'reference_id' => 'EXTENSION-REF-OVER',
        ])->assertSessionHasErrors('payment_amount');
    }

    public function test_extension_payment_is_blocked_until_original_booking_balance_is_paid(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $reservation->update(['total_amount' => 5000, 'paid_amount' => 1000]);
        $extension = RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => 2,
            'amount' => 3600,
            'requested_return_date' => now()->addDays(7)->toDateString(),
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'extension_id' => $extension->id,
            'payment_amount' => 1200,
            'reference_id' => 'EXT-BEFORE-BOOKING',
        ])->assertStatus(422);

        $this->assertDatabaseHas('rental_extensions', [
            'id' => $extension->id,
            'status' => 'approved',
            'payment_submitted_amount' => null,
        ]);
    }

    public function test_payment_submission_requires_control_number_to_belong_to_the_selected_user_booking(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $reservation = $this->createReservation($user);
        $otherReservation = $this->createReservation($otherUser);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $otherReservation->control_number,
            'reservation_id' => $reservation->id,
            'payment_amount' => 100,
            'reference_id' => 'WRONG-CONTROL',
        ])->assertNotFound();
    }

    public function test_admin_can_confirm_partial_extension_payment_and_user_can_submit_remaining_balance(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $extension = RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => 2,
            'amount' => 3600,
            'requested_return_date' => now()->addDays(7)->toDateString(),
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'extension_id' => $extension->id,
            'payment_amount' => 1200,
            'reference_id' => 'EXT-PARTIAL-1200',
        ])->assertRedirect();

        $this->withSession(['is_admin' => true])
            ->patchJson(route('admin.billing.extensions.confirm', [$reservation, $extension]))
            ->assertOk()
            ->assertJsonPath('paid_amount', 1200)
            ->assertJsonPath('balance', 2400)
            ->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('rental_extensions', [
            'id' => $extension->id,
            'paid_amount' => 1200,
            'status' => 'approved',
        ]);

        $this->actingAs($user)->get(route('user.payments'))
            ->assertSee('Paid: ₱1,200.00')
            ->assertSee('Pending Balance: ₱2,400.00');

        $this->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'extension_id' => $extension->id,
            'payment_amount' => 2400,
            'reference_id' => 'EXT-FINAL-2400',
        ])->assertRedirect();

        $this->withSession(['is_admin' => true])
            ->patchJson(route('admin.billing.extensions.confirm', [$reservation, $extension]))
            ->assertOk()
            ->assertJsonPath('paid_amount', 3600)
            ->assertJsonPath('balance', 0)
            ->assertJsonPath('status', 'paid');

        $this->actingAs($user)->get(route('user.payments'))
            ->assertSee('COMPLETED')
            ->assertDontSee('data-extension-id="'.$extension->id.'"', false);
    }

    public function test_extension_submission_cannot_exceed_remaining_balance_after_partial_payment(): void
    {
        $user = User::factory()->create();
        $reservation = $this->createReservation($user);
        $extension = RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => 2,
            'amount' => 3600,
            'paid_amount' => 1200,
            'requested_return_date' => now()->addDays(7)->toDateString(),
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post(route('user.payments.store'), [
            'control_number' => $reservation->control_number,
            'reservation_id' => $reservation->id,
            'extension_id' => $extension->id,
            'payment_amount' => 2401,
            'reference_id' => 'EXT-OVER-REMAINING',
        ])->assertSessionHasErrors('payment_amount');
    }

    private function createReservation(User $user): Reservation
    {
        return Reservation::create([
            'user_id' => $user->id,
            'vehicle' => 'Toyota Vios',
            'service_option' => 'pickup',
            'driver_option' => 'self_drive',
            'pickup_date' => now()->addDays(1)->toDateString(),
            'return_date' => now()->addDays(5)->toDateString(),
            'document_paths' => [],
            'privacy_consent' => true,
            'payment_mode' => 'deposit',
            'control_number' => 'PAYMENT-'.uniqid(),
            'status' => 'released',
        ]);
    }
}
