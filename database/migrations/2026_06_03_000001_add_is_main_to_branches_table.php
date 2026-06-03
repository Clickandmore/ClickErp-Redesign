<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsMainToBranchesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'is_main')) {
                $table->boolean('is_main')->default(false)->after('is_active');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'is_main')) {
                $table->dropColumn('is_main');
            }
        });
    }
}
