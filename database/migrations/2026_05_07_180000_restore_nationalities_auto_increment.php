<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RestoreNationalitiesAutoIncrement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('nationalities')) {
            return;
        }

        $column = DB::selectOne("
            SELECT EXTRA
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'nationalities'
              AND COLUMN_NAME = 'id'
        ");

        if ($column && stripos($column->EXTRA ?? '', 'auto_increment') === false) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                DB::statement('ALTER TABLE nationalities MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('nationalities')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                DB::statement('ALTER TABLE nationalities MODIFY id BIGINT UNSIGNED NOT NULL');
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }
}
