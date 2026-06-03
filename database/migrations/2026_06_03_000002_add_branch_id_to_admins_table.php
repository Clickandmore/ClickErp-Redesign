<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToAdminsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('admins') || Schema::hasColumn('admins', 'branch_id')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $column = $table->unsignedBigInteger('branch_id')->nullable();

            Schema::hasColumn('admins', 'role_id')
                ? $column->after('role_id')
                : $column->after('id');

            $table->index('branch_id', 'admins_branch_id_index');

            if (Schema::hasTable('branches')) {
                $table->foreign('branch_id', 'admins_branch_id_foreign')
                    ->references('id')
                    ->on('branches')
                    ->nullOnDelete();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('admins') || !Schema::hasColumn('admins', 'branch_id')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasTable('branches')) {
                try {
                    $table->dropForeign('admins_branch_id_foreign');
                } catch (\Throwable $exception) {
                    //
                }
            }

            try {
                $table->dropIndex('admins_branch_id_index');
            } catch (\Throwable $exception) {
                //
            }

            $table->dropColumn('branch_id');
        });
    }
}
