<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reservations', 'customer_email')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('customer_email')->nullable()->after('customer_age');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'customer_email')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('customer_email');
            });
        }
    }
};
