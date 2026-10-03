<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $fillable = ['gcash_qr_path', 'account_name', 'account_number'];

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
