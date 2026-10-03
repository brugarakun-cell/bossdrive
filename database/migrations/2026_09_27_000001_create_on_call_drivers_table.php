<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('on_call_drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('contact', 30);
            $table->boolean('available_this_week')->default(true);
            $table->date('availability_week')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('on_call_drivers');
    }
};
