<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reservations', 'customer_age')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->unsignedTinyInteger('customer_age')->nullable()->after('customer_phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'customer_age')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('customer_age');
            });
        }
    }
};
