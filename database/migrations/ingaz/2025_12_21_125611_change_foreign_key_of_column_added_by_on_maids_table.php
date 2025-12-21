<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeForeignKeyOfColumnAddedByOnMaidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->unsignedBigInteger('added_by')->nullable()->after('office_id')->comment('اضيف بواسطة')->change();
            $table->foreign('added_by', 'maids_added_by_admin_fk')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropColumn('added_by');
            $table->unsignedBigInteger('added_by')->nullable()->change();
        });
    }
}
