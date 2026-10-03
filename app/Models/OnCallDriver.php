<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnCallDriver extends Model
{
    protected $fillable = [
        'name',
        'contact',
        'available_this_week',
        'availability_week',
    ];

    protected function casts(): array
    {
        return [
            'available_this_week' => 'boolean',
            'availability_week' => 'date',
        ];
    }
}
