<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalExtension extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'days',
        'amount',
        'paid_amount',
        'requested_return_date',
        'status',
        'payment_proof_path',
        'payment_reference_id',
        'payment_submitted_amount',
    ];

    protected function casts(): array
    {
        return [
            'requested_return_date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_submitted_amount' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
