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
        Schema::create('fn_constraint_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('constraint_id')->nullable();
            $table->foreign('constraint_id')->references('id')->on('fn_constraints')->onDelete('cascade');
            $table->string('file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_constraint_files');
    }
};
