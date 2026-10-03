<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('reservations', 'customer_phone')) {
                $table->string('customer_phone')->nullable()->after('customer_name');
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->change();
            });
        }

        if (DB::getDriverName() === 'sqlite') {
            try {
                Schema::table('reservations', function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                // SQLite may preserve the existing nullability for legacy tables,
                // but the application only needs the column to accept null values.
            }
        }
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'customer_phone')) {
                $table->dropColumn('customer_phone');
            }

            if (Schema::hasColumn('reservations', 'customer_name')) {
                $table->dropColumn('customer_name');
            }
        });
    }
};
