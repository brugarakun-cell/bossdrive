<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_guides', function (Blueprint $table) {
            $table->id();
            $table->string('category')->unique();
            $table->decimal('city_driving', 10, 2);
            $table->decimal('province', 10, 2);
            $table->decimal('long_distance', 10, 2);
            $table->decimal('hourly', 10, 2);
            $table->timestamps();
        });

        foreach (\App\Models\PriceGuide::defaults() as $default) {
            \App\Models\PriceGuide::create($default);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('price_guides');
    }
};
