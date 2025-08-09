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
            // إزالة after() لتجنب الخطأ إذا كان العمود غير موجود
            $table->boolean('procedures_confirmed')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('admins')->onDelete('set null');
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
