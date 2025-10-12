<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixMaidsPrimaryKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `maids` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        $maxId = DB::table('maids')->max('id');
        $nextId = ($maxId === null ? 1 : ((int) $maxId + 1));

        DB::statement('ALTER TABLE `maids` AUTO_INCREMENT = ' . $nextId);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `maids` MODIFY `id` BIGINT UNSIGNED NOT NULL');
    }
}
