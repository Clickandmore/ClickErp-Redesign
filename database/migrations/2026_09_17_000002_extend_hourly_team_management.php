<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hourly_teams', fn (Blueprint $t) => $t->unsignedBigInteger('shelter_place_id')->nullable()->change());
        Schema::table('hourly_team_members', fn (Blueprint $t) => $t->boolean('is_active')->default(true));
        Schema::create('hourly_service_districts', function (Blueprint $t) {
            $t->id(); $t->foreignId('city_id')->constrained('cities')->restrictOnDelete();
            $t->string('name', 150); $t->timestamps(); $t->unique(['city_id', 'name']);
        });
        Schema::create('hourly_team_district', function (Blueprint $t) {
            $t->id(); $t->foreignId('hourly_team_id')->constrained('hourly_teams')->cascadeOnDelete();
            $t->foreignId('district_id')->constrained('hourly_service_districts')->restrictOnDelete();
            $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['hourly_team_id', 'district_id']);
        });
        Schema::create('hourly_team_leaders', function (Blueprint $t) {
            $t->id(); $t->foreignId('hourly_team_id')->constrained('hourly_teams')->cascadeOnDelete();
            $t->unsignedBigInteger('admin_id'); $t->timestamps();
            $t->unique(['hourly_team_id', 'admin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_team_leaders');
        Schema::dropIfExists('hourly_team_district');
        Schema::dropIfExists('hourly_service_districts');
        Schema::table('hourly_team_members', fn (Blueprint $t) => $t->dropColumn('is_active'));
        // Keep shelter nullable: teams created without a shelter must remain valid.
    }
};
