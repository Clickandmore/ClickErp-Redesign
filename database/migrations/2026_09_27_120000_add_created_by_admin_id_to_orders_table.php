<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByAdminIdToOrdersTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('orders', 'created_by_admin_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('orders', 'created_by_admin_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('created_by_admin_id');
        });
    }
}
