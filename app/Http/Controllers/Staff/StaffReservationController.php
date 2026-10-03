<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffReservationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Reservation::with(['user', 'pickupConditionReports', 'vehicleUnit'])->latest();
        $walkInOnly = $request->boolean('walkin');
        $query->where('booking_source', $walkInOnly ? 'admin_staff' : 'user');
        $reservations = $query->get();

        return view('staff.reservations', compact('reservations', 'walkInOnly'));
    }

    public function walkInReservations(Request $request): View
    {
        $request->merge(['walkin' => true, 'admin_staff_booking' => true]);

        return $this->index($request);
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
            'isStaffMode' => true,
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

    public function updateStatus(Request $request, Reservation $reservation): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:processing,released,completed'],
            'assigned_driver_name' => ['nullable', 'string', 'max:255'],
            'assigned_driver_contact' => ['nullable', 'string', 'max:30'],
            'return_condition_checks' => ['nullable', 'array', 'min:1'],
            'return_condition_checks.*' => ['string', 'max:100'],
            'return_condition_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        abort_if($reservation->status === 'cancelled', 422, 'Cancelled reservations cannot be processed.');
        abort_if($reservation->status === 'completed', 422, 'Completed reservations cannot be processed or changed.');
        $documentsVerified = ! $reservation->user_id || ($reservation->user?->documents_verified_at
            && (! $reservation->user->documents_rejected_at
                || $reservation->user->documents_rejected_at <= $reservation->user->documents_verified_at));

        if ($data['status'] === 'processing') {
            abort_unless(
                $reservation->status === 'verified' && $documentsVerified,
                422,
                'Admin must verify the customer documents before staff can process this reservation.'
            );
        } elseif ($data['status'] === 'released') {
            abort_unless($reservation->status === 'processing', 422, 'Only processing reservations can be released.');
        } elseif ($data['status'] === 'completed') {
            abort_unless($reservation->status === 'released', 422, 'Only released reservations can be returned.');
            abort_unless(
                $documentsVerified,
                422,
                'Updated customer documents must be reviewed before this reservation can be returned.'
            );
            abort_unless(! empty($data['return_condition_checks']), 422, 'Complete the vehicle return condition check before returning this reservation.');
        }

        $inspectorName = $request->user()?->name
            ?: (string) $request->session()->get('staff_name', 'Staff User');

        DB::transaction(function () use ($reservation, $data, $inspectorName): void {
            $reservation->update([
                'status' => $data['status'],
                'assigned_driver_name' => $data['assigned_driver_name'] ?? $reservation->assigned_driver_name,
                'assigned_driver_contact' => $data['assigned_driver_contact'] ?? $reservation->assigned_driver_contact,
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
                    ? 'Staff'
                    : $reservation->return_condition_checked_by_role,
            ]);

            $vehicle = $reservation->vehicleUnit;
            if ($vehicle && $vehicle->status !== 'maintenance') {
                $schedule = collect($vehicle->schedule ?? [])
                    ->reject(fn (array $entry): bool => (int) ($entry['reservationId'] ?? 0) === (int) $reservation->id);
                if ($data['status'] !== 'completed') {
                    $schedule->push([
                        'id' => $reservation->id,
                        'vehicleId' => $vehicle->id,
                        'reservationId' => $reservation->id,
                        'type' => $data['status'] === 'released' ? 'Rented' : 'Reserved',
                        'start' => $reservation->pickup_date->toDateString(),
                        'end' => $reservation->return_date->toDateString(),
                        'notes' => 'Reservation '.$reservation->control_number,
                    ]);
                }
                $hasActiveReservation = Reservation::where('vehicle_id', $vehicle->id)
                    ->where('id', '<>', $reservation->id)
                    ->whereNotIn('status', ['cancelled', 'void', 'completed'])
                    ->exists();
                $vehicle->update([
                    'status' => $hasActiveReservation || $data['status'] !== 'completed'
                        ? 'rented'
                        : 'available',
                    'schedule' => $schedule->values()->all(),
                ]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Reservation status updated.',
                'reservation' => $reservation->fresh(),
                'vehicle' => $reservation->vehicleUnit,
            ]);
        }

        return back()->with('success', 'Reservation status updated.');
    }
}
