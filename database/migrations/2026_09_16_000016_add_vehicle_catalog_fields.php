<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('name');
            $table->string('category')->nullable()->after('price');
            $table->string('transmission')->nullable()->after('category');
            $table->string('fuel')->nullable()->after('transmission');
            $table->unsignedTinyInteger('capacity')->nullable()->after('fuel');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['price', 'category', 'transmission', 'fuel', 'capacity']);
        });
    }
};
