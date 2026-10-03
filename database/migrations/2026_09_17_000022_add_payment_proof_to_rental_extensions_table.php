<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_extensions', function (Blueprint $table) {
            $table->string('payment_proof_path')->nullable()->after('status');
            $table->string('payment_reference_id', 32)->nullable()->after('payment_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('rental_extensions', function (Blueprint $table) {
            $table->dropColumn(['payment_proof_path', 'payment_reference_id']);
        });
    }
};
