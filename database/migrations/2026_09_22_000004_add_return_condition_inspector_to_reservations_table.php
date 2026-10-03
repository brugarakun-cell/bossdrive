<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('return_condition_checked_by')->nullable()->after('return_condition_checked_at');
            $table->string('return_condition_checked_by_role')->nullable()->after('return_condition_checked_by');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn(['return_condition_checked_by', 'return_condition_checked_by_role']);
        });
    }
};
