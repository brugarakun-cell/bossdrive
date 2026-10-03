<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PickupConditionReport;
use App\Models\RentalExtension;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ActiveRentalController extends Controller
{
    public function show(Request $request): View
    {
        $reservation = null;
        $vehicleDailyPrice = 0;
        $activeVehicle = null;
        $activeReservations = $request->user()->reservations()
            ->whereIn('status', ['verified', 'processing', 'released'])
            ->latest()
            ->get();
        $reservationId = $request->integer('reservation_id')
            ?: $request->session()->get('active_rental_reservation_id');

        if ($reservationId) {
            $reservation = $activeReservations->firstWhere('id', $reservationId);
        }

        if (! $reservation) {
            $reservation = $activeReservations->first();
            if ($reservation) {
                $request->session()->put('active_rental_reservation_id', $reservation->id);
            }
        }

        if ($reservation) {
            $activeVehicle = $reservation->vehicleUnit;
            $vehicleDailyPrice = (float) ($activeVehicle?->price ?? 0);
        }

        return view('user.active-rental', compact(
            'reservation',
            'vehicleDailyPrice',
            'activeVehicle',
            'activeReservations',
        ));
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'control_number' => ['required', 'string', 'max:255'],
        ]);

        $reservation = $request->user()->reservations()
            ->where('control_number', $data['control_number'])
            ->first();

        if (! $reservation) {
            return back()->withErrors([
                'control_number' => 'The control number is invalid or does not belong to your account.',
            ])->withInput();
        }

        if (! in_array($reservation->status, ['verified', 'processing', 'released'], true)) {
            return back()->withErrors([
                'control_number' => 'Your rental is not ready for Active Rental access yet. Staff must verify the reservation first.',
            ])->withInput();
        }

        $request->session()->put('active_rental_reservation_id', $reservation->id);

        return redirect()->route('user.active-rental');
    }

    public function submitCondition(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);
        abort_unless(in_array($reservation->status, ['verified', 'processing', 'released'], true), 422);

        $data = $request->validate([
            'checks' => ['required', 'array', 'min:1'],
            'checks.*' => ['string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'damage_part' => ['nullable', 'string', 'max:255'],
            'damage_description' => ['nullable', 'string', 'max:2000'],
        ]);

        $path = $request->hasFile('photo')
            ? $request->file('photo')->store('reservations/conditions', 'public')
            : null;

        PickupConditionReport::create([
            'reservation_id' => $reservation->id,
            'user_id' => $request->user()->id,
            'checks' => $data['checks'],
            'notes' => collect([
                $data['notes'] ?? null,
                filled($data['damage_part'] ?? null) ? 'Part: '.$data['damage_part'] : null,
                filled($data['damage_description'] ?? null) ? 'Description: '.$data['damage_description'] : null,
            ])->filter()->implode("\n"),
            'photo_path' => $path,
        ]);

        return back()->with('success', 'Vehicle condition report submitted.');
    }

    public function requestExtension(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);
        abort_unless($reservation->status === 'released', 422);

        $data = $request->validate([
            'requested_return_date' => ['required', 'date', 'after:'.$reservation->return_date->toDateString()],
        ]);
        $newDate = Carbon::parse($data['requested_return_date']);
        $extensionDays = max(1, $reservation->return_date->diffInDays($newDate));
        $vehicle = $reservation->vehicleUnit;
        abort_if(
            $vehicle && $vehicle->hasAvailabilityConflict($reservation->pickup_date->toDateString(), $newDate->toDateString(), $reservation->id),
            422,
            'The requested extension conflicts with another reservation or maintenance schedule for this unit.'
        );
        $extensionAmount = ($vehicle?->price ?? 0) * $extensionDays
            + ($reservation->driver_option === 'with_driver' ? 1500 * $extensionDays : 0);

        RentalExtension::create([
            'reservation_id' => $reservation->id,
            'days' => $extensionDays,
            'amount' => $extensionAmount,
            'requested_return_date' => $newDate,
            'status' => 'approved',
        ]);
        $reservation->update(['return_date' => $newDate]);
        if ($vehicle) {
            $schedule = collect($vehicle->schedule ?? [])->map(function (array $entry) use ($reservation, $newDate) {
                if (($entry['reservationId'] ?? null) === $reservation->id) {
                    $entry['end'] = $newDate->toDateString();
                }

                return $entry;
            })->values()->all();
            $vehicle->update(['schedule' => $schedule]);
        }

        return back()->with('success', 'Rental extension saved. New return date: '.$newDate->format('F j, Y').'.');
    }

    public function initiateReturn(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);
        abort_unless($reservation->status === 'released', 422);

        $reservation->update(['status' => 'completed']);
        $vehicle = $reservation->vehicleUnit;
        if ($vehicle && $vehicle->status !== 'maintenance') {
            $hasActiveReservation = $vehicle->reservations()
                ->where('id', '<>', $reservation->id)
                ->whereNotIn('status', ['cancelled', 'void', 'completed'])
                ->exists();
            $vehicle->update([
                'status' => $hasActiveReservation ? 'rented' : 'available',
                'schedule' => collect($vehicle->schedule ?? [])
                    ->reject(fn (array $entry): bool => ($entry['reservationId'] ?? null) === $reservation->id)
                    ->values()
                    ->all(),
            ]);
        }

        return redirect()->route('user.dashboard')->with('success', 'Return request submitted. Your booking is now completed.');
    }
}
