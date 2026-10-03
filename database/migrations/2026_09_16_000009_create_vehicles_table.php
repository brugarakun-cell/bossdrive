<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('plate')->unique();
            $table->string('status')->default('available');
            $table->text('maintenance_notes')->nullable();
            $table->timestamps();
            $table->index('status');
        });
        DB::table('vehicles')->insert([
            ['name' => 'Toyota Vios', 'plate' => 'BB-001', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mitsubishi Mirage', 'plate' => 'BB-002', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Honda Civic', 'plate' => 'BB-003', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Toyota Fortuner', 'plate' => 'BB-004', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
