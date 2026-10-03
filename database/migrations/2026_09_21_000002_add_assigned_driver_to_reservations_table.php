<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('assigned_driver_name')->nullable()->after('driver_option');
            $table->string('assigned_driver_contact', 30)->nullable()->after('assigned_driver_name');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['assigned_driver_name', 'assigned_driver_contact']);
        });
    }
};
