<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceGuide extends Model
{
    protected $fillable = ['category', 'city_driving', 'province', 'long_distance', 'hourly'];

    protected function casts(): array
    {
        return [
            'city_driving' => 'decimal:2',
            'province' => 'decimal:2',
            'long_distance' => 'decimal:2',
            'hourly' => 'decimal:2',
        ];
    }

    public static function defaults(): array
    {
        return [
            ['category' => 'Sedan', 'city_driving' => 2500, 'province' => 3500, 'long_distance' => 4000, 'hourly' => 120],
            ['category' => 'Pick-up / Expanded (7-Seater) (2 Days) — Gas', 'city_driving' => 4500, 'province' => 5500, 'long_distance' => 6000, 'hourly' => 150],
            ['category' => 'Expanded — Diesel', 'city_driving' => 3500, 'province' => 4000, 'long_distance' => 5000, 'hourly' => 200],
        ];
    }

    public static function current(): \Illuminate\Database\Eloquent\Collection
    {
        if (!static::query()->exists()) {
            foreach (static::defaults() as $default) {
                static::create($default);
            }
        }

        return static::query()->orderBy('id')->get();
    }
}
