<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reservations', 'booking_source')) {
            Schema::table('reservations', function (Blueprint $table): void {
                $table->string('booking_source')->default('user')->after('user_id');
                $table->index('booking_source');
            });
        }

        DB::table('reservations')
            ->whereNull('user_id')
            ->update(['booking_source' => 'admin_staff']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'booking_source')) {
            Schema::table('reservations', function (Blueprint $table): void {
                $table->dropIndex(['booking_source']);
                $table->dropColumn('booking_source');
            });
        }
    }
};
