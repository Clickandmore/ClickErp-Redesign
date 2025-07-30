<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRentalFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profession')) {
                $table->string('profession')->nullable()->after('job')->comment('المهنة');
            }
            if (!Schema::hasColumn('users', 'monthly_income')) {
                $table->decimal('monthly_income', 10, 2)->nullable()->after('profession')->comment('الدخل الشهري');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('users', 'profession')) $columns[] = 'profession';
            if (Schema::hasColumn('users', 'monthly_income')) $columns[] = 'monthly_income';
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
