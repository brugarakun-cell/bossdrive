<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\ReservationController as UserReservationController;
use App\Models\AuditLog;
use App\Models\ContactInquiry;
use App\Models\OnCallDriver;
use App\Models\PaymentSetting;
use App\Models\PickupConditionReport;
use App\Models\PriceGuide;
use App\Models\RentalExtension;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AdminManagementController extends Controller
{
    private const RESERVATION_STATUSES = ['pending', 'verified', 'processing', 'released', 'completed', 'cancelled', 'void'];

    private const PAYMENT_STATUSES = ['unpaid', 'pending', 'approved', 'rejected'];

    private const CITY_DRIVING_PRICES = [
        'sedan' => 2500,
        'expanded — diesel' => 3500,
        'expanded - diesel' => 3500,
        'pick-up / expanded (7-seater) (2 days) — gas' => 4500,
        'pick-up / expanded (7-seater) - gas' => 4500,
    ];

    public function reservations(Request $request): View
    {
        $status = $request->query('status');
        $walkInOnly = $request->boolean('walkin');
        $query = Reservation::with(['user', 'pickupConditionReports', 'vehicleUnit'])->latest();

        $query->where('booking_source', $walkInOnly ? 'admin_staff' : 'user');
        if (in_array($status, self::RESERVATION_STATUSES, true)) {
            $query->where('status', $status);
        }

        $reservations = $query->with('extensions')->paginate(20)->withQueryString();

        return view('admin.reservations', [
            'reservations' => $reservations,
            'walkInOnly' => $walkInOnly,
            'driverCatalog' => $this->driverCatalog(),
            'reservationRows' => $reservations->getCollection()->map(function ($reservation) {
                $user = $reservation->user;
                $vehicle = $reservation->vehicleUnit;
                $days = $reservation->rentalDays();
                $totalAmount = (float) $reservation->total_amount;
                if ($totalAmount <= 0) {
                    $totalAmount = ($vehicle?->price ?? 0) * $days
                        + ($reservation->driver_option === 'with_driver' ? 1500 * $days : 0);
                }
                $paidAmount = (float) $reservation->paid_amount;
                $customerName = $reservation->customer_name ?: ($user?->name ?? 'Walk-in Customer');
                $customerPhone = $reservation->customer_phone ?: ($user?->contact_number ?? 'No contact');

                return [
                    'id' => $reservation->id,
                    'vehicleId' => $reservation->vehicle_id,
                    'vehiclePlate' => $vehicle?->plate,
                    'vehicleCategory' => $vehicle?->category,
                    'vehicleTransmission' => $vehicle?->transmission,
                    'vehicleFuel' => $vehicle?->fuel,
                    'vehicleCapacity' => $vehicle?->capacity_type ?: (($vehicle?->capacity ?? null) ? $vehicle->capacity.' Seater' : null),
                    'name' => $customerName,
                    'age' => $reservation->customer_age ?? $user?->age,
                    'phone' => $customerPhone,
                    'address' => collect([$user?->province, $user?->city, $user?->barangay, $user?->address])->filter()->implode(', ') ?: ($reservation->delivery_address ?: 'No address provided'),
                    'service' => $reservation->service_option === 'delivery' ? 'Delivery' : 'Pick-up',
                    'rate' => match ($reservation->rate_type) {
                        'province' => 'Province',
                        'long_distance' => 'Long Distance (2 Days)',
                        default => 'City Driving',
                    },
                    'driver' => $reservation->driver_option === 'with_driver' ? 'With Driver' : 'Self-Drive',
                    'assignedDriverName' => $reservation->assigned_driver_name,
                    'assignedDriverContact' => $reservation->assigned_driver_contact,
                    'target' => $reservation->delivery_address ?: 'Main Office',
                    'deliveryNotes' => $reservation->delivery_notes,
                    'payment' => ucfirst($reservation->payment_mode),
                    'paymentStatus' => $reservation->payment_status,
                    'paymentSubmittedAmount' => (float) $reservation->payment_submitted_amount,
                    'paidAmount' => $paidAmount,
                    'totalAmount' => $totalAmount,
                    'balance' => max($totalAmount - $paidAmount, 0),
                    'reference' => $reservation->payment_reference_id ?: 'No reference',
                    'control' => $reservation->control_number,
                    'bookingSource' => $reservation->booking_source,
                    'status' => $reservation->status,
                    'documentsNeedReview' => $this->documentsNeedReview($reservation),
                    'pickupDate' => $reservation->pickup_date?->format('F j, Y'),
                    'pickupDateIso' => $reservation->pickup_date?->toDateString(),
                    'pickupTime' => $reservation->pickup_time ? substr($reservation->pickup_time, 0, 5) : null,
                    'returnDate' => $reservation->return_date?->format('F j, Y'),
                    'returnTime' => $reservation->return_time ? substr($reservation->return_time, 0, 5) : null,
                    'documents' => collect($reservation->document_paths ?? [])->map(fn ($path) => $this->uploadedFileUrl($path)),
                    'paymentProof' => $this->uploadedFileUrl($reservation->payment_proof_path),
                    'returnCondition' => [
                        'checks' => $reservation->return_condition_checks ?? [],
                        'notes' => $reservation->return_condition_notes,
                        'date' => $reservation->return_condition_checked_at?->format('M j, Y g:i A'),
                        'name' => $reservation->return_condition_checked_by,
                        'role' => $reservation->return_condition_checked_by_role,
                    ],
                    'pickupConditionReport' => $reservation->pickupConditionReports->sortByDesc('created_at')->first(fn ($report) => true) ? [
                        'checks' => $reservation->pickupConditionReports->sortByDesc('created_at')->first()->checks ?? [],
                        'notes' => $reservation->pickupConditionReports->sortByDesc('created_at')->first()->notes,
                        'photo' => $this->uploadedFileUrl($reservation->pickupConditionReports->sortByDesc('created_at')->first()->photo_path),
                        'date' => $reservation->pickupConditionReports->sortByDesc('created_at')->first()->created_at?->format('M j, Y g:i A'),
                    ] : null,
                    'extensionPayments' => $reservation->extensions->map(fn ($extension) => [
                        'id' => $extension->id,
                        'control' => $reservation->control_number,
                        'days' => (int) $extension->days,
                        'amount' => (float) $extension->amount,
                        'paidAmount' => (float) $extension->paid_amount,
                        'balance' => max((float) $extension->amount - (float) $extension->paid_amount, 0),
                        'submittedAmount' => (float) $extension->payment_submitted_amount,
                        'status' => $extension->status,
                        'reference' => $extension->payment_reference_id,
                        'proof' => $this->uploadedFileUrl($extension->payment_proof_path),
                        'date' => $extension->created_at?->format('M j, Y'),
                    ])->values()->all(),
                ];
            })->values()->all(),
            'status' => $status,
            'statuses' => self::RESERVATION_STATUSES,
        ]);
    }

    public function walkInReservations(Request $request): View
    {
        $request->merge(['walkin' => true, 'admin_staff_booking' => true]);

        return $this->reservations($request);
    }

    public function liveReservations(Request $request): JsonResponse
    {
        $query = Reservation::with(['user', 'pickupConditionReports', 'vehicleUnit'])->latest();
        $query->where('booking_source', $request->boolean('walkin') ? 'admin_staff' : 'user');
        $reservations = $query->get();
        $includeDocuments = $request->session()->get('is_admin') === true;

        return response()->json($reservations->map(function (Reservation $reservation) use ($includeDocuments) {
            $user = $reservation->user;
            $vehicle = $reservation->vehicleUnit;
            $days = $reservation->rentalDays();
            $totalAmount = (float) $reservation->total_amount;
            if ($totalAmount <= 0) {
                $totalAmount = ($vehicle?->price ?? 0) * $days
                    + ($reservation->driver_option === 'with_driver' ? 1500 * $days : 0);
            }
            $paidAmount = (float) $reservation->paid_amount;
            $customerName = $reservation->customer_name ?: ($user?->name ?? 'Walk-in Customer');
            $customerPhone = $reservation->customer_phone ?: ($user?->contact_number ?? 'No contact');
            $documentsVerified = ! $user || ($user->documents_verified_at
                && (! $user->documents_rejected_at
                    || $user->documents_rejected_at <= $user->documents_verified_at));

            $row = [
                'id' => $reservation->id,
                'vehicleId' => $reservation->vehicle_id,
                'vehicle' => $reservation->vehicle,
                'vehiclePlate' => $vehicle?->plate,
                'vehicleCategory' => $vehicle?->category,
                'vehicleTransmission' => $vehicle?->transmission,
                'vehicleFuel' => $vehicle?->fuel,
                'vehicleCapacity' => $vehicle?->capacity_type ?: (($vehicle?->capacity ?? null) ? $vehicle->capacity.' Seater' : null),
                'name' => $customerName,
                'age' => $reservation->customer_age ?? $user?->age,
                'phone' => $customerPhone,
                'email' => $reservation->customer_email ?: ($user?->email ?? 'No email'),
                'address' => collect([$user?->province, $user?->city, $user?->barangay, $user?->address])->filter()->implode(', ') ?: ($reservation->delivery_address ?: 'No address provided'),
                'service' => $reservation->service_option === 'delivery' ? 'Delivery' : 'Pick-up',
                'rate' => match ($reservation->rate_type) {
                    'province' => 'Province',
                    'long_distance' => 'Long Distance (2 Days)',
                    default => 'City Driving',
                },
                'driver' => $reservation->driver_option === 'with_driver' ? 'With Driver' : 'Self-Drive',
                'assignedDriverName' => $reservation->assigned_driver_name,
                'assignedDriverContact' => $reservation->assigned_driver_contact,
                'target' => $reservation->delivery_address ?: 'Main Office',
                'deliveryNotes' => $reservation->delivery_notes,
                'payment' => ucfirst($reservation->payment_mode),
                'paymentStatus' => $reservation->payment_status,
                'payMethod' => ucfirst((string) $reservation->payment_mode),
                'paidAmount' => $paidAmount,
                'totalAmount' => $totalAmount,
                'balance' => max($totalAmount - $paidAmount, 0),
                'reference' => $reservation->payment_reference_id ?: 'No reference',
                'control' => $reservation->control_number,
                'documentsVerified' => (bool) $documentsVerified,
                'isWalkIn' => $reservation->booking_source === 'admin_staff',
                'bookingSource' => $reservation->booking_source,
                'status' => $reservation->status,
                'documentsNeedReview' => $this->documentsNeedReview($reservation),
                'pickupDate' => $reservation->pickup_date?->format('F j, Y'),
                'pickupDateIso' => $reservation->pickup_date?->toDateString(),
                'pickupTime' => $reservation->pickup_time ? substr($reservation->pickup_time, 0, 5) : null,
                'returnDate' => $reservation->return_date?->format('F j, Y'),
                'returnTime' => $reservation->return_time ? substr($reservation->return_time, 0, 5) : null,
                'paymentProof' => $this->uploadedFileUrl($reservation->payment_proof_path),
                'returnCondition' => [
                    'checks' => $reservation->return_condition_checks ?? [],
                    'notes' => $reservation->return_condition_notes,
                    'date' => $reservation->return_condition_checked_at?->format('M j, Y g:i A'),
                    'name' => $reservation->return_condition_checked_by,
                    'role' => $reservation->return_condition_checked_by_role,
                ],
                'pickupConditionReport' => $reservation->pickupConditionReports
                    ->sortByDesc('created_at')
                    ->map(fn ($report) => [
                        'checks' => $report->checks ?? [],
                        'notes' => $report->notes,
                        'photo' => $this->uploadedFileUrl($report->photo_path),
                        'date' => $report->created_at?->format('M j, Y g:i A'),
                    ])->first(),
            ];

            if ($includeDocuments) {
                $row['documents'] = collect($reservation->document_paths ?? [])
                    ->map(fn ($path) => $this->uploadedFileUrl($path));
            }

            return $row;
        })->values());
    }

    public function storeReservation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_age' => ['required', 'integer', 'min:18', 'max:120'],
            'customer_birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'customer_email' => ['required', 'email', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'return_time' => ['required', 'date_format:H:i'],
            'rate_type' => ['required', 'string', 'in:city,province,long_distance'],
            'service_option' => ['required', 'string', 'in:pickup,delivery'],
            'driver_option' => ['required', 'string', 'in:self_drive,with_driver'],
            'driver_id' => ['nullable', 'required_if:driver_option,with_driver', 'exclude_unless:driver_option,with_driver', 'integer', 'exists:on_call_drivers,id'],
            'delivery_province' => ['required_if:service_option,delivery', 'nullable', 'string', 'max:255'],
            'delivery_city' => ['required_if:service_option,delivery', 'nullable', 'string', 'max:255'],
            'delivery_barangay' => ['required_if:service_option,delivery', 'nullable', 'string', 'max:255'],
            'delivery_street' => ['required_if:service_option,delivery', 'nullable', 'string', 'max:1000'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'payment_mode' => ['required', 'string', 'in:full,deposit,walkin'],
            'gcash_reference' => ['nullable', 'string', 'digits_between:13,17'],
        ]);

        return DB::transaction(function () use ($data): JsonResponse {
            $vehicle = Vehicle::lockForUpdate()->findOrFail($data['vehicle_id']);
            if (in_array(strtolower((string) $vehicle->status), ['maintenance', 'unavailable'], true)) {
                return response()->json(['message' => 'This vehicle is currently unavailable.'], 422);
            }
            if ($vehicle->hasAvailabilityConflict($data['start_date'], $data['end_date'])) {
                return response()->json(['message' => 'This vehicle unit is already reserved for the selected dates.'], 422);
            }

            $pickupDateTime = Carbon::parse($data['start_date'].' '.$data['pickup_time']);
            $returnDateTime = Carbon::parse($data['end_date'].' '.$data['return_time']);
            if ($returnDateTime->lessThanOrEqualTo($pickupDateTime)) {
                return response()->json(['message' => 'Return date and time must be later than pickup date and time.'], 422);
            }
            $rentalDurationSeconds = $returnDateTime->getTimestamp() - $pickupDateTime->getTimestamp();
            $days = max(1, (int) ceil($rentalDurationSeconds / 86400));
            if ($data['rate_type'] === 'long_distance' && $rentalDurationSeconds < 2 * 86400) {
                return response()->json(['message' => 'Long Distance bookings require a minimum rental period of 2 full days.'], 422);
            }

            $driver = null;
            if ($data['driver_option'] === 'with_driver') {
                $driver = OnCallDriver::query()->lockForUpdate()->findOrFail($data['driver_id']);
                $weekStart = now()->startOfWeek()->toDateString();
                if ($driver->availability_week?->toDateString() !== $weekStart) {
                    $driver->update([
                        'available_this_week' => true,
                        'availability_week' => $weekStart,
                    ]);
                    $driver->refresh();
                }

                if (! $driver->available_this_week) {
                    return response()->json(['message' => 'This driver is marked unavailable in Driver Management.'], 422);
                }

                $driverHasConflict = Reservation::query()
                    ->where('assigned_driver_name', $driver->name)
                    ->whereIn('status', ['verified', 'processing', 'released'])
                    ->whereDate('pickup_date', '<=', $data['end_date'])
                    ->whereDate('return_date', '>=', $data['start_date'])
                    ->exists();

                if ($driverHasConflict) {
                    return response()->json(['message' => 'This driver is already assigned to another booking for the selected dates.'], 422);
                }
            }

            $maximumDays = match ($data['rate_type']) {
                'province' => 14,
                'long_distance' => 30,
                default => 7,
            };
            if ($days > $maximumDays || ($data['rate_type'] === 'long_distance' && $days < 2)) {
                return response()->json(['message' => 'The selected dates exceed the allowed rental duration for this rate.'], 422);
            }
            $rate = match ($data['rate_type']) {
                'province' => (float) $vehicle->price + (str_contains(strtolower((string) $vehicle->category), 'diesel') ? 500 : 1000),
                'long_distance' => (float) $vehicle->price + 1500,
                default => (float) $vehicle->price,
            };
            $driverFee = $data['driver_option'] === 'with_driver' ? 1500 * $days : 0;
            $totalAmount = ($rate * $days) + $driverFee;
            $paymentMode = $data['payment_mode'];
            $paidAmount = $paymentMode === 'full' ? $totalAmount : ($paymentMode === 'deposit' ? 1000 : 0);

            $reservation = Reservation::create([
                'user_id' => null,
                'vehicle_id' => $vehicle->id,
                'booking_source' => 'admin_staff',
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_age' => $data['customer_age'],
                'customer_birth_date' => $data['customer_birth_date'],
                'customer_email' => $data['customer_email'],
                'vehicle' => $vehicle->name,
                'rate_type' => $data['rate_type'],
                'service_option' => $data['service_option'],
                'driver_option' => $data['driver_option'],
                'assigned_driver_name' => $driver?->name,
                'assigned_driver_contact' => $driver?->contact,
                'delivery_address' => $data['service_option'] === 'delivery'
                    ? collect([
                        $data['delivery_province'] ?? null,
                        $data['delivery_city'] ?? null,
                        $data['delivery_barangay'] ?? null,
                        $data['delivery_street'] ?? null,
                    ])->filter()->implode(', ')
                    : null,
                'delivery_notes' => $data['service_option'] === 'delivery'
                    ? ($data['delivery_notes'] ?? null)
                    : null,
                'delivery_latitude' => $data['service_option'] === 'delivery'
                    ? ($data['delivery_latitude'] ?? null)
                    : null,
                'delivery_longitude' => $data['service_option'] === 'delivery'
                    ? ($data['delivery_longitude'] ?? null)
                    : null,
                'pickup_date' => $data['start_date'],
                'pickup_time' => $data['pickup_time'],
                'return_date' => $data['end_date'],
                'return_time' => $data['return_time'],
                'document_paths' => [],
                'privacy_consent' => true,
                'payment_mode' => $paymentMode,
                'payment_reference_id' => $data['gcash_reference'] ?? null,
                'payment_status' => $paidAmount > 0 ? 'pending' : 'unpaid',
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'control_number' => $this->generateControlNumber(),
                // Walk-ins skip document review but still follow the normal lifecycle.
                'status' => 'verified',
            ]);

            $schedule = collect($vehicle->schedule ?? [])
                ->reject(fn (array $entry) => ($entry['reservationId'] ?? null) === $reservation->id)
                ->push([
                    'id' => $reservation->id,
                    'vehicleId' => $vehicle->id,
                    'reservationId' => $reservation->id,
                    'type' => 'Reserved',
                    'start' => $reservation->pickup_date->toDateString(),
                    'end' => $reservation->return_date->toDateString(),
                    'notes' => 'Walk-in booking '.$reservation->control_number,
                ])
                ->values()
                ->all();

            $vehicle->update(['status' => 'rented', 'schedule' => $schedule]);
            AuditLog::record('reservation.created', $reservation, ['source' => 'admin_walk_in']);

            return response()->json([
                'message' => 'Walk-in booking saved successfully.',
                'reservation_id' => $reservation->id,
                'control_number' => $reservation->control_number,
                'status' => $reservation->status,
                'vehicle_id' => $vehicle->id,
                'vehicle_plate' => $vehicle->plate,
            ], 201);
        });
    }

    public function updateReservationStatus(Request $request, Reservation $reservation): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::RESERVATION_STATUSES)],
            'rejection_comment' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf($reservation->status !== 'cancelled' && $request->input('status') === 'cancelled'),
            ],
            'assigned_driver_name' => ['nullable', 'string', 'max:255'],
            'assigned_driver_contact' => ['nullable', 'string', 'max:30'],
            'verify_documents' => ['sometimes', 'boolean'],
            'return_condition_checks' => ['nullable', 'array', 'min:1'],
            'return_condition_checks.*' => ['string', 'max:100'],
            'return_condition_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (filled($data['assigned_driver_name'] ?? null)) {
            $driver = OnCallDriver::where('name', $data['assigned_driver_name'])->first();
            abort_unless(
                ! $driver || $driver->available_this_week,
                422,
                $data['assigned_driver_name'].' is marked Not Available this week.'
            );
            $driverHasOtherBooking = Reservation::query()
                ->where('assigned_driver_name', $data['assigned_driver_name'])
                ->where('id', '<>', $reservation->id)
                ->whereIn('status', ['verified', 'processing', 'released'])
                ->exists();
            abort_unless(
                ! $driverHasOtherBooking,
                422,
                $data['assigned_driver_name'].' is already Reserved or Ongoing for another booking.'
            );
        }

        if ($reservation->status === 'cancelled' && $data['status'] !== 'cancelled') {
            abort(422, 'This booking was already cancelled and cannot be confirmed.');
        }
        if ($reservation->status === 'completed' && $data['status'] !== 'completed') {
            abort(422, 'Completed reservations cannot be processed or changed.');
        }
        $isUpdatedDocumentReview = $data['status'] === $reservation->status
            && ($data['verify_documents'] ?? false)
            && $this->documentsNeedReview($reservation);
        if (
            in_array($reservation->status, ['verified', 'processing', 'released'], true)
            && (($data['verify_documents'] ?? false) || $data['status'] === 'verified')
            && ! $isUpdatedDocumentReview
        ) {
            abort(422, 'Customer documents have already been confirmed and cannot be confirmed again.');
        }
        if (
            in_array($data['status'], ['processing', 'released'], true)
            && $this->userDocumentsNeedReview($reservation->user)
            && ! $isUpdatedDocumentReview
        ) {
            abort(422, 'Updated customer documents must be reviewed before the rental can continue.');
        }
        if (
            $data['status'] === 'completed'
            && $this->userDocumentsNeedReview($reservation->user)
            && ! $isUpdatedDocumentReview
        ) {
            abort(422, 'Updated customer documents must be reviewed before the rental can be returned.');
        }
        if ($data['status'] === 'completed' && ! $isUpdatedDocumentReview) {
            abort_unless(
                $reservation->status === 'released',
                422,
                'Only released reservations can be returned.'
            );
            abort_unless(
                ! empty($data['return_condition_checks']),
                422,
                'Complete the vehicle return condition check before returning this reservation.'
            );
        }

        $inspectorName = $request->user()?->name
            ?: (string) $request->session()->get('admin_name', 'Admin');

        DB::transaction(function () use ($reservation, $data, $inspectorName): void {
            $reservation->updateOrFail([
                'status' => $data['status'],
                'assigned_driver_name' => $data['assigned_driver_name'] ?? $reservation->assigned_driver_name,
                'assigned_driver_contact' => $data['assigned_driver_contact'] ?? $reservation->assigned_driver_contact,
                'rejection_comment' => $data['status'] === 'cancelled'
                    ? ($data['rejection_comment'] ?? $reservation->rejection_comment)
                    : null,
                'released_at' => $data['status'] === 'released'
                    ? ($reservation->released_at ?? now())
                    : $reservation->released_at,
                'return_condition_checks' => $data['return_condition_checks'] ?? $reservation->return_condition_checks,
                'return_condition_notes' => $data['return_condition_notes'] ?? $reservation->return_condition_notes,
                'return_condition_checked_at' => $data['status'] === 'completed'
                    ? now()
                    : $reservation->return_condition_checked_at,
                'return_condition_checked_by' => $data['status'] === 'completed'
                    ? $inspectorName
                    : $reservation->return_condition_checked_by,
                'return_condition_checked_by_role' => $data['status'] === 'completed'
                    ? 'Admin'
                    : $reservation->return_condition_checked_by_role,
            ]);
            if ($data['status'] === 'verified' || ($data['verify_documents'] ?? false)) {
                $user = $reservation->user;
                if ($user) {
                    $user->update([
                        'documents_verified_at' => now(),
                        'documents_rejected_at' => null,
                    ]);
                }
            } elseif ($data['status'] === 'cancelled') {
                $reservation->user?->update(['documents_rejected_at' => now()]);
            }
            $this->syncVehicleStatus($reservation, $data['status']);
            $this->syncReservationSchedule($reservation, $data['status']);
            AuditLog::record('reservation.status_updated', $reservation, ['status' => $data['status']]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'reservation' => $reservation->fresh(),
                'message' => 'Reservation status updated.',
            ]);
        }

        return back()->with('success', 'Reservation '.$reservation->control_number.' is now '.$data['status'].'.');
    }

    private function uploadedFileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $path = ltrim($path, '/');

        return Storage::disk('public')->exists($path)
            ? asset('storage/'.$path)
            : null;
    }

    private function documentsNeedReview(Reservation $reservation): bool
    {
        $user = $reservation->user;

        return in_array($reservation->status, ['pending', 'verified', 'processing', 'released', 'completed', 'cancelled', 'void'], true)
            && $this->userDocumentsNeedReview($user)
            && $reservation->documents_updated_at !== null
            && $reservation->documents_updated_at->greaterThan($user->documents_verified_at);
    }

    private function userDocumentsNeedReview(?User $user): bool
    {
        return $user?->documents_verified_at !== null
            && $user->documents_rejected_at !== null
            && $user->documents_rejected_at->greaterThan($user->documents_verified_at);
    }

    public function destroyReservation(Request $request, Reservation $reservation): RedirectResponse|JsonResponse
    {
        $controlNumber = $reservation->control_number;

        foreach ($reservation->document_paths ?? [] as $path) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
        }

        if ($reservation->payment_proof_path) {
            Storage::disk('public')->delete($reservation->payment_proof_path);
        }

        $vehicle = $reservation->vehicleUnit;
        if ($vehicle) {
            $hasActiveReservation = $vehicle->reservations()
                ->where('id', '<>', $reservation->id)
                ->whereNotIn('status', ['cancelled', 'void', 'completed'])
                ->exists();
            $vehicle->update([
                'status' => $vehicle->status === 'maintenance'
                    ? 'maintenance'
                    : ($hasActiveReservation ? 'rented' : 'available'),
                'schedule' => collect($vehicle->schedule ?? [])
                    ->reject(fn (array $entry): bool => ($entry['reservationId'] ?? null) === $reservation->id)
                    ->values()
                    ->all(),
            ]);
        }

        PickupConditionReport::where('reservation_id', $reservation->id)->get()->each(function (PickupConditionReport $report) {
            if ($report->photo_path) {
                Storage::disk('public')->delete($report->photo_path);
            }
            $report->delete();
        });

        AuditLog::record('reservation.deleted', $reservation, ['control_number' => $controlNumber]);
        $reservation->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Reservation '.$controlNumber.' was deleted.']);
        }

        return back()->with('success', 'Reservation '.$controlNumber.' was deleted.');
    }

    public function users(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $users = User::withCount('reservations')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', compact('users', 'search'));
    }

    public function user(User $user): View
    {
        return view('admin.user', [
            'account' => $user,
            'reservations' => $user->reservations()->latest()->paginate(20),
        ]);
    }

    public function billing(Request $request): View
    {
        $paymentStatus = $request->query('payment_status');
        $query = Reservation::with('user')
            ->where(function ($query) {
                $query->whereIn('status', ['verified', 'processing', 'released', 'completed'])
                    ->orWhereNotNull('payment_proof_path')
                    ->orWhereNotNull('payment_reference_id')
                    ->orWhereIn('payment_status', self::PAYMENT_STATUSES);
            })
            ->latest();

        if (in_array($paymentStatus, self::PAYMENT_STATUSES, true)) {
            $query->where('payment_status', $paymentStatus);
        }

        $reservations = $query->with('extensions')->paginate(20)->withQueryString();

        return view('admin.billing', [
            'reservations' => $reservations,
            'invoiceRows' => $reservations->getCollection()->map(function ($reservation) {
                $vehicle = $reservation->vehicleUnit;
                $days = $reservation->rentalDays();
                $total = (float) $reservation->total_amount;
                if ($total <= 0) {
                    $total = ($vehicle?->price ?? 0) * $days
                        + ($reservation->driver_option === 'with_driver' ? 1500 * $days : 0);
                }

                return [
                    'reservationId' => $reservation->id,
                    'invoiceType' => 'booking',
                    'id' => $reservation->control_number,
                    'source' => $reservation->booking_source === 'admin_staff' ? 'walkin' : 'online',
                    'name' => $reservation->customer_name ?: ($reservation->user?->name ?? 'Walk-in Customer'),
                    'phone' => $reservation->customer_phone ?: ($reservation->user?->contact_number ?? 'No contact'),
                    'vehicle' => $reservation->vehicle,
                    'vehiclePlate' => $vehicle?->plate,
                    'total' => $total,
                    'paid' => (float) $reservation->paid_amount,
                    'submittedAmount' => (float) $reservation->payment_submitted_amount,
                    'verificationPending' => $reservation->payment_status === 'pending'
                        && ((float) $reservation->payment_submitted_amount > 0
                            || ((float) $reservation->paid_amount > 0 && ($reservation->payment_proof_path || $reservation->payment_reference_id))),
                    'ref' => $reservation->payment_reference_id ?: 'No reference',
                    'proof' => $this->uploadedFileUrl($reservation->payment_proof_path),
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
                        'proof' => $this->uploadedFileUrl($extension->payment_proof_path),
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
                    'method' => ucfirst($reservation->payment_mode),
                    'status' => (float) $reservation->paid_amount >= $total ? 'approved' : $reservation->payment_status,
                    'paymentActor' => AuditLog::with('actor')
                        ->where('entity_type', Reservation::class)
                        ->where('entity_id', $reservation->id)
                        ->whereIn('action', ['reservation.payment_added', 'reservation.payment_status_updated'])
                        ->latest()
                        ->get()
                        ->map(fn ($log) => [
                            'name' => $log->actor?->name ?: 'System',
                            'role' => $log->actor?->role === 'admin' ? 'Admin' : ($log->actor?->role === 'staff' ? 'Staff' : 'System'),
                            'action' => $log->action === 'reservation.payment_added' ? 'Payment added' : 'Payment status updated',
                            'amount' => $log->metadata['amount'] ?? null,
                            'date' => $log->created_at?->format('M j, Y g:i A'),
                        ])->values()->all(),
                ];
            })->values()->all(),
            'paymentStatus' => $paymentStatus,
            'paymentStatuses' => self::PAYMENT_STATUSES,
            'paymentSettings' => PaymentSetting::current(),
        ]);
    }

    public function updatePaymentStatus(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate(['payment_status' => ['required', 'in:'.implode(',', self::PAYMENT_STATUSES)]]);
        $reservation->update(['payment_status' => $data['payment_status']]);
        AuditLog::record('reservation.payment_status_updated', $reservation, ['payment_status' => $data['payment_status']]);

        return back()->with('success', 'Payment for '.$reservation->control_number.' was marked '.$data['payment_status'].'.');
    }

    public function confirmBookingPayment(Reservation $reservation): JsonResponse
    {
        $result = DB::transaction(function () use ($reservation): array {
            $booking = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            abort_if(in_array($booking->status, ['cancelled', 'void'], true), 422, 'Cancelled bookings cannot receive payments.');
            abort_unless($booking->payment_status === 'pending', 422, 'This booking payment is not awaiting verification.');

            $submittedAmount = (float) $booking->payment_submitted_amount;
            $initialPaymentAwaitingVerification = (float) $booking->paid_amount > 0
                && ($booking->payment_proof_path || $booking->payment_reference_id);
            abort_if(
                $submittedAmount <= 0 && ! $initialPaymentAwaitingVerification,
                422,
                'There is no submitted booking payment to confirm.'
            );

            $amountToConfirm = $submittedAmount > 0 ? $submittedAmount : (float) $booking->paid_amount;
            $totalAmount = (float) $booking->total_amount;
            if ($totalAmount <= 0) {
                $days = $booking->rentalDays();
                $totalAmount = (float) ($booking->vehicleUnit?->price ?? 0) * $days
                    + ($booking->driver_option === 'with_driver' ? 1500 * $days : 0);
            }
            $paidAmount = (float) $booking->paid_amount;
            if ($submittedAmount > 0) {
                abort_if($amountToConfirm > max($totalAmount - $paidAmount, 0), 422, 'The submitted amount exceeds the booking balance.');
                $paidAmount += $amountToConfirm;
            }

            $booking->update([
                'paid_amount' => $paidAmount,
                'total_amount' => $totalAmount,
                'payment_submitted_amount' => null,
                'payment_status' => 'approved',
            ]);

            return [
                'amount' => $amountToConfirm,
                'paid_amount' => $paidAmount,
                'balance' => max($totalAmount - $paidAmount, 0),
                'payment_status' => $booking->payment_status,
            ];
        });

        $auditLog = AuditLog::record('reservation.payment_added', $reservation, [
            'amount' => $result['amount'],
            'paid_amount' => $result['paid_amount'],
            'balance' => $result['balance'],
            'payment_status' => $result['payment_status'],
            'source' => 'user_payment_verification',
        ])->load('actor');

        return response()->json($result + [
            'confirmed_by' => $auditLog->actor?->name ?: 'System',
            'confirmed_role' => $auditLog->actor?->role === 'admin' ? 'Admin' : ($auditLog->actor?->role === 'staff' ? 'Staff' : 'System'),
            'confirmed_at' => $auditLog->created_at?->format('M j, Y g:i A'),
        ]);
    }

    public function addPayment(Request $request, Reservation $reservation): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0']]);
        $totalAmount = (float) $reservation->total_amount;
        if ($totalAmount <= 0) {
            $vehicle = $reservation->vehicleUnit;
            $days = $reservation->rentalDays();
            $totalAmount = ($vehicle?->price ?? 0) * $days
                + ($reservation->driver_option === 'with_driver' ? 1500 * $days : 0);
        }
        $paid = min($totalAmount, (float) $reservation->paid_amount + (float) $data['amount']);
        $reservation->update([
            'total_amount' => $totalAmount,
            'paid_amount' => $paid,
            'payment_status' => $paid >= $totalAmount ? 'approved' : 'pending',
        ]);
        AuditLog::record('reservation.payment_added', $reservation, ['amount' => $data['amount'], 'paid_amount' => $paid]);

        if ($request->expectsJson()) {
            return response()->json([
                'paid_amount' => $paid,
                'total_amount' => $totalAmount,
                'balance' => max($totalAmount - $paid, 0),
                'payment_status' => $reservation->payment_status,
            ]);
        }

        return back()->with('success', 'Payment added. Remaining balance: ₱'.number_format(max((float) $reservation->total_amount - $paid, 2), 2).'.');
    }

    public function confirmExtensionPayment(Request $request, Reservation $reservation, RentalExtension $extension): JsonResponse
    {
        $result = DB::transaction(function () use ($reservation, $extension): array {
            $lockedExtension = $reservation->extensions()
                ->whereKey($extension->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedExtension->status === 'payment_pending', 422, 'This extension payment is not awaiting verification.');
            $submittedAmount = (float) $lockedExtension->payment_submitted_amount;
            abort_if($submittedAmount <= 0, 422, 'The submitted extension payment amount is missing.');

            $paidAmount = (float) $lockedExtension->paid_amount;
            $totalAmount = (float) $lockedExtension->amount;
            $balance = max($totalAmount - $paidAmount, 0);
            abort_if($submittedAmount > $balance, 422, 'The submitted amount exceeds the extension balance.');

            $paidAmount += $submittedAmount;
            $newBalance = max($totalAmount - $paidAmount, 0);
            $lockedExtension->update([
                'paid_amount' => $paidAmount,
                'status' => $newBalance <= 0 ? 'paid' : 'approved',
            ]);

            return [
                'paid_amount' => $paidAmount,
                'balance' => $newBalance,
                'status' => $lockedExtension->status,
            ];
        });

        AuditLog::record('rental_extension.payment_confirmed', $extension, [
            'amount' => (float) $extension->payment_submitted_amount,
            'paid_amount' => $result['paid_amount'],
            'balance' => $result['balance'],
            'status' => $result['status'],
        ]);

        return response()->json($result);
    }

    public function activeRentals(Request $request): View
    {
        $status = $request->query('status');
        $query = Reservation::with('user')
            ->whereIn('status', ['processing', 'released'])
            ->latest('pickup_date');

        if (in_array($status, ['processing', 'released'], true)) {
            $query->where('status', $status);
        }

        $reservations = $query->paginate(20)->withQueryString();
        $vehiclePlates = Vehicle::whereIn('id', $reservations->getCollection()->pluck('vehicle_id')->filter()->unique())
            ->pluck('plate', 'id');

        return view('admin.active-rentals', [
            'reservations' => $reservations,
            'vehiclePlates' => $vehiclePlates,
            'status' => $status,
            'processingCount' => Reservation::where('status', 'processing')->count(),
            'releasedCount' => Reservation::where('status', 'released')->count(),
            'todayCount' => Reservation::whereIn('status', ['processing', 'released'])
                ->whereDate('return_date', now()->toDateString())->count(),
            'overdueCount' => Reservation::whereIn('status', ['processing', 'released'])
                ->whereDate('return_date', '<', now()->toDateString())->count(),
        ]);
    }

    public function updateActiveRentalStatus(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:processing,released,completed,cancelled']]);

        if ($reservation->status === 'cancelled' && $data['status'] !== 'cancelled') {
            abort(422, 'This booking was already cancelled and cannot be confirmed.');
        }
        if ($reservation->status === 'completed' && $data['status'] !== 'completed') {
            abort(422, 'Completed rentals cannot be processed or changed.');
        }

        $reservation->update([
            'status' => $data['status'],
            'released_at' => $data['status'] === 'released'
                ? ($reservation->released_at ?? now())
                : $reservation->released_at,
        ]);
        $this->syncVehicleStatus($reservation, $data['status']);
        AuditLog::record('reservation.active_status_updated', $reservation, ['status' => $data['status']]);

        return back()->with('success', 'Rental '.$reservation->control_number.' is now '.$data['status'].'.');
    }

    public function extendActiveRental(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->booking_source === 'admin_staff', 422, 'Only walk-in rentals can be extended here.');
        abort_unless(in_array($reservation->status, ['processing', 'released'], true), 422, 'Only active rentals can be extended.');

        $data = $request->validate([
            'requested_return_date' => ['required', 'date', 'after:'.$reservation->return_date->toDateString()],
        ]);
        $newDate = Carbon::parse($data['requested_return_date']);
        $vehicle = $reservation->vehicleUnit;
        abort_if(
            $vehicle && $vehicle->hasAvailabilityConflict(
                $reservation->pickup_date->toDateString(),
                $newDate->toDateString(),
                $reservation->id
            ),
            422,
            'The requested extension conflicts with another reservation or maintenance schedule for this unit.'
        );
        $extensionDays = max(1, $reservation->return_date->diffInDays($newDate));
        $dailyAmount = (float) ($vehicle?->price ?? 0)
            + ($reservation->driver_option === 'with_driver' ? 1500 : 0);
        $extensionAmount = $dailyAmount * $extensionDays;

        RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => $extensionDays,
            'amount' => $extensionAmount,
            'requested_return_date' => $newDate,
            'status' => 'approved',
        ]);

        $reservation->update([
            'return_date' => $newDate,
        ]);

        if ($vehicle) {
            $schedule = collect($vehicle->schedule ?? [])->map(function (array $entry) use ($reservation, $newDate) {
                if (($entry['reservationId'] ?? null) === $reservation->id) {
                    $entry['end'] = $newDate->toDateString();
                }

                return $entry;
            })->values()->all();
            $vehicle->update(['schedule' => $schedule]);
        }

        AuditLog::record('reservation.rental_extended', $reservation, [
            'days' => $extensionDays,
            'amount' => $extensionAmount,
            'return_date' => $newDate->toDateString(),
            'source' => 'walk-in',
        ]);

        return back()->with('success', 'Walk-in rental extended to '.$newDate->format('F j, Y').'. Additional charge: ₱'.number_format($extensionAmount, 2).'.');
    }

    private function syncVehicleStatus(Reservation $reservation, string $reservationStatus): void
    {
        $vehicle = $reservation->vehicleUnit;
        if (! $vehicle || $vehicle->status === 'maintenance') {
            return;
        }

        $hasActiveReservation = Reservation::where('vehicle_id', $vehicle->id)
            ->where('id', '<>', $reservation->id)
            ->whereNotIn('status', ['cancelled', 'void', 'completed'])
            ->exists();

        $vehicle->update([
            'status' => $hasActiveReservation || ! in_array($reservationStatus, ['completed', 'cancelled'], true)
                ? 'rented'
                : 'available',
        ]);
    }

    private function syncReservationSchedule(Reservation $reservation, string $reservationStatus): void
    {
        $vehicle = $reservation->vehicleUnit;
        if (! $vehicle) {
            return;
        }

        $schedule = collect($vehicle->schedule ?? [])
            ->reject(fn (array $entry): bool => (int) ($entry['reservationId'] ?? 0) === (int) $reservation->id);

        if (! in_array($reservationStatus, ['cancelled', 'completed'], true)) {
            $schedule->push([
                'id' => $reservation->id,
                'vehicleId' => $vehicle->id,
                'reservationId' => $reservation->id,
                'type' => $reservationStatus === 'released' ? 'Rented' : 'Reserved',
                'start' => $reservation->pickup_date->toDateString(),
                'end' => $reservation->return_date->toDateString(),
                'notes' => 'Reservation '.$reservation->control_number,
            ]);
        }

        $vehicle->update(['schedule' => $schedule->values()->all()]);
    }

    public function vehicles(): View
    {
        app(UserReservationController::class)->synchronizeVehicleStatuses();
        $vehicles = Vehicle::withCount('reservations')->orderBy('name')->orderBy('id')->get();
        $activeReservations = Reservation::whereNotIn('status', ['cancelled', 'void', 'completed'])
            ->whereNotNull('pickup_date')
            ->whereNotNull('return_date')
            ->latest('created_at')
            ->get()
            ->unique('vehicle_id')
            ->keyBy('vehicle_id');
        $calendarReservations = Reservation::whereNotIn('status', ['cancelled', 'void', 'completed'])
            ->get()
            ->keyBy('id');
        $conditionReports = PickupConditionReport::with(['user', 'reservation'])
            ->latest()
            ->get();
        $returnConditionReports = Reservation::with('user')
            ->whereNotNull('return_condition_checked_at')
            ->latest('return_condition_checked_at')
            ->get();

        return view('admin.vehicles', [
            'priceGuides' => PriceGuide::current(),
            'conditionReports' => $conditionReports,
            'returnConditionReports' => $returnConditionReports,
            'vehicles' => $vehicles,
            'driverCatalog' => $this->driverCatalog(),
            'vehicleRows' => $vehicles->map(function ($vehicle) use ($calendarReservations, $activeReservations) {
                $schedule = collect($vehicle->schedule ?? [])
                    ->filter(fn (array $entry): bool => ($entry['type'] ?? null) === 'Maintenance'
                    || isset($entry['reservationId'], $calendarReservations[$entry['reservationId']]))
                    ->map(function (array $entry) use ($calendarReservations): array {
                        if (($entry['type'] ?? null) === 'Maintenance'
                            || ! isset($entry['reservationId'], $calendarReservations[$entry['reservationId']])) {
                            return $entry;
                        }
                        $reservation = $calendarReservations[$entry['reservationId']];
                        $isUpcomingDelivery = $reservation->service_option === 'delivery'
                            && $reservation->status !== 'released';
                        $entry['type'] = $isUpcomingDelivery
                            ? 'Special'
                            : ($reservation->status === 'released' ? 'Rented' : 'Reserved');
                        $entry['start'] = $reservation->pickup_date->toDateString();
                        $entry['end'] = $reservation->return_date->toDateString();
                        if ($isUpcomingDelivery) {
                            $entry['controlNumber'] = $reservation->control_number;
                            $entry['deliveryDate'] = $reservation->pickup_date->format('F j, Y');
                        }

                        return $entry;
                    })
                    ->values()
                    ->all();

                return [
                    'id' => $vehicle->id,
                    'name' => $vehicle->name,
                    'brand_name' => $vehicle->brand_name ?? '',
                    'plate' => $vehicle->plate,
                    'price' => (float) $vehicle->price,
                    'category' => $vehicle->category ?? '',
                    'transmission' => $vehicle->transmission ?? '',
                    'fuel' => $vehicle->fuel ?? '',
                    'capacity' => (string) ($vehicle->capacity ?? ''),
                    'capacity_type' => $vehicle->capacity_type ?: (($vehicle->capacity ?? null) ? $vehicle->capacity.' Seater' : ''),
                    'status' => ucfirst($vehicle->status) === 'Unavailable' ? 'Maintenance' : ucfirst($vehicle->status),
                    'photo' => $vehicle->image_path ? asset('storage/'.$vehicle->image_path) : asset('image/car'.(($vehicle->id - 1) % 4 + 1).'.png'),
                    'imagePath' => $vehicle->image_path,
                    'rating' => (float) $vehicle->rating,
                    'timesRented' => $vehicle->reservations_count,
                    'feedbacks' => $vehicle->feedbacks ?? [],
                    'damageLog' => $vehicle->damage_log ?? [],
                    'schedule' => $schedule,
                    'currentReservation' => $activeReservations->has($vehicle->id) ? [
                        'pickup_date' => $activeReservations[$vehicle->id]->pickup_date->toDateString(),
                        'return_date' => $activeReservations[$vehicle->id]->return_date->toDateString(),
                        'status' => $activeReservations[$vehicle->id]->status,
                    ] : null,
                ];
            })->values()->all(),
        ]);
    }

    public function drivers(): View
    {
        $reservations = Reservation::with('user')
            ->where('driver_option', 'with_driver')
            ->whereNotNull('assigned_driver_name')
            ->latest('pickup_date')
            ->get();

        return view('admin.drivers', [
            'driverReservations' => $reservations,
            'driverCatalog' => $this->driverCatalog(),
            'isStaffMode' => false,
        ]);
    }

    public function updateDriverAvailability(Request $request, OnCallDriver $driver): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'available_this_week' => ['required', 'boolean'],
        ]);

        $driver->update([
            'available_this_week' => $data['available_this_week'],
            'availability_week' => now()->startOfWeek()->toDateString(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['driver' => $driver->fresh(), 'message' => 'Driver availability updated.']);
        }

        return back()->with('success', $driver->name.' is marked '.($driver->available_this_week ? 'Available' : 'Not Available').' this week.');
    }

    private function driverCatalog(): array
    {
        $defaults = [
            ['name' => 'Mark Santos', 'contact' => '0917 000 0001'],
            ['name' => 'Julius Reyes', 'contact' => '0917 000 0002'],
            ['name' => 'Ramon Cruz', 'contact' => '0917 000 0003'],
            ['name' => 'Andres Villanueva', 'contact' => '0917 000 0004'],
        ];

        foreach ($defaults as $default) {
            OnCallDriver::firstOrCreate(['name' => $default['name']], $default);
        }

        $weekStart = now()->startOfWeek()->toDateString();

        $driverAssignments = Reservation::query()
            ->where('driver_option', 'with_driver')
            ->whereNotNull('assigned_driver_name')
            ->whereIn('status', ['verified', 'processing', 'released'])
            ->whereNotNull('pickup_date')
            ->whereNotNull('return_date')
            ->get(['assigned_driver_name', 'pickup_date', 'return_date', 'status', 'id'])
            ->groupBy('assigned_driver_name');

        return OnCallDriver::orderBy('name')->get()->map(function (OnCallDriver $driver) use ($weekStart, $driverAssignments): array {
            if ($driver->availability_week?->toDateString() !== $weekStart) {
                $driver->update([
                    'available_this_week' => true,
                    'availability_week' => $weekStart,
                ]);
                $driver->refresh();
            }

            $assignments = $driverAssignments->get($driver->name, collect());
            $ongoing = $assignments->firstWhere('status', 'released');
            $reserved = $assignments->first(fn (Reservation $reservation): bool => in_array($reservation->status, ['verified', 'processing'], true));
            $current = $ongoing ?: $reserved;

            return [
                'id' => $driver->id,
                'name' => $driver->name,
                'contact' => $driver->contact,
                'availableThisWeek' => $driver->available_this_week,
                'availabilityWeek' => $driver->availability_week?->toDateString(),
                'bookingStatus' => $ongoing ? 'ongoing' : ($reserved ? 'reserved' : 'available'),
                'bookingReservationId' => $current?->id,
                'bookings' => $assignments->map(fn (Reservation $reservation): array => [
                    'start' => $reservation->pickup_date->toDateString(),
                    'end' => $reservation->return_date->toDateString(),
                ])->values()->all(),
            ];
        })->all();
    }

    public function updatePriceGuide(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'prices' => ['required', 'array', 'size:3'],
            'prices.*.id' => ['required', 'integer', 'exists:price_guides,id'],
            'prices.*.city_driving' => ['required', 'numeric', 'min:0'],
            'prices.*.province' => ['required', 'numeric', 'min:0'],
            'prices.*.long_distance' => ['required', 'numeric', 'min:0'],
            'prices.*.hourly' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($data['prices'] as $priceData) {
            $guide = PriceGuide::findOrFail($priceData['id']);
            $guide->update([
                'city_driving' => $priceData['city_driving'],
                'province' => $priceData['province'],
                'long_distance' => $priceData['long_distance'],
                'hourly' => $priceData['hourly'],
            ]);
            $categoryAliases = [$guide->category];
            if (str_contains(mb_strtolower($guide->category), 'diesel')) {
                $categoryAliases[] = 'Expanded - Diesel';
            } elseif (str_contains(mb_strtolower($guide->category), 'gas')) {
                $categoryAliases[] = 'Pick-up / Expanded (7-Seater) - Gas';
            }
            Vehicle::whereIn('category', array_unique($categoryAliases))
                ->update(['price' => $guide->city_driving]);
        }

        if ($request->expectsJson()) {
            return response()->json(['priceGuides' => PriceGuide::current()]);
        }

        return back()->with('success', 'Price Guide updated successfully.');
    }

    public function storeVehicle(Request $request): Response
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'plate' => ['required', 'string', 'max:32', 'unique:vehicles,plate'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['nullable', 'string', 'max:255'],
            'transmission' => ['nullable', 'string', 'max:50'],
            'fuel' => ['nullable', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'capacity_type' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:available,maintenance,rented,unavailable'],
            'maintenance_notes' => ['nullable', 'string', 'max:5000'],
            'damage_log' => ['nullable', 'array'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'schedule' => ['nullable', 'array'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $data['price'] = $this->cityDrivingPrice($data['category'] ?? null);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('vehicles', 'public');
        }
        $vehicle = Vehicle::create($data);
        AuditLog::record('vehicle.created', $vehicle);
        if ($request->expectsJson()) {
            return response()->json(['vehicle' => $vehicle], 201);
        }

        return back()->with('success', 'Vehicle created.');
    }

    public function updateVehicle(Request $request, Vehicle $vehicle): RedirectResponse|JsonResponse
    {
        if (strtolower((string) $vehicle->status) === 'rented') {
            $message = 'Reserved or ongoing vehicles cannot be edited.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return back()->with('error', $message);
        }

        foreach (['damage_log', 'schedule'] as $jsonField) {
            if (is_string($request->input($jsonField))) {
                $request->merge([$jsonField => json_decode($request->input($jsonField), true, 512, JSON_THROW_ON_ERROR)]);
            }
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'plate' => ['required', 'string', 'max:32', 'unique:vehicles,plate,'.$vehicle->id],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['nullable', 'string', 'max:255'],
            'transmission' => ['nullable', 'string', 'max:50'],
            'fuel' => ['nullable', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'capacity_type' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:available,maintenance,rented,unavailable'],
            'maintenance_notes' => ['nullable', 'string', 'max:5000'],
            'damage_log' => ['nullable', 'array'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'schedule' => ['nullable', 'array'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $data['price'] = $this->cityDrivingPrice($data['category'] ?? null);
        if (in_array($data['status'], ['available', 'maintenance'], true) && isset($data['schedule'])) {
            $data['schedule'] = collect($data['schedule'])
                ->reject(fn ($entry) => in_array($entry['type'] ?? null, ['Reserved', 'Rented'], true))
                ->values()
                ->all();
        }
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('vehicles', 'public');
            if ($vehicle->image_path) {
                Storage::disk('public')->delete($vehicle->image_path);
            }
        }
        $vehicle->update($data);
        AuditLog::record('vehicle.updated', $vehicle);
        if ($request->expectsJson()) {
            return response()->json(['vehicle' => $vehicle->fresh()]);
        }

        return back()->with('success', 'Vehicle updated.');
    }

    public function destroyVehicle(Vehicle $vehicle): RedirectResponse
    {
        AuditLog::record('vehicle.deleted', $vehicle, ['name' => $vehicle->name, 'plate' => $vehicle->plate]);
        $vehicle->delete();

        return back()->with('success', 'Vehicle removed.');
    }

    public function destroyVehicleFeedbackReply(Vehicle $vehicle, int $feedbackIndex): JsonResponse
    {
        $feedbacks = $vehicle->feedbacks ?? [];
        abort_unless(
            is_array($feedbacks[$feedbackIndex] ?? null)
                && is_array($feedbacks[$feedbackIndex]['adminReply'] ?? null),
            404,
            'Feedback reply not found.'
        );

        $feedbacks[$feedbackIndex]['adminReply'] = null;
        $vehicle->update(['feedbacks' => $feedbacks]);

        return response()->json(['feedbacks' => $vehicle->fresh()->feedbacks ?? []]);
    }

    public function destroyInquiry(ContactInquiry $inquiry): RedirectResponse|JsonResponse
    {
        $inquiry->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Inquiry deleted.']);
        }

        return back()->with('success', 'Inquiry deleted.');
    }

    public function destroyAuditLog(AuditLog $auditLog): RedirectResponse|JsonResponse
    {
        $auditLog->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Audit log deleted.']);
        }

        return back()->with('success', 'Audit log deleted.');
    }

    public function destroyPickupReport(PickupConditionReport $report): JsonResponse
    {
        if ($report->photo_path) {
            Storage::disk('public')->delete($report->photo_path);
        }

        $report->delete();

        return response()->json(['message' => 'Pickup condition report deleted.']);
    }

    public function destroyAllReportRecords(string $type): JsonResponse
    {
        $deleted = 0;

        if ($type === 'cancelled') {
            $cancelledReservations = Reservation::where('status', 'cancelled')->get();
            foreach ($cancelledReservations as $reservation) {
                $this->syncReservationSchedule($reservation, 'cancelled');
            }
            $deleted = Reservation::where('status', 'cancelled')->delete();
        } elseif ($type === 'voided') {
            $voidedReservations = Reservation::where('status', 'void')->get();
            foreach ($voidedReservations as $reservation) {
                $this->syncReservationSchedule($reservation, 'void');
            }
            $deleted = Reservation::where('status', 'void')->delete();
        } elseif ($type === 'audit') {
            $deleted = AuditLog::query()->delete();
        } elseif ($type === 'inquiries') {
            $deleted = ContactInquiry::query()->delete();
        } elseif ($type === 'pickup') {
            $reports = PickupConditionReport::query()->get();
            foreach ($reports as $report) {
                if ($report->photo_path) {
                    Storage::disk('public')->delete($report->photo_path);
                }
            }
            $deleted = PickupConditionReport::query()->delete();
        } else {
            abort(404, 'Unknown report type.');
        }

        return response()->json([
            'message' => $deleted.' report record(s) deleted.',
            'deleted' => $deleted,
        ]);
    }

    public function paymentSettings(): RedirectResponse
    {
        return redirect()->route('admin.billing');
    }

    public function updatePaymentSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:32'],
            'gcash_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);
        $settings = PaymentSetting::current();
        if ($request->hasFile('gcash_qr')) {
            $oldPath = $settings->gcash_qr_path;
            $settings->gcash_qr_path = $request->file('gcash_qr')->store('payment', 'public');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        $settings->account_name = $data['account_name'] ?? null;
        $settings->account_number = $data['account_number'] ?? null;
        $settings->save();
        AuditLog::record('payment_settings.updated', $settings, [
            'account_name_changed' => array_key_exists('account_name', $data),
            'account_number_changed' => array_key_exists('account_number', $data),
            'qr_changed' => $request->hasFile('gcash_qr'),
        ]);

        return back()->with('success', 'Payment settings updated.');
    }

    public function storeUser(Request $request): RedirectResponse|JsonResponse
    {
        $request->merge(['role' => strtolower((string) $request->input('role'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:user,admin'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ]);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        AuditLog::record('user.created', $user, ['role' => $user->role]);
        if ($request->expectsJson()) {
            return response()->json(['user' => $user], 201);
        }

        return back()->with('success', 'User created.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $request->merge(['role' => strtolower((string) $request->input('role'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:user,admin'],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ]);
        if ($data['password'] ?? null) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        AuditLog::record('user.updated', $user, ['role' => $user->role]);
        if ($request->expectsJson()) {
            return response()->json(['user' => $user->fresh()]);
        }

        return back()->with('success', 'User updated.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if ((int) $request->session()->get('admin_user_id') === $user->id) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You cannot delete the signed-in administrator.'], 422);
            }

            return back()->withErrors(['user' => 'You cannot delete the signed-in administrator.']);
        }
        AuditLog::record('user.deleted', $user, ['email' => $user->email]);
        $user->delete();
        if ($request->expectsJson()) {
            return response()->json(['message' => 'User deleted.']);
        }

        return back()->with('success', 'User deleted.');
    }

    public function reports(Request $request): View
    {
        $from = $request->date('from') ?: now()->startOfYear();
        $to = $request->date('to') ?: now()->endOfYear();
        $reservations = Reservation::with('user')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])->get();
        $voidedReservations = Reservation::with('user')
            ->where('status', 'void')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->latest('updated_at')
            ->get();
        $pickupReports = PickupConditionReport::with(['user', 'reservation'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->latest()
            ->get();
        $auditLogs = AuditLog::query()
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->latest()
            ->get();
        $inquiries = ContactInquiry::query()
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->latest()
            ->get();

        return view('admin.reports', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'reservations' => $reservations,
            'total' => $reservations->count(),
            'revenueLikeCount' => $reservations->whereIn('payment_status', ['approved'])->count(),
            'statusCounts' => $reservations->groupBy('status')->map->count(),
            'paymentCounts' => $reservations->groupBy('payment_status')->map->count(),
            'vehicleCounts' => $reservations->groupBy('vehicle')->map->count(),
            'monthlyCounts' => $reservations->groupBy(fn (Reservation $reservation) => $reservation->created_at->format('Y-m'))->map->count(),
            'cancelledCount' => $reservations->where('status', 'cancelled')->count(),
            'voidedReservations' => $voidedReservations,
            'voidedCount' => $voidedReservations->count(),
            'pickupReports' => $pickupReports,
            'pickupCount' => $pickupReports->count(),
            'auditLogs' => $auditLogs,
            'auditCount' => $auditLogs->count(),
            'deletedVehicleCount' => $auditLogs->where('action', 'vehicle.deleted')->count(),
            'inquiries' => $inquiries,
            'inquiriesCount' => $inquiries->count(),
        ]);
    }

    private function generateControlNumber(): string
    {
        do {
            $number = 'BB-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Reservation::where('control_number', $number)->exists());

        return $number;
    }

    private function cityDrivingPrice(?string $category): int
    {
        $normalized = mb_strtolower(trim((string) $category));
        $guide = PriceGuide::current()->first(function (PriceGuide $priceGuide) use ($normalized): bool {
            return mb_strtolower($priceGuide->category) === $normalized;
        });
        if ($guide) {
            return (int) $guide->city_driving;
        }

        if (str_contains($normalized, 'diesel')) {
            return self::CITY_DRIVING_PRICES['expanded — diesel'];
        }

        if (str_contains($normalized, 'pick-up')
            || str_contains($normalized, 'pickup')
            || str_contains($normalized, 'gas')) {
            return self::CITY_DRIVING_PRICES['pick-up / expanded (7-seater) (2 days) — gas'];
        }

        return self::CITY_DRIVING_PRICES['sedan'];
    }
}
