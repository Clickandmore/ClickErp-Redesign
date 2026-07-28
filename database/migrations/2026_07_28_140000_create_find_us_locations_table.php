<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('find_us_locations', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name');
            $table->text('branch_address');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->text('map_embed_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('find_us_locations');
    }
};
