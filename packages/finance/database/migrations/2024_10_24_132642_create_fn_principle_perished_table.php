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
        Schema::create('fn_principle_perished', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principle_id')->constrained('fn_principles')->onDelete('cascade');
            $table->double('junk_value')->default(0);
            $table->string('perished_value')->nullable();
            $table->foreignId('perished_account_id')->nullable()->constrained('fn_accounts')->onDelete('cascade');
            $table->date('perished_end_date')->nullable();
            $table->integer('period')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_principle_perished');
    }
};
