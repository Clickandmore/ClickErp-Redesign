<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('logo_header')->nullable();
            $table->string('logo_footer')->nullable();
            $table->string('favicon')->nullable();
            $table->string('site_color')->nullable();
            $table->string('email')->default('#');
            $table->string('phone')->default('#');
            $table->string('map_link', 1000)->nullable();
            $table->string('copyrights')->nullable();


            $table->string('twitter')->default('#');
            $table->string('facebook')->default('#');
            $table->string('instagram')->default('#');
            $table->string('snapchat')->default('#');
            $table->string('linkedin')->default('#');
            $table->string('youtube')->default('#');
            $table->string('whatsapp')->default('#');

            $table->string('other_phone')->default('#');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
