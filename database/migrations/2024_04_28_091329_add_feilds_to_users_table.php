<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeildsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('nationality_id')
                ->nullable()
                ->constrained('nationalities')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('email',255)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('national_num',50)->nullable();
            $table->string('phone2',50)->nullable();
            $table->string('address',500)->nullable();
            $table->string('city',10)->nullable();
            $table->string('rooms_num',10)->nullable();
            $table->string('family_num',10)->nullable();
            $table->string('children',10)->nullable();
            $table->string('job',255)->nullable();
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
            //
        });
    }
}
