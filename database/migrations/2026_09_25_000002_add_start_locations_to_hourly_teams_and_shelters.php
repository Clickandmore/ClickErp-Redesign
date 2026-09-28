<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shelter_places', function (Blueprint $table) {
            if (!Schema::hasColumn('shelter_places', 'latitude')) $table->decimal('latitude', 10, 7)->nullable();
            if (!Schema::hasColumn('shelter_places', 'longitude')) $table->decimal('longitude', 10, 7)->nullable();
        });

        Schema::table('hourly_teams', function (Blueprint $table) {
            $table->string('base_location_type', 20)->default('custom')->after('description');
            $table->foreignId('base_shelter_place_id')
                ->nullable()
                ->after('base_location_type')
                ->constrained('shelter_places')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hourly_teams', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') $table->dropForeign(['base_shelter_place_id']);
            $table->dropColumn('base_shelter_place_id');
        });
        Schema::table('hourly_teams', function (Blueprint $table) {
            $table->dropColumn('base_location_type');
        });

        // Coordinates belong to Click as well; retain existing values.
    }
};
