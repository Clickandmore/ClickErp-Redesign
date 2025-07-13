<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersNationalityForeignKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the old foreign key constraint
            $table->dropForeign(['nationality_id']);

            // Add the new foreign key constraint pointing to countries table
            $table->foreign('nationality_id')
                ->references('id')
                ->on('countries')
                ->onDelete('cascade')
                ->onUpdate('cascade');
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
            // Drop the new foreign key constraint
            $table->dropForeign(['nationality_id']);

            // Restore the old foreign key constraint pointing to nationalities table
            $table->foreign('nationality_id')
                ->references('id')
                ->on('nationalities')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }
}
