<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\PriceGuide;
use App\Models\PickupConditionReport;
use App\Models\User;
use App\Models\OnCallDriver;
use Illuminate\View\View;
use App\Http\Controllers\User\ReservationController as UserReservationController;

class StaffVehicleController extends Controller
{
    public function index(): View
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
        $driverCatalog = $this->driverCatalog();

        $vehicleRows = $vehicles->map(function (Vehicle $vehicle) use ($activeReservations, $calendarReservations): array {
            $activeReservation = $activeReservations->get($vehicle->id);
            $schedule = collect($vehicle->schedule ?? [])
                ->filter(fn (array $entry): bool => ($entry['type'] ?? null) === 'Maintenance'
                    || isset($entry['reservationId'], $calendarReservations[$entry['reservationId']]))
                ->map(function (array $entry) use ($calendarReservations): array {
                    if (($entry['type'] ?? null) === 'Maintenance'
                        || !isset($entry['reservationId'], $calendarReservations[$entry['reservationId']])) {
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
                'photo' => $vehicle->image_path
                    ? asset('storage/'.$vehicle->image_path)
                    : asset('image/car'.(($vehicle->id - 1) % 4 + 1).'.png'),
                'imagePath' => $vehicle->image_path,
                'rating' => (float) $vehicle->rating,
                'timesRented' => $vehicle->reservations_count,
                'feedbacks' => $vehicle->feedbacks ?? [],
                'damageLog' => $vehicle->damage_log ?? [],
                'schedule' => $schedule,
                'currentReservation' => $activeReservation ? [
                    'pickup_date' => $activeReservation->pickup_date->toDateString(),
                    'return_date' => $activeReservation->return_date->toDateString(),
                    'status' => $activeReservation->status,
                ] : null,
            ];
        })->values()->all();

        return view('admin.vehicles', [
            'priceGuides' => PriceGuide::current(),
            'conditionReports' => $conditionReports,
            'returnConditionReports' => $returnConditionReports,
            'vehicles' => $vehicles,
            'vehicleRows' => $vehicleRows,
            'driverCatalog' => $driverCatalog,
            'staffMode' => true,
        ]);
    }

    public function drivers(): View
    {
        $staffAccount = User::find(session('staff_user_id'));
        $staffName = trim((string) (session('staff_name') ?: 'Staff User'));
        $staffInitials = collect(preg_split('/\s+/', $staffName))
            ->filter()
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        $reservations = Reservation::with('user')
            ->where('driver_option', 'with_driver')
            ->whereNotNull('assigned_driver_name')
            ->latest('pickup_date')
            ->get();

        return view('admin.drivers', [
            'driverReservations' => $reservations,
            'driverCatalog' => $this->driverCatalog(),
            'isStaffMode' => true,
            'staffAccount' => $staffAccount,
            'staffInitials' => $staffInitials ?: 'SU',
        ]);
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
            ->get(['assigned_driver_name', 'pickup_date', 'return_date'])
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

            return [
                'id' => $driver->id,
                'name' => $driver->name,
                'contact' => $driver->contact,
                'availableThisWeek' => $driver->available_this_week,
                'availabilityWeek' => $driver->availability_week?->toDateString(),
                'bookings' => $assignments->map(fn (Reservation $reservation): array => [
                    'start' => $reservation->pickup_date->toDateString(),
                    'end' => $reservation->return_date->toDateString(),
                ])->values()->all(),
            ];
        })->all();
    }
}
