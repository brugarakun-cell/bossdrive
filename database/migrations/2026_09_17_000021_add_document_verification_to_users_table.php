<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('documents_verified_at')->nullable()->after('remember_token');
            $table->timestamp('documents_rejected_at')->nullable()->after('documents_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['documents_verified_at', 'documents_rejected_at']);
        });
    }
};
