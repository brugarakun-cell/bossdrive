<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('vehicle_id')
                ->nullable()
                ->after('user_id')
                ->constrained('vehicles')
                ->nullOnDelete();
        });

        $unitsByName = DB::table('vehicles')
            ->orderBy('id')
            ->get(['id', 'name', 'schedule'])
            ->groupBy('name');

        DB::table('reservations')
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use ($unitsByName): void {
                foreach ($reservations as $reservation) {
                    $units = $unitsByName->get($reservation->vehicle, collect());
                    $unit = $units->first(function ($candidate) use ($reservation): bool {
                        $schedule = json_decode($candidate->schedule ?: '[]', true) ?: [];

                        return collect($schedule)->contains(
                            fn (array $entry): bool => (int) ($entry['reservationId'] ?? 0) === (int) $reservation->id
                        );
                    }) ?? $units->first();

                    if ($unit) {
                        DB::table('reservations')
                            ->where('id', $reservation->id)
                            ->update(['vehicle_id' => $unit->id]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
        });
    }
};
