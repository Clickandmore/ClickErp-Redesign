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
        Schema::create('fn_constraints', function (Blueprint $table) {
            $table->id();
            $table->integer('type');
            $table->double('value')->default(0);
            $table->date('date')->default(\Illuminate\Support\Carbon::now());
            $table->string('statement',500)->default('-');
            $table->unsignedBigInteger('year_id')->nullable();
            $table->foreign('year_id')->references('id')->on('fn_years')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_constraints');
    }
};
