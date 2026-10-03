<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('payment_proof_path')->nullable()->after('payment_mode');
            $table->string('payment_reference_id', 32)->nullable()->after('payment_proof_path');
            $table->string('payment_status')->default('unpaid')->after('payment_reference_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['payment_proof_path', 'payment_reference_id', 'payment_status']);
        });
    }
};
