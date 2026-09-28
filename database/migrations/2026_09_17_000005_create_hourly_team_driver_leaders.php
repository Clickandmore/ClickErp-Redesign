<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Keep legacy admin assignments separate: admin IDs are not driver IDs.
        Schema::create('hourly_team_driver_leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hourly_team_id')->constrained('hourly_teams')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('hourly_drivers')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['hourly_team_id', 'driver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_team_driver_leaders');
    }
};
