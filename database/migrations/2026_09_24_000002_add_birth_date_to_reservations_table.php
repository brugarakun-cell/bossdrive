<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reservations', 'customer_birth_date')) {
            Schema::table('reservations', function (Blueprint $table): void {
                $table->date('customer_birth_date')->nullable()->after('customer_age');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'customer_birth_date')) {
            Schema::table('reservations', function (Blueprint $table): void {
                $table->dropColumn('customer_birth_date');
            });
        }
    }
};
