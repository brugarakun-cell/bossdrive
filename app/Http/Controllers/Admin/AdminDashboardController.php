<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $activeUsers = $this->activeUsersQuery();

        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'activeUsers' => (clone $activeUsers)->count(),
            'activeUserList' => $activeUsers
                ->latest('last_seen_at')
                ->get(['name', 'email', 'role']),
            'totalRentals' => Reservation::whereNotIn('status', ['cancelled', 'void'])->count(),
            'pendingReservations' => Reservation::where('status', 'pending')->count(),
            'activeRentals' => Reservation::whereIn('status', ['processing', 'released'])->count(),
            'pendingPayments' => Reservation::where('payment_status', 'pending')->count(),
            'reservations' => Reservation::with('user')->latest()->take(10)->get(),
            'rentedVehicles' => Vehicle::query()
                ->withCount(['reservations' => fn ($query) => $query->whereNotIn('status', ['cancelled', 'void'])])
                ->orderByDesc('reservations_count')
                ->get(['id', 'name', 'plate']),
            'monthlyRentals' => $this->rentalOverview('year', now()),
        ]);
    }

    public function liveOverview(Request $request): JsonResponse
    {
        $period = $request->query('period', 'year');
        abort_unless(in_array($period, ['year', 'month', 'week', 'day'], true), 422, 'Invalid rental overview period.');

        $dateValue = $request->query('date', now()->toDateString());
        abort_unless(is_string($dateValue) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue), 422, 'Invalid rental overview date.');
        $date = Carbon::createFromFormat('!Y-m-d', $dateValue);
        abort_unless($date && $date->format('Y-m-d') === $dateValue, 422, 'Invalid rental overview date.');

        $activeUsers = $this->activeUsersQuery()
            ->latest('last_seen_at')
            ->get(['name', 'email', 'role']);

        return response()->json([
            'totalUsers' => User::count(),
            'totalRentals' => Reservation::whereNotIn('status', ['cancelled', 'void'])->count(),
            'activeUsers' => $activeUsers
                ->map(fn (User $user): array => $user->only(['name', 'email', 'role']))
                ->values(),
            'activeUserCount' => $activeUsers->count(),
            'unitsRented' => Vehicle::query()
                ->withCount(['reservations' => fn ($query) => $query->whereNotIn('status', ['cancelled', 'void'])])
                ->orderByDesc('reservations_count')
                ->get(['name', 'plate'])
                ->map(fn (Vehicle $vehicle): array => [
                    'name' => $vehicle->name,
                    'plate' => $vehicle->plate,
                    'reservations_count' => $vehicle->reservations_count,
                ]),
            'chart' => $this->rentalOverview($period, $date),
        ])->header('Cache-Control', 'no-store, private');
    }

    private function activeUsersQuery()
    {
        return User::query()
            ->where('role', 'user')
            ->where('last_seen_at', '>=', now()->subMinutes(5));
    }

    private function rentalOverview(string $period, Carbon $date): array
    {
        $start = match ($period) {
            'year' => $date->copy()->startOfYear(),
            'month' => $date->copy()->startOfMonth(),
            'week' => $date->copy()->startOfWeek(Carbon::MONDAY),
            default => $date->copy()->startOfDay(),
        };
        $end = match ($period) {
            'year' => $date->copy()->endOfYear(),
            'month' => $date->copy()->endOfMonth(),
            'week' => $date->copy()->endOfWeek(Carbon::SUNDAY),
            default => $date->copy()->endOfDay(),
        };
        $driver = DB::connection()->getDriverName();
        $counts = collect();
        if ($period !== 'day') {
            $groupExpression = match ($period) {
                'year' => $driver === 'sqlite'
                    ? "CAST(strftime('%m', created_at) AS INTEGER)"
                    : 'MONTH(created_at)',
                'month' => $driver === 'sqlite'
                    ? "CAST(strftime('%d', created_at) AS INTEGER)"
                    : 'DAY(created_at)',
                default => $driver === 'sqlite'
                    ? "strftime('%Y-%m-%d', created_at)"
                    : 'DATE(created_at)',
            };
            $counts = Reservation::query()
                ->whereNotIn('status', ['cancelled', 'void'])
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw($groupExpression.' as bucket, COUNT(*) as total')
                ->groupBy('bucket')
                ->pluck('total', 'bucket');
        }

        if ($period === 'year') {
            $labels = collect(range(1, 12))->map(fn (int $month): string => Carbon::createFromDate($date->year, $month, 1)->format('M'));
            $values = collect(range(1, 12))->map(fn (int $month): int => (int) ($counts[$month] ?? 0));
        } elseif ($period === 'month') {
            $days = $date->daysInMonth;
            $labels = collect(range(1, $days))->map(fn (int $day): string => (string) $day);
            $values = collect(range(1, $days))->map(fn (int $day): int => (int) ($counts[$day] ?? 0));
        } elseif ($period === 'week') {
            $dates = collect(range(0, 6))->map(fn (int $offset) => $start->copy()->addDays($offset));
            $labels = $dates->map(fn (Carbon $day): string => $day->format('D j'));
            $values = $dates->map(fn (Carbon $day): int => (int) ($counts[$day->toDateString()] ?? 0));
        } else {
            $labels = collect(range(0, 23))->map(fn (int $hour): string => Carbon::createFromTime($hour)->format('g A'));
            $hourCounts = Reservation::query()
                ->whereNotIn('status', ['cancelled', 'void'])
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw((DB::connection()->getDriverName() === 'sqlite'
                    ? "CAST(strftime('%H', created_at) AS INTEGER)"
                    : 'HOUR(created_at)').' as bucket, COUNT(*) as total')
                ->groupBy('bucket')
                ->pluck('total', 'bucket');
            $values = collect(range(0, 23))->map(fn (int $hour): int => (int) ($hourCounts[$hour] ?? 0));
        }

        return [
            'period' => $period,
            'date' => $date->toDateString(),
            'title' => match ($period) {
                'year' => 'Rental Overview — '.$date->format('Y'),
                'month' => 'Rental Overview — '.$date->format('F Y'),
                'week' => 'Rental Overview — Week of '.$start->format('M j, Y'),
                default => 'Rental Overview — '.$date->format('M j, Y'),
            },
            'labels' => $labels->values(),
            'values' => $values->values(),
        ];
    }
}
