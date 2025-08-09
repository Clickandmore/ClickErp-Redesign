<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTripIdToDeportationProceduresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deportation_procedures', function (Blueprint $table) {
            // إضافة عمود trip_id إذا لم يكن موجوداً
            if (!Schema::hasColumn('deportation_procedures', 'trip_id')) {
                $table->foreignId('trip_id')->nullable()->after('maid_id')
                    ->constrained('travel_histories')->onDelete('set null')
                    ->comment('معرف رحلة السفر');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deportation_procedures', function (Blueprint $table) {
            if (Schema::hasColumn('deportation_procedures', 'trip_id')) {
                $table->dropForeign(['trip_id']);
                $table->dropColumn('trip_id');
            }
        });
    }
}
