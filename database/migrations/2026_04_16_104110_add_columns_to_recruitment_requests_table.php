<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToRecruitmentRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            $table->boolean('is_read')->default(false)->after('worker_status');
            $table->unsignedBigInteger('read_by')->nullable()->after('is_read');

            $table->foreign('read_by')->references('id')->on('admins')->onDelete('set null')->onUpdate('cascade');
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
            $table->dropForeign(['read_by']);
            $table->dropColumn('is_read');
            $table->dropColumn('read_by');
        });
    }
}
