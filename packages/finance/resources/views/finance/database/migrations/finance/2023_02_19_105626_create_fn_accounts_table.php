<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFnAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fn_accounts', function (Blueprint $table) {
            $table->id(); // This will create an auto-increment primary key column named 'id'
            $table->string('name');
            $table->enum('status', ['debtor', 'creditor']);
            $table->tinyInteger('level');
            $table->bigInteger('code'); // Remove auto_increment
            $table->integer('type');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fn_accounts');
    }
}
