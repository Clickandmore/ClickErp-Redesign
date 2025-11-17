<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImagesToAbouts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('about_us', function (Blueprint $table) {
            $table->string('image')->nullable();
            $table->string('image_vision')->nullable()->after('image');
            $table->string('image_message')->nullable()->after('image_vision');
            $table->string('image_value')->nullable()->after('image_message');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('about_us', function (Blueprint $table) {
            $table->dropColumn(['image', 'image_vision', 'image_message', 'image_value']);
        });
    }
}
