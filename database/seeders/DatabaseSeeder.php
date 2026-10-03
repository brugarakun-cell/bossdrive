<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Reservation;
use App\Models\PickupConditionReport;
use App\Models\AuditLog;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $demoReservations = Reservation::whereIn('control_number', [
            'BD-DEMO-ACTIVE',
            'BD-DEMO-CANCELLED',
            'BD-DEMO-PENDING-001',
            'BD-DEMO-PENDING-002',
            'BD-DEMO-PENDING-003',
        ])->get();

        AuditLog::where('entity_type', Reservation::class)
            ->whereIn('entity_id', $demoReservations->pluck('id'))
            ->delete();
        PickupConditionReport::whereIn('reservation_id', $demoReservations->pluck('id'))->delete();
        $demoReservations->each(function (Reservation $reservation) {
            foreach ($reservation->document_paths ?? [] as $path) {
                if ($path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                }
            }
            if ($reservation->payment_proof_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($reservation->payment_proof_path);
            }
            $reservation->delete();
        });

        User::where('email', 'test@example.com')->delete();
        Vehicle::whereIn('plate', ['DEMO-001', 'DEMO-002'])->delete();

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin Patrick', 'password' => Hash::make('password'), 'role' => 'admin']
        );

        $staff = User::firstOrCreate(
            ['email' => 'staff@example.com'],
            ['name' => 'Staff Patrick', 'password' => Hash::make('password'), 'role' => 'staff']
        );

        $customerOne = User::firstOrCreate(
            ['email' => 'customer1@example.com'],
            ['name' => 'John Wick', 'password' => Hash::make('password'), 'role' => 'user', 'contact_number' => '09123456789']
        );

        $customerTwo = User::firstOrCreate(
            ['email' => 'customer2@example.com'],
            ['name' => 'Maria Makiling', 'password' => Hash::make('password'), 'role' => 'user', 'contact_number' => '09987654321']
        );

        $customerThree = User::firstOrCreate(
            ['email' => 'customer3@example.com'],
            ['name' => 'Juan Dela Cruz', 'password' => Hash::make('password'), 'role' => 'user', 'contact_number' => '09171234567']
        );

        $today = now()->startOfDay();
        $reservations = [
            [
                'user_id' => $customerOne->id,
                'customer_name' => 'John Wick',
                'customer_phone' => '09123456789',
                'vehicle' => 'Toyota Vios',
                'service_option' => 'delivery',
                'driver_option' => 'self_drive',
                'delivery_address' => 'SM Dasmariñas, Cavite',
                'pickup_date' => $today->copy()->addDay(),
                'pickup_time' => '09:00:00',
                'return_date' => $today->copy()->addDays(4),
                'return_time' => '09:00:00',
                'document_paths' => [],
                'privacy_consent' => true,
                'payment_mode' => 'deposit',
                'payment_proof_path' => null,
                'payment_reference_id' => 'REF-99012',
                'payment_status' => 'pending',
                'total_amount' => 5000,
                'paid_amount' => 1000,
                'control_number' => 'BD-20260810-7734',
                'status' => 'pending',
            ],
            [
                'user_id' => $customerTwo->id,
                'customer_name' => 'Maria Makiling',
                'customer_phone' => '09987654321',
                'vehicle' => 'Toyota Fortuner',
                'service_option' => 'pickup',
                'driver_option' => 'with_driver',
                'delivery_address' => null,
                'pickup_date' => $today->copy()->addDays(2),
                'pickup_time' => '10:30:00',
                'return_date' => $today->copy()->addDays(5),
                'return_time' => '10:30:00',
                'document_paths' => [],
                'privacy_consent' => true,
                'payment_mode' => 'walkin',
                'payment_proof_path' => null,
                'payment_reference_id' => 'REF-88721',
                'payment_status' => 'approved',
                'total_amount' => 12000,
                'paid_amount' => 12000,
                'control_number' => 'BD-20260811-3092',
                'status' => 'processing',
            ],
            [
                'user_id' => $customerThree->id,
                'customer_name' => 'Juan Dela Cruz',
                'customer_phone' => '09171234567',
                'vehicle' => 'Honda Civic',
                'service_option' => 'pickup',
                'driver_option' => 'self_drive',
                'delivery_address' => null,
                'pickup_date' => $today->copy()->addDays(3),
                'pickup_time' => '08:00:00',
                'return_date' => $today->copy()->addDays(6),
                'return_time' => '08:00:00',
                'document_paths' => [],
                'privacy_consent' => true,
                'payment_mode' => 'full',
                'payment_proof_path' => null,
                'payment_reference_id' => 'REF-11223',
                'payment_status' => 'approved',
                'total_amount' => 9000,
                'paid_amount' => 9000,
                'control_number' => 'BD-20260812-9912',
                'status' => 'released',
            ],
        ];

        foreach ($reservations as $reservation) {
            Reservation::updateOrCreate(
                ['control_number' => $reservation['control_number']],
                $reservation
            );
        }
    }
}
