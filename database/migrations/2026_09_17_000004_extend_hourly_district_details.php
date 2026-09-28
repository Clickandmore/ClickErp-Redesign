<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hourly_service_districts', function(Blueprint $t) {
            $t->string('name_en',150)->nullable();
            $t->string('slug',200)->nullable();
            $t->decimal('latitude',10,7)->nullable();
            $t->decimal('longitude',10,7)->nullable();
            $t->unique(['city_id','name_en']);
        });
    }
    public function down(): void
    {
        Schema::table('hourly_service_districts', function(Blueprint $t) {
            $t->dropUnique(['city_id','name_en']);
            $t->dropColumn(['name_en','slug','latitude','longitude']);
        });
    }
};
