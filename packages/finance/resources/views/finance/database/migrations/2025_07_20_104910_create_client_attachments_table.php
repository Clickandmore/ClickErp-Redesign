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
        Schema::create('fn_client_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->references('id')->on('fn_clients')->onDelete('cascade');
            $table->string('file')->default('no image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_attachments');
    }
};
