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
        Schema::create('fn_depreciation_principles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principle_id')->constrained('fn_principles')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('fn_accounts')->onDelete('cascade');
            $table->unsignedBigInteger('constraint_id');
            $table->foreign('constraint_id')->references('id')->on('fn_constraints')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_depreciation_principles');
    }
};
