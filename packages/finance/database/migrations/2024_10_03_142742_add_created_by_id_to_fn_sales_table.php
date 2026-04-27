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
        Schema::table('fn_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_id')->after('id')->nullable();
            $table->foreign('created_by_id')->references('id')->on('admins')->onDelete('cascade');
        });

        Schema::table('fn_purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_id')->after('id')->nullable();
            $table->foreign('created_by_id')->references('id')->on('admins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_sales', function (Blueprint $table) {
            //
        });
    }
};
