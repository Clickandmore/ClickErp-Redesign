<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortAtAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // تحقق إذا كان العمود موجود مسبقًا
        if (!Schema::hasColumn('admins', 'sort')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->integer('sort')->default(999)->after('admin_type');
            });
        }

        if (!Schema::hasColumn('shelter_workers', 'reserve_date')) {
            Schema::table('shelter_workers', function (Blueprint $table) {
                $table->date('reserve_date')->nullable()->after('reserve');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('admins', 'sort')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('sort');
            });
        }

        if (Schema::hasColumn('shelter_workers', 'reserve_date')) {
            Schema::table('shelter_workers', function (Blueprint $table) {
                $table->dropColumn('reserve_date');
            });
        }
    }
}
