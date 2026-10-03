<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('vehicle');
            $table->string('service_option');
            $table->string('driver_option');
            $table->date('pickup_date');
            $table->date('return_date');
            $table->json('document_paths');
            $table->boolean('privacy_consent')->default(false);
            $table->string('payment_mode');
            $table->string('control_number')->unique();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['pickup_date', 'return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
