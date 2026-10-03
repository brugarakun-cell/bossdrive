<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_condition_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('checks');
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->index(['reservation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_condition_reports');
    }
};
