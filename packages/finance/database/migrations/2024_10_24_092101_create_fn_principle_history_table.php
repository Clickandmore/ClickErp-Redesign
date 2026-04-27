<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFnPrincipleHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fn_principle_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principle_id')->constrained('fn_principles')->onDelete('cascade');
            $table->foreignId('from_website_id')->constrained('fn_principle_websites')->onDelete('cascade');
            $table->foreignId('to_website_id')->constrained('fn_principle_websites')->onDelete('cascade');
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
        Schema::dropIfExists('fn_principle_history');
    }
}
