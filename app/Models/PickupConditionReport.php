<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupConditionReport extends Model
{
    protected $fillable = ['reservation_id', 'user_id', 'checks', 'notes', 'photo_path'];

    protected $casts = ['checks' => 'array'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
