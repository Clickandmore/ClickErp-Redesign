<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComplaintTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complaint_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->unsignedBigInteger('complaint_type_id')->nullable()->after('id');
            $table->foreign('complaint_type_id')->references('id')->on('complaint_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['complaint_type_id']);
            $table->dropColumn('complaint_type_id');
        });
        Schema::dropIfExists('complaint_types');
    }
}
