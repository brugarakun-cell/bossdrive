<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Staff\StaffAuthController;
use App\Http\Controllers\Staff\StaffBillingController;
use App\Http\Controllers\Staff\StaffProfileController;
use App\Http\Controllers\Staff\StaffReportController;
use App\Http\Controllers\Staff\StaffReservationController;
use App\Http\Controllers\Staff\StaffVehicleController;
use App\Http\Controllers\User\AccountController;
use App\Http\Controllers\User\ActiveRentalController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\ReservationController;
use App\Http\Controllers\VehicleAvailabilityController;
use App\Models\AuditLog;
use App\Models\ContactInquiry;
use App\Models\PriceGuide;
use App\Models\RentalExtension;
use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('guest.about');
})->name('home');

Route::get('/about', function () {
    return view('guest.about');
})->name('about');

Route::get('/vehicles/availability', VehicleAvailabilityController::class)
    ->name('vehicles.availability');

Route::get('/reservations', function (Request $request) {
    $reservationController = app(ReservationController::class);
    $reservationController->expireUnpaidWalkInReservations();
    $reservationController->synchronizeVehicleStatuses();
    $allUnits = Vehicle::withCount('reservations')->orderBy('name')->orderBy('id')->get();
    $search = strtolower(trim((string) $request->query('search')));
    $capacity = (string) $request->query('capacity');
    $matchingModels = $allUnits
        ->filter(fn ($vehicle) => $search === ''
            || str_contains(strtolower($vehicle->name.' '.$vehicle->plate.' '.$vehicle->category), $search))
        ->pluck('name')
        ->unique();
    $units = $allUnits
        ->filter(fn ($vehicle) => ($capacity === '' || (string) $vehicle->capacity === $capacity)
            && ($search === '' || $matchingModels->contains($vehicle->name)))
        ->values();
    $allUnitsByModel = $allUnits->groupBy('name');
    $unitNumberById = collect();
    $allUnitsByModel->each(function ($modelUnits) use ($unitNumberById): void {
        $modelUnits->values()->each(function ($unit, $index) use ($unitNumberById): void {
            $unitNumberById->put($unit->id, $index + 1);
        });
    });
    $activeReservationsForUnits = Reservation::whereNotIn('status', ['cancelled', 'void', 'completed'])
        ->get(['id', 'vehicle_id']);
    $activeReservationIds = $activeReservationsForUnits->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();
    $activeVehicleIds = $activeReservationsForUnits->pluck('vehicle_id')
        ->filter()
        ->map(fn ($id) => (int) $id)
        ->all();
    $today = now()->toDateString();
    $units->each(function ($vehicle) use ($activeReservationIds, $activeVehicleIds, $today): void {
        $cleanSchedule = collect($vehicle->schedule ?? [])
            ->reject(function (array $entry) use ($activeReservationIds, $today): bool {
                $reservationId = $entry['reservationId'] ?? null;
                if ($reservationId !== null && ! in_array((int) $reservationId, $activeReservationIds, true)) {
                    return true;
                }

                if ($reservationId === null && in_array(($entry['type'] ?? null), ['Reserved', 'Rented'], true)) {
                    return true;
                }

                return in_array(($entry['type'] ?? null), ['Reserved', 'Rented'], true)
                    && filled($entry['end'] ?? null)
                    && (string) $entry['end'] < $today;
            })
            ->values()
            ->all();

        $hasRentalSchedule = collect($cleanSchedule)->contains(
            fn (array $entry): bool => in_array(($entry['type'] ?? null), ['Reserved', 'Rented'], true)
        );
        $hasActiveReservation = in_array((int) $vehicle->id, $activeVehicleIds, true);
        $nextStatus = $vehicle->status === 'maintenance'
            ? 'maintenance'
            : ($hasActiveReservation || $hasRentalSchedule ? 'rented' : 'available');

        if ($cleanSchedule !== ($vehicle->schedule ?? []) || $vehicle->status !== $nextStatus) {
            $vehicle->update(['status' => $nextStatus, 'schedule' => $cleanSchedule]);
            $vehicle->status = $nextStatus;
            $vehicle->schedule = $cleanSchedule;
        }
    });
    $visibleModelNames = $units->pluck('name')->unique()->all();
    $unitsByModel = collect($allUnitsByModel->all())
        ->filter(fn ($modelUnits, $modelName): bool => in_array($modelName, $visibleModelNames, true))
        ->map(fn ($modelUnits): array => $modelUnits
            ->map(fn ($unit): array => [
                'id' => $unit->id,
                'plate' => $unit->plate,
                'category' => $unit->category,
                'transmission' => $unit->transmission,
                'fuel' => $unit->fuel,
                'capacity_type' => $unit->capacity_type ?: (($unit->capacity ?? null) ? $unit->capacity.' Seater' : ''),
                'status' => $unit->status,
                'schedule' => $unit->schedule ?? [],
            ])
            ->values()
            ->all());
    $vehicles = $units->groupBy('name')->map(
        fn ($modelUnits) => $modelUnits->first(fn ($unit) => $unit->status === 'available') ?? $modelUnits->first()
    )->values();

    $activeReservations = Reservation::whereNotIn('status', ['cancelled', 'void', 'completed'])
        ->whereNotNull('pickup_date')
        ->whereNotNull('return_date')
        ->latest('created_at')
        ->get()
        ->unique('vehicle_id')
        ->keyBy('vehicle_id');
    $calendarReservations = Reservation::whereNotIn('status', ['cancelled', 'void', 'completed'])
        ->whereNotNull('pickup_date')
        ->whereNotNull('return_date')
        ->whereNotNull('vehicle_id')
        ->latest('created_at')
        ->get()
        ->unique('id')
        ->map(fn ($reservation) => [
            'vehicle' => $reservation->vehicle,
            'vehicleId' => $reservation->vehicle_id,
            'unitNumber' => $unitNumberById->get($reservation->vehicle_id),
            'plate' => $reservation->vehicleUnit?->plate,
            'status' => $reservation->status === 'released' ? 'Ongoing' : 'Reserved',
            'start' => $reservation->pickup_date->toDateString(),
            'end' => $reservation->return_date->toDateString(),
        ])
        ->values();
    $calendarMaintenance = $allUnits
        ->flatMap(fn ($vehicle) => collect($vehicle->schedule ?? [])
            ->filter(fn (array $entry): bool => ($entry['type'] ?? null) === 'Maintenance')
            ->map(fn (array $entry) => [
                'vehicle' => $vehicle->name,
                'vehicleId' => $vehicle->id,
                'unitNumber' => $unitNumberById->get($vehicle->id),
                'status' => 'Maintenance',
                'start' => $entry['start'] ?? null,
                'end' => $entry['end'] ?? null,
            ]))
        ->filter(fn (array $entry): bool => filled($entry['start']) && filled($entry['end']))
        ->unique(fn (array $entry): string => $entry['vehicleId'].':'.$entry['start'].':'.$entry['end'])
        ->values();

    return view('guest.reservation', compact('vehicles', 'unitsByModel', 'activeReservations', 'calendarReservations', 'calendarMaintenance'))
        ->with('priceGuides', PriceGuide::current());
})->name('reservations');

Route::post('/reservations', function (Request $request) {
    $request->validate([
        'pickup_date' => ['required', 'date'],
        'return_date' => ['required', 'date', 'after:pickup_date'],
    ]);

    return back()->with('success', 'Reservation details received. Please log in to complete your booking.');
})->middleware('auth')->name('reservations.submit');

Route::get('/policy', function () {
    return view('guest.policy');
})->name('policy');

Route::get('/contact', function () {
    return view('guest.contact');
})->name('contact');

Route::post('/contact', function (Request $request) {
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:30'],
        'email' => ['required', 'email', 'max:255'],
        'subject' => ['required', 'string', 'max:255'],
        'message' => ['required', 'string', 'max:2000'],
    ]);

    ContactInquiry::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'subject' => $data['subject'],
        'message' => $data['message'],
        'status' => 'unresolved',
    ]);

    return back()->with('success', 'Thank you! Your message has been received.');
})->name('contact.submit');

Route::middleware('guest')->group(function () {
    Route::get('/user/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/user/login', [AuthController::class, 'login'])->name('user.login.submit');
    Route::get('/user/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/user/register/personal', [AuthController::class, 'registerPersonal'])->name('user.register.personal');
    Route::post('/user/register/verify-email', [AuthController::class, 'verifyRegistrationEmail'])->name('user.register.verify-email');
    Route::post('/user/register/resend-email', [AuthController::class, 'resendRegistrationEmail'])->name('user.register.resend-email');
    Route::post('/user/register/back', [AuthController::class, 'registrationBack'])->name('user.register.back');
    Route::post('/user/register/address', [AuthController::class, 'completeRegistration'])->name('user.register.address');
    Route::get('/user/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/user/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/user/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset.code');
    Route::get('/user/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/user/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::get('/user/dashboard', function () {
    $reservationController = app(ReservationController::class);
    $reservationController->expireUnpaidWalkInReservations();
    $reservationController->synchronizeVehicleStatuses();
    $user = request()->user();
    $reservations = $user->reservations()
        ->latest()
        ->get();

    return view('user.dashboard', [
        'user' => $user,
        'reservations' => $reservations,
        'latestReservation' => $reservations->first(),
        'approvedDocuments' => $reservations->first()?->document_paths ?? [],
    ]);
})->middleware('auth')->name('user.dashboard');

Route::get('/user/dashboard/live', function () {
    $reservationController = app(ReservationController::class);
    $reservationController->expireUnpaidWalkInReservations();
    $reservationController->synchronizeVehicleStatuses();
    $carImages = [
        'Toyota Vios' => 'car1.png',
        'Mitsubishi Mirage' => 'car2.png',
        'Honda Civic' => 'car3.png',
        'Toyota Fortuner' => 'car4.png',
    ];
    $reservations = request()->user()->reservations()
        ->with('vehicleUnit:id,plate')
        ->latest()
        ->get()
        ->map(fn (Reservation $reservation): array => [
            'id' => $reservation->id,
            'vehicle' => $reservation->vehicle,
            'image' => asset('image/'.($carImages[$reservation->vehicle] ?? 'car1.png')),
            'plate' => $reservation->vehicleUnit?->plate ?: 'Not assigned',
            'control_number' => $reservation->control_number,
            'status' => strtoupper($reservation->status),
            'service_option' => ucfirst($reservation->service_option),
            'payment_mode' => ucfirst($reservation->payment_mode),
            'pickup_date' => $reservation->pickup_date?->format('M d, Y'),
            'pickup_time' => $reservation->pickup_time ? substr($reservation->pickup_time, 0, 5) : null,
            'return_date' => $reservation->return_date?->format('M d, Y'),
            'driver_option' => $reservation->driver_option,
            'assigned_driver_name' => $reservation->assigned_driver_name,
            'assigned_driver_contact' => $reservation->assigned_driver_contact,
            'delivery_address' => $reservation->delivery_address,
            'rejection_comment' => $reservation->rejection_comment,
            'cancel_url' => route('user.reservations.cancel', $reservation),
            'documents_url' => route('user.reservations.documents.update', $reservation),
        ])
        ->values();

    return response()->json(['reservations' => $reservations])
        ->header('Cache-Control', 'no-store, private');
})->middleware('auth')->name('user.dashboard.live');

Route::middleware('auth')->group(function () {
    Route::get('/user/account', [AccountController::class, 'edit'])->name('user.account');
    Route::put('/user/account', [AccountController::class, 'update'])->name('user.account.update');
    Route::put('/user/account/password', [AccountController::class, 'updatePassword'])->name('user.account.password');
    Route::get('/user/active-rental', [ActiveRentalController::class, 'show'])->name('user.active-rental');
    Route::post('/user/active-rental/verify', [ActiveRentalController::class, 'verify'])->name('user.active-rental.verify');
    Route::post('/user/active-rental/{reservation}/condition', [ActiveRentalController::class, 'submitCondition'])
        ->name('user.active-rental.condition');
    Route::post('/user/active-rental/{reservation}/extension', [ActiveRentalController::class, 'requestExtension'])
        ->name('user.active-rental.extension');
    Route::post('/user/active-rental/{reservation}/return', [ActiveRentalController::class, 'initiateReturn'])
        ->name('user.active-rental.return');
    Route::get('/user/reservations', [ReservationController::class, 'create'])
        ->name('user.reservations');
    Route::post('/user/reservations', [ReservationController::class, 'store'])
        ->name('user.reservations.store');
    Route::post('/user/vehicles/{vehicle}/feedback', [ReservationController::class, 'feedback'])
        ->name('user.vehicles.feedback');
    Route::post('/user/reservations/{reservation}/documents', [ReservationController::class, 'updateDocuments'])
        ->name('user.reservations.documents.update');
    Route::post('/user/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
        ->name('user.reservations.cancel');
    Route::get('/user/payments', [PaymentController::class, 'index'])->name('user.payments');
    Route::post('/user/payments', [PaymentController::class, 'store'])->name('user.payments.store');
});

Route::post('/user/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('user.logout');

// Admin authentication is session-based and intentionally independent of the
// customer guard, so a signed-in customer can still access the admin login.
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');

Route::get('/staff/login', [StaffAuthController::class, 'showLogin'])->name('staff.login');
Route::post('/staff/login', [StaffAuthController::class, 'login'])->name('staff.login.submit');
Route::post('/staff/logout', [StaffAuthController::class, 'logout'])->name('staff.logout');

Route::middleware('staff')->prefix('staff')->name('staff.')->group(function () {
    Route::get('/reservations', [StaffReservationController::class, 'index'])->name('reservations');
    Route::get('/reservations/live', [AdminManagementController::class, 'liveReservations'])
        ->name('reservations.live');
    Route::get('/walk-in-bookings', [StaffReservationController::class, 'walkInReservations'])->name('walk-in-bookings');
    Route::post('/reservations', [AdminManagementController::class, 'storeReservation'])->name('reservations.store');
    Route::delete('/reservations/{reservation}', [AdminManagementController::class, 'destroyReservation'])->name('reservations.destroy');
    Route::patch('/reservations/{reservation}/status', [StaffReservationController::class, 'updateStatus'])->name('reservations.status');
    Route::get('/profile', [StaffProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [StaffProfileController::class, 'update'])->name('profile.update');
    Route::get('/vehicles', [StaffVehicleController::class, 'index'])->name('vehicles');
    Route::get('/drivers', [StaffVehicleController::class, 'drivers'])->name('drivers');
    Route::patch('/drivers/{driver}/availability', [AdminManagementController::class, 'updateDriverAvailability'])->name('drivers.availability');
    Route::post('/vehicles', [AdminManagementController::class, 'storeVehicle'])->name('vehicles.store');
    Route::patch('/vehicles/{vehicle}', [AdminManagementController::class, 'updateVehicle'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}/feedback/{feedbackIndex}/reply', [AdminManagementController::class, 'destroyVehicleFeedbackReply'])
        ->name('vehicles.feedback.reply.destroy');
    Route::delete('/vehicles/{vehicle}', [AdminManagementController::class, 'destroyVehicle'])->name('vehicles.destroy');
    Route::get('/billing', [StaffBillingController::class, 'index'])->name('billing');
    Route::get('/billing/live', function () {
        return response()->json(Reservation::with('extensions')->latest()->get()
            ->map(fn ($reservation) => [
                'id' => $reservation->id,
                'total_amount' => (float) $reservation->total_amount,
                'paid_amount' => (float) $reservation->paid_amount,
                'submitted_amount' => (float) $reservation->payment_submitted_amount,
                'payment_status' => $reservation->payment_status,
                'verification_pending' => $reservation->payment_status === 'pending'
                    && ((float) $reservation->payment_submitted_amount > 0
                        || ((float) $reservation->paid_amount > 0 && ($reservation->payment_proof_path || $reservation->payment_reference_id))),
                'reference' => $reservation->payment_reference_id ?: 'No reference',
                'proof' => $reservation->payment_proof_path ? asset('storage/'.ltrim($reservation->payment_proof_path, '/')) : null,
                'extensionPayments' => $reservation->extensions->map(fn ($extension) => [
                    'id' => $extension->id,
                    'control' => $reservation->control_number,
                    'days' => (int) $extension->days,
                    'amount' => (float) $extension->amount,
                    'paidAmount' => (float) $extension->paid_amount,
                    'balance' => max((float) $extension->amount - (float) $extension->paid_amount, 0),
                    'submittedAmount' => (float) $extension->payment_submitted_amount,
                    'status' => $extension->status,
                    'reference' => $extension->payment_reference_id ?: 'No reference',
                    'proof' => $extension->payment_proof_path ? asset('storage/'.ltrim($extension->payment_proof_path, '/')) : null,
                    'date' => $extension->created_at?->format('M j, Y'),
                    'paymentActor' => AuditLog::with('actor')
                        ->where('entity_type', RentalExtension::class)
                        ->where('entity_id', $extension->id)
                        ->where('action', 'rental_extension.payment_confirmed')
                        ->latest()
                        ->get()
                        ->map(fn ($log) => [
                            'name' => $log->actor?->name ?: 'System',
                            'role' => $log->actor?->role === 'admin' ? 'Admin' : ($log->actor?->role === 'staff' ? 'Staff' : 'System'),
                            'action' => 'Extension payment confirmed',
                            'amount' => $log->metadata['amount'] ?? null,
                            'date' => $log->created_at?->format('M j, Y g:i A'),
                        ])->values()->all(),
                ])->values()->all(),
            ]));
    })->name('billing.live');
    Route::patch('/billing/{reservation}/status', [AdminManagementController::class, 'updatePaymentStatus'])
        ->name('billing.status');
    Route::patch('/billing/{reservation}/confirm', [AdminManagementController::class, 'confirmBookingPayment'])
        ->name('billing.confirm');
    Route::post('/billing/{reservation}/payment', [AdminManagementController::class, 'addPayment'])
        ->name('billing.payment');
    Route::patch('/billing/{reservation}/extensions/{extension}/confirm', [AdminManagementController::class, 'confirmExtensionPayment'])
        ->name('billing.extensions.confirm');
    Route::get('/active-rentals', [StaffReservationController::class, 'activeRentals'])->name('active-rentals');
    Route::patch('/active-rentals/{reservation}/extend', [AdminManagementController::class, 'extendActiveRental'])
        ->name('active-rentals.extend');
    Route::get('/reports', [StaffReportController::class, 'index'])->name('reports');
    Route::delete('/reports/inquiries/{inquiry}', [StaffReportController::class, 'destroyInquiry'])->name('reports.inquiries.destroy');
    Route::delete('/reports/pickup-reports/{report}', [StaffReportController::class, 'destroyPickupReport'])->name('reports.pickup.destroy');
});

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/live', [AdminDashboardController::class, 'liveOverview'])->name('dashboard.live');
    Route::get('/profile', [AdminAuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AdminAuthController::class, 'updateProfile'])->name('profile.update');
    Route::post('/reservations', [AdminManagementController::class, 'storeReservation'])->name('reservations.store');
    Route::get('/reservations', [AdminManagementController::class, 'reservations'])->name('reservations');
    Route::get('/walk-in-bookings', [AdminManagementController::class, 'walkInReservations'])->name('walk-in-bookings');
    Route::get('/reservations/live', [AdminManagementController::class, 'liveReservations'])
        ->name('reservations.live');
    Route::patch('/reservations/{reservation}/status', [AdminManagementController::class, 'updateReservationStatus'])
        ->name('reservations.status');
    Route::delete('/reservations/{reservation}', [AdminManagementController::class, 'destroyReservation'])
        ->name('reservations.destroy');
    Route::get('/users', [AdminManagementController::class, 'users'])->name('users');
    Route::get('/users/{user}', [AdminManagementController::class, 'user'])->name('users.show');
    Route::get('/billing', [AdminManagementController::class, 'billing'])->name('billing');
    Route::get('/billing/live', function () {
        return response()->json(Reservation::with('extensions')->latest()->get()
            ->map(fn ($reservation) => [
                'id' => $reservation->id,
                'total_amount' => (float) $reservation->total_amount,
                'paid_amount' => (float) $reservation->paid_amount,
                'submitted_amount' => (float) $reservation->payment_submitted_amount,
                'payment_status' => $reservation->payment_status,
                'verification_pending' => $reservation->payment_status === 'pending'
                    && ((float) $reservation->payment_submitted_amount > 0
                        || ((float) $reservation->paid_amount > 0 && ($reservation->payment_proof_path || $reservation->payment_reference_id))),
                'reference' => $reservation->payment_reference_id ?: 'No reference',
                'proof' => $reservation->payment_proof_path ? asset('storage/'.ltrim($reservation->payment_proof_path, '/')) : null,
                'extensionPayments' => $reservation->extensions->map(fn ($extension) => [
                    'id' => $extension->id,
                    'control' => $reservation->control_number,
                    'days' => (int) $extension->days,
                    'amount' => (float) $extension->amount,
                    'paidAmount' => (float) $extension->paid_amount,
                    'balance' => max((float) $extension->amount - (float) $extension->paid_amount, 0),
                    'submittedAmount' => (float) $extension->payment_submitted_amount,
                    'status' => $extension->status,
                    'reference' => $extension->payment_reference_id ?: 'No reference',
                    'proof' => $extension->payment_proof_path ? asset('storage/'.ltrim($extension->payment_proof_path, '/')) : null,
                    'date' => $extension->created_at?->format('M j, Y'),
                    'paymentActor' => AuditLog::with('actor')
                        ->where('entity_type', RentalExtension::class)
                        ->where('entity_id', $extension->id)
                        ->where('action', 'rental_extension.payment_confirmed')
                        ->latest()
                        ->get()
                        ->map(fn ($log) => [
                            'name' => $log->actor?->name ?: 'System',
                            'role' => $log->actor?->role === 'admin' ? 'Admin' : ($log->actor?->role === 'staff' ? 'Staff' : 'System'),
                            'action' => 'Extension payment confirmed',
                            'amount' => $log->metadata['amount'] ?? null,
                            'date' => $log->created_at?->format('M j, Y g:i A'),
                        ])->values()->all(),
                ])->values()->all(),
            ]));
    })->name('billing.live');
    Route::patch('/billing/{reservation}/status', [AdminManagementController::class, 'updatePaymentStatus'])
        ->name('billing.status');
    Route::patch('/billing/{reservation}/confirm', [AdminManagementController::class, 'confirmBookingPayment'])
        ->name('billing.confirm');
    Route::post('/billing/{reservation}/payment', [AdminManagementController::class, 'addPayment'])
        ->name('billing.payment');
    Route::patch('/billing/{reservation}/extensions/{extension}/confirm', [AdminManagementController::class, 'confirmExtensionPayment'])
        ->name('billing.extensions.confirm');
    Route::get('/active-rentals', [AdminManagementController::class, 'activeRentals'])->name('active-rentals');
    Route::patch('/active-rentals/{reservation}/status', [AdminManagementController::class, 'updateActiveRentalStatus'])
        ->name('active-rentals.status');
    Route::patch('/active-rentals/{reservation}/extend', [AdminManagementController::class, 'extendActiveRental'])
        ->name('active-rentals.extend');
    Route::get('/vehicles', [AdminManagementController::class, 'vehicles'])->name('vehicles');
    Route::get('/drivers', [AdminManagementController::class, 'drivers'])->name('drivers');
    Route::patch('/drivers/{driver}/availability', [AdminManagementController::class, 'updateDriverAvailability'])->name('drivers.availability');
    Route::post('/vehicles', [AdminManagementController::class, 'storeVehicle'])->name('vehicles.store');
    Route::patch('/vehicles/{vehicle}', [AdminManagementController::class, 'updateVehicle'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}/feedback/{feedbackIndex}/reply', [AdminManagementController::class, 'destroyVehicleFeedbackReply'])
        ->name('vehicles.feedback.reply.destroy');
    Route::delete('/vehicles/{vehicle}', [AdminManagementController::class, 'destroyVehicle'])->name('vehicles.destroy');
    Route::put('/price-guide', [AdminManagementController::class, 'updatePriceGuide'])->name('price-guide.update');
    Route::get('/payment-settings', [AdminManagementController::class, 'paymentSettings'])->name('payment-settings');
    Route::put('/payment-settings', [AdminManagementController::class, 'updatePaymentSettings'])->name('payment-settings.update');
    Route::post('/users', [AdminManagementController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}', [AdminManagementController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminManagementController::class, 'destroyUser'])->name('users.destroy');
    Route::get('/reports', [AdminManagementController::class, 'reports'])->name('reports');
    Route::delete('/inquiries/{inquiry}', [AdminManagementController::class, 'destroyInquiry'])->name('inquiries.destroy');
    Route::delete('/reports/pickup-reports/{report}', [AdminManagementController::class, 'destroyPickupReport'])
        ->name('reports.pickup.destroy');
    Route::delete('/audit-logs/{auditLog}', [AdminManagementController::class, 'destroyAuditLog'])->name('audit-logs.destroy');
    Route::delete('/reports/{type}/all', [AdminManagementController::class, 'destroyAllReportRecords'])->name('reports.delete-all');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// Keep the restored admin UI's original links working while the pages use
// Laravel routes instead of standalone HTML files.
Route::middleware('admin')->group(function () {
    Route::redirect('/admin/Admin-Dashboard.html', '/admin/dashboard');
    Route::redirect('/admin/Admin-Reservations Management.html', '/admin/reservations');
    Route::redirect('/admin/Admin-Vehicle Management.html', '/admin/vehicles');
    Route::redirect('/admin/Admin-Payments & Billing Records.html', '/admin/billing');
    Route::redirect('/admin/Admin-Active Rentals.html', '/admin/active-rentals');
    Route::redirect('/admin/Admin-userAccoute.html', '/admin/users');
    Route::redirect('/admin/Admin-Report.html', '/admin/reports');
    Route::redirect('/admin/Admin-Profile.html', '/admin/profile');
});
