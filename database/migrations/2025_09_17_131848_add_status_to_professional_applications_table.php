<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToProfessionalApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('professional_applications', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('other_requirement'); // ممكن تختار default حسب احتياجك
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('professional_applications', function (Blueprint $table) {
                        $table->dropColumn('status');

        });
    }
}
