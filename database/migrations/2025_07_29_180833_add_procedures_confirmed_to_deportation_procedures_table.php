<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProceduresConfirmedToDeportationProceduresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deportation_procedures', function (Blueprint $table) {
            $table->boolean('procedures_confirmed')->default(false)->after('trip_id');
            $table->timestamp('confirmed_at')->nullable()->after('procedures_confirmed');
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')->constrained('admins')->onDelete('set null');
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
            $table->dropColumn(['procedures_confirmed', 'confirmed_at', 'confirmed_by']);
        });
    }
}
