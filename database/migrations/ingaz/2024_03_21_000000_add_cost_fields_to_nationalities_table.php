<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostFieldsToNationalitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nationalities', function (Blueprint $table) {
            $table->decimal('service_transportation_cost', 10, 2)->nullable();
            $table->decimal('rental_cost', 10, 2)->nullable();
            $table->decimal('monthly_salary', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nationalities', function (Blueprint $table) {
            $table->dropColumn([
                'service_transportation_cost',
                'rental_cost',
                'monthly_salary'
            ]);
        });
    }
}
