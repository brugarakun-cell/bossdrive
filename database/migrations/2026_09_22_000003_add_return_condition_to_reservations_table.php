<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->json('return_condition_checks')->nullable()->after('document_paths');
            $table->text('return_condition_notes')->nullable()->after('return_condition_checks');
            $table->string('return_condition_photo_path')->nullable()->after('return_condition_notes');
            $table->timestamp('return_condition_checked_at')->nullable()->after('return_condition_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn([
                'return_condition_checks',
                'return_condition_notes',
                'return_condition_photo_path',
                'return_condition_checked_at',
            ]);
        });
    }
};
