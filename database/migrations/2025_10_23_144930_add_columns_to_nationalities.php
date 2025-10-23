<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToNationalities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nationalities', function (Blueprint $table) {
            $table->double('salary_with_experience')->nullable()->after('price');
            $table->double('salary_with_no_experience')->nullable()->after('salary_with_experience');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nationalities', function (Blueprint $table) {
            $table->dropColumn(['salary_with_experience', 'salary_with_no_experience']);
        });
    }
}
