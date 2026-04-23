<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixMaidsAddedByForeignKeys extends Migration
{
    public function up()
    {
        if (!$this->canProcessAdmins()) {
            return;
        }

        $this->dropAddedByForeignKeys();

        Schema::table('maids', function (Blueprint $table) {
            $table->unsignedBigInteger('added_by')->nullable()->after('office_id')->comment('اضيف بواسطة')->change();
        });

        DB::statement(
            'UPDATE `maids` AS `m`
             LEFT JOIN `admins` AS `a` ON `a`.`id` = `m`.`added_by`
             SET `m`.`added_by` = NULL
             WHERE `m`.`added_by` IS NOT NULL AND `a`.`id` IS NULL'
        );

        Schema::table('maids', function (Blueprint $table) {
            $table->foreign('added_by', 'maids_added_by_admin_fk')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        if (!$this->canProcessBase()) {
            return;
        }

        $this->dropAddedByForeignKeys();

        if (!Schema::hasTable('users')) {
            return;
        }

        DB::statement(
            'UPDATE `maids` AS `m`
             LEFT JOIN `users` AS `u` ON `u`.`id` = `m`.`added_by`
             SET `m`.`added_by` = NULL
             WHERE `m`.`added_by` IS NOT NULL AND `u`.`id` IS NULL'
        );

        Schema::table('maids', function (Blueprint $table) {
            $table->foreign('added_by', 'maids_added_by_foreign')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    private function canProcessBase(): bool
    {
        return DB::connection()->getDriverName() === 'mysql'
            && Schema::hasTable('maids')
            && Schema::hasColumn('maids', 'added_by');
    }

    private function canProcessAdmins(): bool
    {
        return $this->canProcessBase() && Schema::hasTable('admins');
    }

    private function dropAddedByForeignKeys(): void
    {
        foreach ($this->getAddedByForeignKeyNames() as $constraintName) {
            $escapedName = str_replace('`', '``', $constraintName);
            DB::statement("ALTER TABLE `maids` DROP FOREIGN KEY `{$escapedName}`");
        }
    }

    private function getAddedByForeignKeyNames(): array
    {
        $databaseName = DB::connection()->getDatabaseName();

        return collect(DB::select(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$databaseName, 'maids', 'added_by']
        ))
            ->map(fn ($row) => data_get($row, 'CONSTRAINT_NAME'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
