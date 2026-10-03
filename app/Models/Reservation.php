<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'booking_source',
        'customer_name',
        'customer_phone',
        'customer_age',
        'customer_birth_date',
        'customer_email',
        'vehicle',
        'rate_type',
        'service_option',
        'driver_option',
        'assigned_driver_name',
        'assigned_driver_contact',
        'return_condition_checks',
        'return_condition_notes',
        'return_condition_photo_path',
        'return_condition_checked_at',
        'return_condition_checked_by',
        'return_condition_checked_by_role',
        'delivery_address',
        'delivery_notes',
        'delivery_latitude',
        'delivery_longitude',
        'pickup_date',
        'pickup_time',
        'return_date',
        'return_time',
        'document_paths',
        'documents_updated_at',
        'privacy_consent',
        'payment_mode',
        'payment_proof_path',
        'payment_reference_id',
        'payment_status',
        'payment_submitted_amount',
        'total_amount',
        'paid_amount',
        'control_number',
        'status',
        'released_at',
        'rejection_comment',
    ];

    protected function casts(): array
    {
        return [
            'pickup_date' => 'date',
            'return_date' => 'date',
            'customer_birth_date' => 'date',
            'document_paths' => 'array',
            'documents_updated_at' => 'datetime',
            'return_condition_checks' => 'array',
            'return_condition_checked_at' => 'datetime',
            'privacy_consent' => 'boolean',
            'released_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_submitted_amount' => 'decimal:2',
        ];
    }

    public function rentalDays(): int
    {
        if (! $this->pickup_date || ! $this->return_date) {
            return 1;
        }

        if (! $this->pickup_time || ! $this->return_time) {
            return max(1, $this->pickup_date->diffInDays($this->return_date) + 1);
        }

        $pickup = Carbon::parse($this->pickup_date->toDateString().' '.$this->pickup_time);
        $return = Carbon::parse($this->return_date->toDateString().' '.$this->return_time);
        $durationSeconds = max(0, $return->getTimestamp() - $pickup->getTimestamp());

        return max(1, (int) ceil($durationSeconds / 86400));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicleUnit(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class);
    }

    public function pickupConditionReports(): HasMany
    {
        return $this->hasMany(PickupConditionReport::class);
    }
}
