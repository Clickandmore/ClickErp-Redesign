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
        Schema::table('fn_bond_checks', function (Blueprint $table) {
            $table->unsignedBigInteger('constraint_id')->nullable()->after('year_id');
            $table->foreign('constraint_id')->references('id')->on('fn_constraints')->onDelete('cascade');
            $table->string('refuse_reason',1000)->nullable()->after('year_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_bonds_checks', function (Blueprint $table) {
            //
        });
    }
};
