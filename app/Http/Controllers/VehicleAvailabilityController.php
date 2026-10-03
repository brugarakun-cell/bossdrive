<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleAvailabilityController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $includeReservationDetails = (bool) $request->session()->get('is_admin')
            || (bool) $request->session()->get('is_staff');
        $reservations = Reservation::query()
            ->whereNotIn('status', ['cancelled', 'void', 'completed'])
            ->whereNotNull('pickup_date')
            ->whereNotNull('return_date')
            ->with('vehicleUnit:id,name,plate')
            ->latest('created_at')
            ->get();

        $reservationSchedules = $reservations
            ->filter(fn (Reservation $reservation): bool => $reservation->vehicle_id !== null)
            ->map(function (Reservation $reservation) use ($includeReservationDetails): array {
                $schedule = [
                    'id' => $reservation->id,
                    'reservationId' => $reservation->id,
                    'vehicleId' => $reservation->vehicle_id,
                    'vehicle' => $reservation->vehicleUnit?->name,
                    'plate' => $reservation->vehicleUnit?->plate,
                    'type' => $reservation->status === 'released'
                        ? 'Rented'
                        : ($reservation->service_option === 'delivery' ? 'Special' : 'Reserved'),
                    'status' => $reservation->status === 'released' ? 'Ongoing' : 'Reserved',
                    'start' => $reservation->pickup_date->toDateString(),
                    'end' => $reservation->return_date->toDateString(),
                    'notes' => 'Reservation schedule',
                ];

                if ($includeReservationDetails && $schedule['type'] === 'Special') {
                    $schedule['controlNumber'] = $reservation->control_number;
                    $schedule['deliveryDate'] = $reservation->pickup_date->format('F j, Y');
                }

                return $schedule;
            })
            ->values();

        $reservationsByVehicle = $reservationSchedules
            ->groupBy('vehicleId')
            ->map(fn ($entries) => $entries->first());

        $vehicles = Vehicle::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'status', 'schedule'])
            ->map(function (Vehicle $vehicle) use ($reservationSchedules, $reservationsByVehicle): array {
                $maintenanceSchedules = collect($vehicle->schedule ?? [])
                    ->filter(fn (array $entry): bool => ($entry['type'] ?? null) === 'Maintenance')
                    ->map(fn (array $entry): array => [
                        'id' => $entry['id'] ?? null,
                        'type' => 'Maintenance',
                        'start' => $entry['start'] ?? null,
                        'end' => $entry['end'] ?? null,
                    ])
                    ->values();
                $vehicleReservations = $reservationSchedules
                    ->where('vehicleId', $vehicle->id)
                    ->values();
                $activeReservation = $reservationsByVehicle->get($vehicle->id);
                $status = $vehicle->status === 'maintenance'
                    ? 'Maintenance'
                    : ($activeReservation
                        ? 'Rented'
                        : (in_array($vehicle->status, ['unavailable', 'maintenance'], true)
                            ? 'Maintenance'
                            : ($vehicle->status === 'rented' ? 'Available' : ucfirst($vehicle->status))));

                return [
                    'id' => $vehicle->id,
                    'name' => $vehicle->name,
                    'status' => $status,
                    'currentReservation' => $activeReservation ? [
                        'pickup_date' => $activeReservation['start'],
                        'return_date' => $activeReservation['end'],
                        'status' => $activeReservation['status'] === 'Ongoing' ? 'released' : 'reserved',
                    ] : null,
                    'schedule' => $maintenanceSchedules
                        ->concat($vehicleReservations)
                        ->values(),
                ];
            })
            ->values();

        $maintenance = $vehicles->flatMap(function (array $vehicle) {
            return collect($vehicle['schedule'])
                ->filter(fn (array $entry): bool => ($entry['type'] ?? null) === 'Maintenance')
                ->map(fn (array $entry): array => [
                    'vehicleId' => $vehicle['id'],
                    'vehicle' => $vehicle['name'],
                    'status' => 'Maintenance',
                    'start' => $entry['start'] ?? null,
                    'end' => $entry['end'] ?? null,
                ]);
        })->filter(fn (array $entry): bool => filled($entry['start']) && filled($entry['end']))
            ->values();

        return response()->json([
            'vehicles' => $vehicles,
            'reservations' => $reservationSchedules,
            'maintenance' => $maintenance,
        ])->header('Cache-Control', 'no-store, private');
    }
}
