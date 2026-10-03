<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'brand_name', 'plate', 'price', 'category', 'transmission', 'fuel', 'capacity', 'capacity_type',
        'status', 'maintenance_notes', 'damage_log', 'image_path', 'schedule', 'feedbacks', 'rating',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'capacity' => 'integer',
            'damage_log' => 'array',
            'schedule' => 'array',
            'feedbacks' => 'array',
            'rating' => 'decimal:2',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'vehicle_id');
    }

    public function hasAvailabilityConflict(string $start, string $end, ?int $exceptReservationId = null): bool
    {
        $hasReservation = $this->reservations()
            ->whereNotIn('status', ['cancelled', 'void', 'completed'])
            ->when($exceptReservationId, fn ($query) => $query->where('id', '<>', $exceptReservationId))
            ->whereDate('pickup_date', '<=', $end)
            ->whereDate('return_date', '>=', $start)
            ->exists();
        $hasMaintenance = collect($this->schedule ?? [])->contains(
            fn (array $entry): bool => ($entry['type'] ?? null) === 'Maintenance'
                && filled($entry['start'] ?? null)
                && filled($entry['end'] ?? null)
                && (string) $entry['start'] <= $end
                && (string) $entry['end'] >= $start
        );

        return $hasReservation || $hasMaintenance;
    }
}
