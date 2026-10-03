<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('payment_submitted_amount', 12, 2)->nullable()->after('payment_status');
        });

        Schema::table('rental_extensions', function (Blueprint $table) {
            $table->decimal('payment_submitted_amount', 12, 2)->nullable()->after('payment_reference_id');
        });
    }

    public function down(): void
    {
        Schema::table('rental_extensions', function (Blueprint $table) {
            $table->dropColumn('payment_submitted_amount');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('payment_submitted_amount');
        });
    }
};
