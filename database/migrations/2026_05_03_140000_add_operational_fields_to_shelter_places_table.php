<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOperationalFieldsToShelterPlacesTable extends Migration
{
    public function up()
    {
        Schema::table('shelter_places', function (Blueprint $table) {
            $table->unsignedInteger('female_capacity')->nullable()->after('id');
            $table->unsignedInteger('male_capacity')->nullable()->after('female_capacity');
            $table->string('address', 1000)->nullable()->after('male_capacity');
            $table->string('map_url', 1000)->nullable()->after('address');
            $table->decimal('latitude', 10, 7)->nullable()->after('map_url');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedBigInteger('supervisor_admin_id')->nullable()->after('longitude');
            $table->unsignedBigInteger('responsible_admin_id')->nullable()->after('supervisor_admin_id');
            $table->text('notes')->nullable()->after('responsible_admin_id');

            $table->foreign('supervisor_admin_id', 'shelter_places_supervisor_admin_fk')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');

            $table->foreign('responsible_admin_id', 'shelter_places_responsible_admin_fk')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('shelter_places', function (Blueprint $table) {
            $table->dropForeign('shelter_places_supervisor_admin_fk');
            $table->dropForeign('shelter_places_responsible_admin_fk');

            $table->dropColumn([
                'female_capacity',
                'male_capacity',
                'address',
                'map_url',
                'latitude',
                'longitude',
                'supervisor_admin_id',
                'responsible_admin_id',
                'notes',
            ]);
        });
    }
}
