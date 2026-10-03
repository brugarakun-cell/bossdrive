<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Models\PriceGuide;
use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class ReservationController extends Controller
{
    public function synchronizeVehicleStatuses(): void
    {
        $activeReservations = Reservation::whereNotIn('status', ['cancelled', 'void', 'completed'])
            ->get()
            ->groupBy('vehicle_id');
        $activeReservationIds = $activeReservations->flatten()->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        Vehicle::query()->get()->each(function (Vehicle $vehicle) use ($activeReservations, $activeReservationIds): void {
            $schedule = collect($vehicle->schedule ?? [])
                ->reject(function (array $entry) use ($activeReservationIds): bool {
                    $reservationId = $entry['reservationId'] ?? null;

                    return in_array(($entry['type'] ?? null), ['Reserved', 'Rented'], true)
                        && ($reservationId === null
                            || ! in_array((int) $reservationId, $activeReservationIds, true));
                })
                ->values()
                ->all();
            $hasActiveReservation = $activeReservations->has($vehicle->id);
            $nextStatus = $vehicle->status === 'maintenance'
                ? 'maintenance'
                : ($hasActiveReservation ? 'rented' : 'available');

            if ($vehicle->status !== $nextStatus || $schedule !== ($vehicle->schedule ?? [])) {
                $vehicle->update(['status' => $nextStatus, 'schedule' => $schedule]);
            }
        });
    }

    public function expireUnpaidWalkInReservations(): void
    {
        Reservation::query()
            ->where('payment_mode', 'walkin')
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(3))
            ->get()
            ->each(function (Reservation $reservation): void {
                $reservation->update(['status' => 'void']);
                $vehicle = $reservation->vehicleUnit;

                if ($vehicle) {
                    $schedule = collect($vehicle->schedule ?? [])
                        ->reject(fn (array $entry): bool => (int) ($entry['reservationId'] ?? 0) === $reservation->id)
                        ->values()
                        ->all();
                    $hasActiveReservation = $vehicle->reservations()
                        ->whereNotIn('status', ['cancelled', 'void', 'completed'])
                        ->exists();

                    $vehicle->update([
                        'status' => $vehicle->status === 'maintenance'
                            ? 'maintenance'
                            : ($hasActiveReservation ? 'rented' : 'available'),
                        'schedule' => $schedule,
                    ]);
                }
            });
    }

    public function create(Request $request): View
    {
        $this->expireUnpaidWalkInReservations();
        $this->synchronizeVehicleStatuses();
        $user = $request->user();
        $documentsApproved = $user->documents_verified_at
            && (! $user->documents_rejected_at || $user->documents_rejected_at <= $user->documents_verified_at);
        $vehicles = Vehicle::withCount('reservations')->orderBy('name')->orderBy('id')->get();
        $existingDocuments = $documentsApproved ? $user->reservations()
            ->whereIn('status', ['verified', 'processing', 'released', 'completed'])
            ->whereNotNull('document_paths')
            ->latest('created_at')
            ->value('document_paths') ?? [] : [];
        $hasExistingDocuments = collect(['driver_license', 'valid_id', 'proof_of_billing'])
            ->every(fn (string $key): bool => filled($existingDocuments[$key] ?? null));
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

        return view('user.reservation', [
            'priceGuides' => PriceGuide::current(),
            'vehicles' => $vehicles->map(fn (Vehicle $vehicle) => [
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
                'image_path' => $vehicle->image_path,
                'photo' => $vehicle->image_path ? asset('storage/'.$vehicle->image_path) : asset('image/car'.(($vehicle->id - 1) % 4 + 1).'.png'),
                'rating' => (float) $vehicle->rating,
                'timesRented' => $vehicle->reservations_count,
                'feedbacks' => $vehicle->feedbacks ?? [],
                'damageLog' => $vehicle->damage_log ?? [],
                'schedule' => collect($vehicle->schedule ?? [])
                    ->filter(fn (array $entry): bool => ($entry['type'] ?? null) === 'Maintenance'
                        || isset($entry['reservationId'], $calendarReservations[$entry['reservationId']]))
                    ->map(function (array $entry) use ($calendarReservations): array {
                        if (($entry['type'] ?? null) === 'Maintenance'
                            || ! isset($entry['reservationId'], $calendarReservations[$entry['reservationId']])) {
                            return $entry;
                        }
                        $reservation = $calendarReservations[$entry['reservationId']];
                        $entry['type'] = $reservation->status === 'released' ? 'Rented' : 'Reserved';
                        $entry['start'] = $reservation->pickup_date->toDateString();
                        $entry['end'] = $reservation->return_date->toDateString();

                        return $entry;
                    })
                    ->values()
                    ->all(),
                'currentReservation' => isset($activeReservations[$vehicle->id]) ? [
                    'pickup_date' => $activeReservations[$vehicle->id]->pickup_date->toDateString(),
                    'return_date' => $activeReservations[$vehicle->id]->return_date->toDateString(),
                    'status' => $activeReservations[$vehicle->id]->status,
                ] : null,
            ])->values()->all(),
            'paymentSettings' => PaymentSetting::current(),
            'hasExistingDocuments' => $hasExistingDocuments,
            'selectedVehicleId' => $request->integer('vehicle_id') ?: null,
        ]);
    }

    public function feedback(Request $request, Vehicle $vehicle): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $feedbacks = $vehicle->feedbacks ?? [];
        $feedbacks[] = [
            'name' => $request->user()->name,
            'date' => now()->format('M j, Y'),
            'stars' => $data['rating'],
            'comment' => $data['comment'],
            'adminReply' => null,
        ];
        $vehicle->update([
            'feedbacks' => $feedbacks,
            'rating' => collect($feedbacks)->avg(fn (array $feedback) => (int) ($feedback['stars'] ?? 0)),
        ]);

        return response()->json([
            'feedbacks' => $vehicle->fresh()->feedbacks ?? [],
            'rating' => (float) $vehicle->fresh()->rating,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $request->merge([
            'gcash_reference' => preg_replace('/\s+/', '', (string) $request->input('gcash_reference')),
        ]);

        $data = $request->validate([
            'vehicle' => ['required', 'string'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'rate_type' => ['required', 'string', 'in:city,province,long_distance'],
            'service_option' => ['required', 'string', 'in:pickup,delivery'],
            'driver_option' => ['required', 'string', 'in:self_drive,with_driver'],
            'delivery_province' => ['required_if:service_option,delivery', 'nullable', 'string', 'max:255'],
            'delivery_city' => ['required_if:service_option,delivery', 'nullable', 'string', 'max:255'],
            'delivery_barangay' => ['required_if:service_option,delivery', 'nullable', 'string', 'max:255'],
            'delivery_street' => ['required_if:service_option,delivery', 'nullable', 'string', 'max:1000'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'return_time' => ['required', 'date_format:H:i'],
            'valid_id' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'driver_license' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'proof_of_billing' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'privacy_consent' => ['accepted'],
            'final_agreement' => ['accepted'],
            'payment_mode' => ['required', 'string', 'in:full,deposit,walkin'],
            'gcash_screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'gcash_reference' => ['nullable', 'string', 'digits_between:13,17'],
        ]);

        $vehicle = Vehicle::lockForUpdate()->findOrFail($data['vehicle_id']);
        if ($vehicle->name !== $data['vehicle']
            || in_array(strtolower((string) $vehicle->status), ['maintenance', 'unavailable'], true)) {
            return back()->withErrors(['vehicle' => 'This vehicle unit is no longer available.'])->withInput();
        }
        if ($vehicle->hasAvailabilityConflict($data['pickup_date'], $data['return_date'])) {
            return back()->withErrors(['vehicle' => 'This vehicle unit is already reserved for the selected dates.'])->withInput();
        }

        $pickupDateTime = Carbon::parse($data['pickup_date'].' '.$data['pickup_time']);
        $returnDateTime = Carbon::parse($data['return_date'].' '.$data['return_time']);
        if ($returnDateTime->lessThanOrEqualTo($pickupDateTime)) {
            return back()->withErrors(['return_time' => 'Return date and time must be later than pickup date and time.'])->withInput();
        }

        $rentalDurationSeconds = $returnDateTime->getTimestamp() - $pickupDateTime->getTimestamp();
        $rentalDays = max(1, (int) ceil($rentalDurationSeconds / 86400));
        $maximumRentalDays = match ($data['rate_type']) {
            'province' => 14,
            'long_distance' => 30,
            default => 7,
        };
        if ($data['rate_type'] === 'long_distance' && $rentalDurationSeconds < 2 * 86400) {
            return back()->withErrors(['return_date' => 'Long Distance bookings require a minimum rental period of 2 full days.'])->withInput();
        }
        if ($rentalDays > $maximumRentalDays) {
            return back()
                ->withErrors(['return_date' => sprintf(
                    '%s bookings can only be rented for up to %d days.',
                    match ($data['rate_type']) {
                        'province' => 'Province',
                        'long_distance' => 'Long Distance',
                        default => 'City Driving',
                    },
                    $maximumRentalDays
                )])
                ->withInput();
        }

        if ($data['payment_mode'] !== 'walkin'
            && ! $request->hasFile('gcash_screenshot')
            && blank($data['gcash_reference'] ?? null)) {
            return back()
                ->withErrors(['gcash_payment' => 'Upload a GCash payment screenshot or enter the GCash reference number.'])
                ->withInput();
        }

        $documentsApproved = $user->documents_verified_at
            && (! $user->documents_rejected_at || $user->documents_rejected_at <= $user->documents_verified_at);
        $previousDocuments = $documentsApproved ? $user->reservations()
            ->whereIn('status', ['verified', 'processing', 'released', 'completed'])
            ->whereNotNull('document_paths')
            ->latest('created_at')
            ->value('document_paths') ?? [] : [];
        $documentPaths = [];
        foreach (['valid_id', 'driver_license', 'proof_of_billing'] as $documentKey) {
            if ($request->hasFile($documentKey)) {
                $documentPaths[$documentKey] = $this->storeUploadedDocument($request->file($documentKey), $documentKey);
            } elseif ($this->documentPathIsAvailable($previousDocuments[$documentKey] ?? null)) {
                $documentPaths[$documentKey] = $previousDocuments[$documentKey];
            } else {
                return back()->withErrors([$documentKey => 'This document is missing or unavailable. Please upload it again before continuing.'])->withInput();
            }
        }
        $paymentProofPath = null;
        if ($request->hasFile('gcash_screenshot')) {
            $paymentProofPath = $request->file('gcash_screenshot')->store('reservations/payments', 'public');
            if (! is_string($paymentProofPath) || ! Storage::disk('public')->exists($paymentProofPath)) {
                report(new RuntimeException('Unable to store reservation payment screenshot.'));

                throw ValidationException::withMessages([
                    'gcash_screenshot' => 'The payment screenshot could not be saved. Please try again or contact support.',
                ]);
            }
        }
        $paymentReference = filled($data['gcash_reference'] ?? null)
            ? preg_replace('/\s+/', '', $data['gcash_reference'])
            : null;
        $days = max(1, $rentalDays);
        $rate = match ($data['rate_type']) {
            'province' => (float) $vehicle->price + (str_contains(strtolower((string) $vehicle->category), 'diesel') ? 500 : 1000),
            'long_distance' => (float) $vehicle->price + 1500,
            default => (float) $vehicle->price,
        };
        if ($data['rate_type'] === 'long_distance') {
            $days = max(2, $days);
        }
        $totalAmount = ($rate * $days)
            + ($data['driver_option'] === 'with_driver' ? 1500 * $days : 0);
        $paidAmount = $data['payment_mode'] === 'full'
            ? $totalAmount
            : ($data['payment_mode'] === 'deposit' ? 1000 : 0);

        $reservation = DB::transaction(function () use (
            $vehicle,
            $data,
            $user,
            $documentPaths,
            $paymentProofPath,
            $paymentReference,
            $totalAmount,
            $paidAmount
        ): Reservation {
            $lockedVehicle = Vehicle::lockForUpdate()->findOrFail($vehicle->id);
            if (in_array(strtolower((string) $lockedVehicle->status), ['maintenance', 'unavailable'], true)
                || $lockedVehicle->hasAvailabilityConflict($data['pickup_date'], $data['return_date'])) {
                throw ValidationException::withMessages([
                    'vehicle' => 'This vehicle unit is no longer available for the selected dates.',
                ]);
            }

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'vehicle_id' => $lockedVehicle->id,
                'booking_source' => 'user',
                'vehicle' => $lockedVehicle->name,
                'rate_type' => $data['rate_type'],
                'service_option' => $data['service_option'],
                'driver_option' => $data['driver_option'],
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
                'pickup_date' => $data['pickup_date'],
                'pickup_time' => $data['pickup_time'],
                'return_date' => $data['return_date'],
                'return_time' => $data['return_time'],
                'document_paths' => $documentPaths,
                'privacy_consent' => true,
                'payment_mode' => $data['payment_mode'],
                'payment_proof_path' => $paymentProofPath,
                'payment_reference_id' => $paymentReference,
                'payment_status' => $paymentProofPath || $paymentReference ? 'pending' : 'unpaid',
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'control_number' => $this->controlNumber(),
                'status' => 'pending',
            ]);

            $schedule = collect($lockedVehicle->schedule ?? [])
                ->push([
                    'id' => $reservation->id,
                    'vehicleId' => $lockedVehicle->id,
                    'reservationId' => $reservation->id,
                    'type' => 'Reserved',
                    'start' => $reservation->pickup_date->toDateString(),
                    'end' => $reservation->return_date->toDateString(),
                    'notes' => 'Reservation '.$reservation->control_number,
                ])->values()->all();
            $lockedVehicle->update(['status' => 'rented', 'schedule' => $schedule]);

            return $reservation;
        });

        return redirect()
            ->route('user.dashboard')
            ->with('success', 'Reservation submitted successfully. Your control number is '.$reservation->control_number.'.');
    }

    public function updateDocuments(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'document_type' => ['required', 'string', 'in:driver_license,valid_id,proof_of_billing'],
            'document' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $documents = $reservation->document_paths ?? [];
        $user = $request->user();
        $documents[$data['document_type']] = $this->storeUploadedDocument(
            $request->file('document'),
            $data['document_type']
        );

        $reviewAfter = now();
        if ($user->documents_verified_at && $reviewAfter->lessThanOrEqualTo($user->documents_verified_at)) {
            $reviewAfter = $user->documents_verified_at->copy()->addSecond();
        }

        $reservation->update([
            'document_paths' => $documents,
            'documents_updated_at' => $reviewAfter,
        ]);

        if ($user->documents_verified_at) {
            $user->update(['documents_rejected_at' => $reviewAfter]);
        }

        return back()->with('success', 'Document updated successfully. The admin must review the updated document before your booking can continue.');
    }

    private function storeUploadedDocument(UploadedFile $file, string $documentType): string
    {
        $path = $file->store('reservations/documents', 'public');
        if (is_string($path) && Storage::disk('public')->exists($path)) {
            return $path;
        }

        report(new RuntimeException('Unable to store uploaded reservation document: '.$documentType));

        throw ValidationException::withMessages([
            $documentType => 'The document could not be saved. Please try again or contact support.',
        ]);
    }

    private function documentPathIsAvailable(mixed $path): bool
    {
        if (! is_string($path) || blank($path)) {
            return false;
        }

        return filter_var($path, FILTER_VALIDATE_URL)
            || Storage::disk('public')->exists(ltrim($path, '/'));
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        if ($reservation->status !== 'pending') {
            return back()->withErrors(['reservation' => 'Only pending reservations can be cancelled.']);
        }

        DB::transaction(function () use ($reservation, $request): void {
            $reservation->update(['status' => 'cancelled']);
            $this->removeReservationSchedule($reservation);

            $user = $request->user();
            if ($user && $user->documents_verified_at) {
                $user->update([
                    'documents_rejected_at' => now(),
                ]);
            }

        });

        return back()->with('success', 'Booking cancelled successfully. The selected vehicle unit has been released.');
    }

    private function removeReservationSchedule(Reservation $reservation): void
    {
        $vehicle = $reservation->vehicleUnit()->lockForUpdate()->first();
        if (! $vehicle) {
            return;
        }

        $schedule = collect($vehicle->schedule ?? [])
            ->reject(fn (array $entry): bool => ($entry['reservationId'] ?? null) === $reservation->id)
            ->values()
            ->all();
        $hasActiveReservation = Reservation::where('vehicle_id', $vehicle->id)
            ->where('id', '<>', $reservation->id)
            ->whereNotIn('status', ['cancelled', 'void', 'completed'])
            ->exists();
        $vehicle->update([
            'status' => $vehicle->status === 'maintenance'
                ? 'maintenance'
                : ($hasActiveReservation ? 'rented' : 'available'),
            'schedule' => $schedule,
        ]);
    }

    private function controlNumber(): string
    {
        do {
            $number = 'BB-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Reservation::where('control_number', $number)->exists());

        return $number;
    }
}
