<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToRecruitmentRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            // $table->string('visa_number')->default('#')->nullable()->after('request_type');
            // $table->string('social_status')->default('#')->nullable()->after('visa_number');
            // $table->integer('age')->default(0)->nullable()->after('social_status');
            // $table->foreignId('religion_id')->nullable()->after('age')->references('id')->on('religions')->onDelete('set null');
            // $table->string('language')->default('#')->nullable()->after('religion_id');
            // $table->string('worker_status')->default('#')->nullable()->after('language');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            // $table->dropForeign(['religion_id']);
            // $table->dropColumn('visa_number');
            // $table->dropColumn('social_status');
            // $table->dropColumn('age');
            // $table->dropColumn('religion_id');
            // $table->dropColumn('language');
            // $table->dropColumn('worker_status');
        });
    }
}
